<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>{{ $certificate->certificate_no }}</title>

    <style>
        @page {
            margin: 16px 24px 78px 24px;
        }

        body {
            font-family: "Times New Roman", Times, DejaVu Serif, serif;
            font-size: 13px;
            color: #111;
            margin: 0;
        }

        table {
            border-collapse: collapse;
        }

        .header-table {
            width: 100%;
            margin-bottom: 12px;
        }

        .header-table td {
            border: none;
            vertical-align: top;
        }

        .logo {
            width: 108px;
            margin-top: 4px;
        }

        .company-title {
            text-align: center;
            color: #c92424;
            font-weight: bold;
            font-size: 17px;
            line-height: 1.28;
            text-transform: uppercase;
            padding-top: 10px;
        }

        .company-subtitle {
            text-align: center;
            color: #c92424;
            font-weight: bold;
            font-size: 16px;
            line-height: 1.28;
            text-transform: uppercase;
        }

        .iso-box {
            text-align: center;
            color: #1267ac;
            font-family: Arial, DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.25;
            font-weight: bold;
            padding-top: 5px;
        }

        .pcn {
            color: #e24932;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .cert-title {
            text-align: center;
            font-size: 25px;
            font-weight: bold;
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        .cert-no {
            text-align: center;
            font-size: 15px;
            margin-bottom: 7px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 7px;
        }

        .info-table td {
            border: none;
            padding: 2px 4px;
            vertical-align: top;
            line-height: 1.25;
            font-size: 14px;
        }

        .info-table .label {
            width: 23%;
            white-space: nowrap;
        }

        .product-table {
            width: 100%;
            table-layout: fixed;
        }

        .product-table th {
            border: 1px solid #222;
            background: #d6f8f8;
            padding: 5px 3px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.18;
            vertical-align: middle;
        }

        .product-table td {
            border-left: 1px solid #222;
            border-right: 1px solid #222;
            border-bottom: 1px solid #d8d8d8;
            padding: 2px 4px;
            height: 16px;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.18;
        }

        .product-table tbody tr:last-child td {
            border-bottom: 1px solid #222;
        }

        .text-center {
            text-align: center;
        }

        .note {
            margin-top: 7px;
            font-size: 12px;
            line-height: 1.35;
        }

        .note strong {
            font-weight: bold;
        }

        .signature-space {
            height: 36px;
            text-align: center;
            color: #c95b5b;
            font-size: 10px;
            line-height: 1.2;
            padding-top: 8px;
        }

        .footer-wrap {
            position: fixed;
            left: -24px;
            right: -24px;
            bottom: 0;
            height: 76px;
        }

        .website {
            height: 18px;
            line-height: 18px;
            color: #65a834;
            font-size: 15px;
            padding-left: 64px;
        }

        .footer-band {
            width: 100%;
            height: 58px;
            background: #80ca3e;
            color: #fff;
            font-family: Arial, DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.25;
        }

        .footer-band td {
            border: none;
            width: 50%;
            padding: 7px 52px;
            vertical-align: top;
        }
    </style>
</head>

<body>
@php
    $pcnNo = str_pad((string) $certificate->id, 7, '0', STR_PAD_LEFT);
@endphp

    <table class="header-table">
        <tr>
            <td style="width: 21%;">
                @if (file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" class="logo">
                @endif
            </td>
            <td style="width: 58%;">
                <div class="company-title">CÔNG TY CỔ PHẦN NHỰA THIẾU NIÊN TIỀN PHONG</div>
                <div class="company-subtitle">TIEN PHONG PLASTIC JOINT STOCK COMPANY</div>
            </td>
            <td style="width: 21%;">
                <div class="iso-box">
                    QUACERT &nbsp;&nbsp; JAS-ANZ<br>
                    ISO 9001:2015<br>
                    TIENPHONG : {{ now()->year }}<br>
                    PCN: <span class="pcn">{{ $pcnNo }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="cert-title">PHIẾU CHỨNG NHẬN CHẤT LƯỢNG</div>
    <div class="cert-no">Số {{ $certificate->certificate_no }}</div>

    <table class="info-table">
        <tr>
            <td class="label">1 Tên khách hàng:</td>
            <td>{{ $certificate->request->customer->customer_name ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">2 Tên công trình:</td>
            <td>{{ $certificate->request->customer->project_name ?? '' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Địa điểm công trình: {{ $certificate->request->customer->project_address ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">3 Ngày xuất hàng:</td>
            <td>{{ $certificate->request->delivery_date ? $certificate->request->delivery_date->format('d/m/Y') : '' }}</td>
        </tr>
    </table>

    <table class="product-table">
        <thead>
            <tr>
                <th style="width: 5%;">TT</th>
                <th style="width: 35%;">Tên sản phẩm</th>
                <th style="width: 10%;">Số lượng</th>
                <th style="width: 14%;">Kích thước<br>danh nghĩa</th>
                <th style="width: 14%;">Yêu cầu kỹ<br>thuật</th>
                <th style="width: 22%;">Tiêu chuẩn sản phẩm</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($certificate->details as $detail)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $detail->product->product_name ?? '' }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($detail->quantity, 2, '.', ''), '0'), '.') }}</td>
                    <td class="text-center">{{ $detail->nominal_size }}</td>
                    <td class="text-center">{{ $detail->technical_requirements }}</td>
                    <td class="text-center">{{ $detail->quality_standard }}</td>
                </tr>
            @endforeach

            @for ($i = $certificate->details->count() + 1; $i <= 11; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="note">
        <strong>Ghi chú:</strong> Phiếu này thay thế cho phiếu chứng nhận xuất xưởng hàng hóa
        <br>
        <span style="padding-left: 48px;">Sản phẩm đạt yêu cầu theo tiêu chuẩn sản phẩm công ty đã công bố</span>
    </div>

    <div class="signature-space">
        @if ($hardCopy ?? false)
            <strong>PHÒNG THỬ NGHIỆM</strong><br><br>
            <em>Ký, ghi rõ họ tên và đóng dấu</em>
        @elseif ($certificate->signed_at)
            Ký bởi {{ $certificate->signed_by }}<br>
            Signed by {{ $certificate->signed_by }}<br>
            Ký ngày: {{ $certificate->signed_at->format('d/m/Y') }}
        @endif
    </div>

    <div class="footer-wrap">
        <div class="website">Website: www.nhuatienphong.vn</div>

        <table class="footer-band">
            <tr>
                <td>
                    - Trụ sở chính:<br>
                    Số 2 An Đà, Gia Viên, TP. Hải Phòng, Việt Nam<br>
                    - Văn phòng giao dịch &amp; Nhà máy:<br>
                    Số 222 Mạc Đăng Doanh, P. Hưng Đạo, TP. Hải Phòng, Việt Nam<br>
                    ĐT: (0225) 3813979 - Fax: (0225) 3813989
                </td>
                <td>
                    - Head office:<br>
                    No 2 An Da St., Gia Vien Ward, Hai Phong City, Viet Nam<br>
                    - Liaison office &amp; Factory:<br>
                    No 222 Mac Dang Doanh St., Hung Dao Ward, Hai Phong City, Viet Nam<br>
                    Tel: (0225) 3813979 - Fax: (0225) 3813989
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
