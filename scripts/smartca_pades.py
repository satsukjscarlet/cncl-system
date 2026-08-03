#!/usr/bin/env python
import argparse
import asyncio
import base64
import hashlib
import json
from datetime import datetime, timezone
from pathlib import Path

from asn1crypto import algos, cms, x509
from pyhanko.pdf_utils.incremental_writer import IncrementalPdfFileWriter
from pyhanko.sign import fields, signers
from pyhanko.sign.signers import pdf_byterange, pdf_cms, pdf_signer
from pyhanko_certvalidator.registry import SimpleCertificateStore


def _b64decode(value):
    return base64.b64decode(value.encode("ascii"), validate=False)


def _load_certificates(cert_base64, chain_json=None):
    signing_cert = x509.Certificate.load(_b64decode(cert_base64))
    store = SimpleCertificateStore()

    chain = chain_json or {}
    if isinstance(chain, str):
        chain = json.loads(chain)

    for value in chain.values() if isinstance(chain, dict) else chain:
        if value:
            store.register(x509.Certificate.load(_b64decode(value)))

    return signing_cert, store


def _signature_mechanism():
    return algos.SignedDigestAlgorithm({"algorithm": "sha256_rsa"})


async def prepare(args):
    signing_cert, cert_store = _load_certificates(args.cert_base64, args.chain_json)
    signer = signers.ExternalSigner(
        signing_cert=signing_cert,
        cert_registry=cert_store,
        signature_value=args.signature_placeholder_bytes,
        signature_mechanism=_signature_mechanism(),
    )

    input_path = Path(args.input)
    prepared_path = Path(args.prepared)
    prepared_path.parent.mkdir(parents=True, exist_ok=True)

    with input_path.open("rb") as inf:
        writer = IncrementalPdfFileWriter(inf)

        if args.visible:
            fields.append_signature_field(
                writer,
                fields.SigFieldSpec(
                    sig_field_name=args.field_name,
                    on_page=args.page,
                    box=tuple(int(part) for part in args.box.split(",")),
                ),
            )

        meta = signers.PdfSignatureMetadata(
            field_name=args.field_name,
            md_algorithm="sha256",
            name=args.signer_name,
            reason=args.reason,
            location=args.location,
            subfilter=fields.SigSeedSubFilter.PADES,
        )
        pdf_signer = signers.PdfSigner(meta, signer=signer)

        with prepared_path.open("w+b") as outf:
            prepared_digest, tbs_doc, output = await pdf_signer.async_digest_doc_for_signing(
                writer,
                bytes_reserved=args.bytes_reserved,
                output=outf,
            )

            signed_attr_settings = pdf_cms.PdfCMSSignedAttributes(
                signing_time=datetime.now(timezone.utc),
            )
            signed_attrs = await signer.signed_attrs(
                prepared_digest.document_digest,
                "sha256",
                attr_settings=signed_attr_settings,
                use_pades=tbs_doc.use_pades,
                timestamper=tbs_doc.timestamper,
                dry_run=False,
                is_pdf_sig=True,
            )
            signed_attrs_der = signed_attrs.dump()
            second_hash = hashlib.sha256(signed_attrs_der).digest()

    state = {
        "document_digest": base64.b64encode(prepared_digest.document_digest).decode("ascii"),
        "reserved_region_start": prepared_digest.reserved_region_start,
        "reserved_region_end": prepared_digest.reserved_region_end,
        "signed_attrs_der": base64.b64encode(signed_attrs_der).decode("ascii"),
        "second_hash_hex": second_hash.hex(),
        "second_hash_base64": base64.b64encode(second_hash).decode("ascii"),
        "prepared_pdf": str(prepared_path),
        "field_name": args.field_name,
        "md_algorithm": "sha256",
        "signature_mechanism": "sha256_rsa",
    }

    state_path = Path(args.state)
    state_path.parent.mkdir(parents=True, exist_ok=True)
    state_path.write_text(json.dumps(state, indent=2), encoding="utf-8")

    print(json.dumps(state, ensure_ascii=False))


async def finalize(args):
    state = json.loads(Path(args.state).read_text(encoding="utf-8"))
    signing_cert, cert_store = _load_certificates(args.cert_base64, args.chain_json)

    remote_signature = _b64decode(args.signature_value)
    signed_attrs = cms.CMSAttributes.load(_b64decode(state["signed_attrs_der"]))

    signer = signers.ExternalSigner(
        signing_cert=signing_cert,
        cert_registry=cert_store,
        signature_value=remote_signature,
        signature_mechanism=_signature_mechanism(),
    )
    signature_cms = await signer.async_sign_prescribed_attributes(
        "sha256",
        signed_attrs,
    )

    prepared_digest = pdf_byterange.PreparedByteRangeDigest(
        document_digest=_b64decode(state["document_digest"]),
        reserved_region_start=int(state["reserved_region_start"]),
        reserved_region_end=int(state["reserved_region_end"]),
    )

    prepared_path = Path(state["prepared_pdf"])
    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_bytes(prepared_path.read_bytes())

    with output_path.open("r+b") as outf:
        pdf_signer.PdfTBSDocument.resume_signing(
            outf,
            prepared_digest,
            signature_cms,
        )

    print(json.dumps({"signed_pdf": str(output_path)}, ensure_ascii=False))


def main():
    parser = argparse.ArgumentParser()
    sub = parser.add_subparsers(dest="command", required=True)

    p = sub.add_parser("prepare")
    p.add_argument("--input", required=True)
    p.add_argument("--prepared", required=True)
    p.add_argument("--state", required=True)
    p.add_argument("--cert-base64", required=True)
    p.add_argument("--chain-json")
    p.add_argument("--field-name", default="Signature1")
    p.add_argument("--signer-name")
    p.add_argument("--reason", default="Ky so phieu CNCL")
    p.add_argument("--location", default="VNPT SmartCA")
    p.add_argument("--bytes-reserved", type=int, default=65536)
    p.add_argument("--signature-placeholder-bytes", type=int, default=512)
    p.add_argument("--visible", action="store_true")
    p.add_argument("--page", type=int, default=0)
    p.add_argument("--box", default="360,80,560,150")
    p.set_defaults(func=prepare)

    f = sub.add_parser("finalize")
    f.add_argument("--state", required=True)
    f.add_argument("--output", required=True)
    f.add_argument("--cert-base64", required=True)
    f.add_argument("--chain-json")
    f.add_argument("--signature-value", required=True)
    f.set_defaults(func=finalize)

    args = parser.parse_args()
    asyncio.run(args.func(args))


if __name__ == "__main__":
    main()
