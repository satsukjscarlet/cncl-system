<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>{{ $certificate->certificate_no }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 16px 22px 136px 22px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", "DejaVu Serif", serif;
            font-size: 13px;
            color: #111;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            margin-bottom: 10px;
        }

        .header-table td {
            border: none;
            vertical-align: top;
        }

        .logo-cell {
            width: 18%;
        }

        .logo {
            width: 112px;
            margin-top: 3px;
        }

        .brand-cell {
            width: 64%;
            text-align: center;
            padding-top: 7px;
        }

        .company-title {
            color: #d71920;
            font-family: Arial, "DejaVu Sans", sans-serif;
            font-size: 15.7px;
            font-weight: bold;
            line-height: 1.18;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .company-subtitle {
            color: #d71920;
            font-family: Arial, "DejaVu Sans", sans-serif;
            font-size: 13.5px;
            font-weight: bold;
            line-height: 1.18;
            text-transform: uppercase;
            margin-top: 3px;
        }

        .iso-cell {
            width: 18%;
            text-align: center;
            color: #0069b4;
            font-family: Arial, "DejaVu Sans", sans-serif;
            font-size: 9.7px;
            font-weight: bold;
            line-height: 1.18;
            padding-top: 3px;
        }

        .pcn-label {
            color: #d71920;
            margin-top: 2px;
            white-space: nowrap;
        }

        .pcn-number {
            color: #e3342f;
            font-size: 17px;
            letter-spacing: .8px;
            white-space: nowrap;
        }

        .cert-title {
            text-align: center;
            font-size: 23px;
            font-weight: bold;
            line-height: 1.15;
            margin: 8px 0 5px;
            text-transform: uppercase;
        }

        .cert-no {
            text-align: center;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .info-table {
            margin-bottom: 8px;
        }

        .info-table td {
            border: none;
            padding: 2px 3px;
            vertical-align: top;
            font-size: 14px;
            line-height: 1.25;
        }

        .info-table .index {
            width: 4%;
            text-align: right;
            padding-right: 8px;
        }

        .info-table .label {
            width: 18%;
            white-space: nowrap;
        }

        .info-table .value {
            width: 78%;
            font-weight: bold;
        }

        .info-table .sub-value {
            width: 78%;
            font-weight: normal;
        }

        .product-table {
            table-layout: fixed;
            margin-top: 4px;
            page-break-inside: auto;
        }

        .product-table thead {
            display: table-header-group;
        }

        .product-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .product-table th {
            border: 1px solid #222;
            background: #d9f8f7;
            padding: 5px 3px;
            text-align: center;
            vertical-align: middle;
            font-size: 12px;
            font-weight: bold;
            line-height: 1.15;
        }

        .product-table td {
            border-left: 1px solid #222;
            border-right: 1px solid #222;
            border-bottom: 1px solid #d6d6d6;
            padding: 3px 4px;
            height: 21px;
            vertical-align: top;
            font-size: 12px;
            line-height: 1.18;
        }

        .product-table td:nth-child(1),
        .product-table td:nth-child(3),
        .product-table td:nth-child(4),
        .product-table td:nth-child(5),
        .product-table td:nth-child(6) {
            text-align: center;
            vertical-align: middle;
        }

        .product-table tbody tr:last-child td {
            border-bottom: 1px solid #222;
        }

        .text-center {
            text-align: center;
        }

        .note {
            margin-top: 7px;
            font-size: 12.5px;
            line-height: 1.32;
        }

        .note strong {
            font-weight: bold;
        }

        .note-indent {
            padding-left: 58px;
        }

        .digital-signature {
            margin-top: 18px;
            text-align: center;
            color: #c74d4d;
            font-size: 10px;
            line-height: 1.25;
            font-weight: normal;
        }

        .footer-wrap {
            position: fixed;
            left: -22px;
            right: -22px;
            bottom: -136px;
            height: 128px;
            font-family: Arial, "DejaVu Sans", sans-serif;
        }

        .footer-website {
            width: 100%;
            height: 22px;
            padding: 3px 0 0 74px;
            color: #6aa842;
            font-size: 14px;
            line-height: 18px;
        }

        .footer-band {
            width: 100%;
            height: 106px;
            background: #77bf35;
            color: #fff;
            font-size: 10.5px;
            line-height: 1.22;
        }

        .footer-band td {
            border: none;
            width: 50%;
            padding: 9px 20px 8px 44px;
            vertical-align: top;
        }

        .footer-band strong {
            font-size: 11px;
        }

        .footer-nowrap {
            white-space: nowrap;
            font-size: 9.5px;
        }
    </style>
</head>

<body>
@php
    $pcnNo = str_pad((string) $certificate->id, 7, '0', STR_PAD_LEFT);
    $detailsCount = $certificate->details->count();
    $minimumRows = 9;
    $blankRows = max(0, $minimumRows - $detailsCount);
@endphp

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if (file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Tien Phong Plastic">
                @endif
            </td>
            <td class="brand-cell">
                <div class="company-title">CÔNG TY CỔ PHẦN NHỰA THIẾU NIÊN TIỀN PHONG</div>
                <div class="company-subtitle">TIEN PHONG PLASTIC JOINT STOCK COMPANY</div>
            </td>
            <td class="iso-cell">
                QUACERT<br>
                JAS-ANZ<br>
                ISO 9001:2015<br>
                TIENPHONG : {{ now()->year }}
                <div class="pcn-label">PCN: <span class="pcn-number">{{ $pcnNo }}</span></div>
            </td>
        </tr>
    </table>

    <div class="cert-title">PHIẾU CHỨNG NHẬN CHẤT LƯỢNG</div>
    <div class="cert-no">Số {{ $certificate->certificate_no }}</div>

    <table class="info-table">
        <tr>
            <td class="index">1</td>
            <td class="label">Tên khách hàng:</td>
            <td class="value">{{ $certificate->request->customer->customer_name ?? '' }}</td>
        </tr>
        <tr>
            <td class="index">2</td>
            <td class="label">Tên công trình:</td>
            <td class="value">{{ $certificate->request->customer->project_name ?? '' }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td class="sub-value">Địa điểm công trình: {{ $certificate->request->customer->project_address ?? '' }}</td>
        </tr>
        <tr>
            <td class="index">3</td>
            <td class="label">Ngày xuất hàng:</td>
            <td class="value">{{ $certificate->request->delivery_date ? $certificate->request->delivery_date->format('d/m/Y') : '' }}</td>
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
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $detail->product->product_name ?? '' }}</td>
                    <td>{{ rtrim(rtrim(number_format($detail->quantity, 2, '.', ''), '0'), '.') }}</td>
                    <td>{{ $detail->nominal_size }}</td>
                    <td>{{ $detail->technical_requirements }}</td>
                    <td>{{ $detail->quality_standard }}</td>
                </tr>
            @endforeach

            @for ($i = 0; $i < $blankRows; $i++)
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
        <span class="note-indent">Sản phẩm đạt yêu cầu theo tiêu chuẩn sản phẩm công ty đã công bố</span>
    </div>

    @if ($certificate->signed_at && !($hardCopy ?? false))
        <div class="digital-signature">
            Phiếu được ký điện tử bởi {{ $certificate->signed_by }}
            - Ngày ký: {{ $certificate->signed_at->format('d/m/Y') }}
        </div>
    @endif

    <div class="footer-wrap">
        <div class="footer-website">Website: www.nhuatienphong.vn</div>

        <table class="footer-band">
            <tr>
                <td>
                    <strong>- Trụ sở chính:</strong><br>
                    Số 2 An Đà, Gia Viên, TP. Hải Phòng, Việt Nam<br>
                    <strong>- Văn phòng giao dịch &amp; Nhà máy:</strong><br>
                    <span class="footer-nowrap">Số 222 Mạc Đăng Doanh, P. Hưng Đạo, TP. Hải Phòng, Việt Nam</span><br>
                    ĐT: (0225) 3813979 * Fax: (0225) 3813989
                </td>
                <td>
                    <strong>- Head office:</strong><br>
                    No 2 An Da St., Gia Vien Ward, Hai Phong City, Viet Nam<br>
                    <strong>- Liaison office &amp; Factory:</strong><br>
                    <span class="footer-nowrap">No 222 Mac Dang Doanh St., Hung Dao Ward, Hai Phong City, Viet Nam</span><br>
                    Tel: (0225) 3813979 * Fax: (0225) 3813989
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
