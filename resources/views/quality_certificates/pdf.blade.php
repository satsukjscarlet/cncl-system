<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>{{ $certificate->certificate_no }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            vertical-align: top;
        }

        .logo {
            width: 120px;
        }

        .company-title {
            text-align: center;
            color: #c00000;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
        }

        .company-subtitle {
            text-align: center;
            color: #c00000;
            font-size: 14px;
            font-weight: bold;
        }

        .cert-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .cert-no {
            text-align: center;
            margin-bottom: 10px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .info-table td {
            border: none;
            padding: 3px 4px;
            vertical-align: top;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .product-table th {
            border: 1px solid #000;
            background: #d9f3f3;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
        }

        .product-table td {
            border: 1px solid #000;
            padding: 5px 4px;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .note {
            margin-top: 12px;
            font-size: 12px;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
            font-size: 12px;
        }

        .signed-box {
            margin-top: 20px;
            text-align: center;
            color: #c00000;
            font-size: 11px;
        }

        .footer {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td style="width: 20%;">
                {{-- Nếu có logo thật, đặt file tại public/images/logo.png --}}
                @if (file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" class="logo">
                @endif
            </td>

            <td style="width: 60%;">
                <div class="company-title">
                    CÔNG TY CỔ PHẦN NHỰA THIẾU NIÊN TIỀN PHONG
                </div>
                <div class="company-subtitle">
                    TIEN PHONG PLASTIC JOINT STOCK COMPANY
                </div>
            </td>

            <td style="width: 20%; text-align: right;">
                <div><strong>ISO 9001:2015</strong></div>
                <div>TIENPHONG:{{ now()->year }}</div>
                <div style="color:#c00000;"><strong>PCN: {{ $certificate->certificate_no }}</strong></div>
            </td>
        </tr>
    </table>

    <div class="cert-title">
        PHIẾU CHỨNG NHẬN CHẤT LƯỢNG
    </div>

    <div class="cert-no">
        Số: {{ $certificate->certificate_no }}
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 25%;">1 Tên khách hàng:</td>
            <td>
                {{ $certificate->request->customer->customer_name ?? '' }}
            </td>
        </tr>

        <tr>
            <td>2 Tên công trình:</td>
            <td>
                {{ $certificate->request->customer->project_name ?? '' }}
            </td>
        </tr>

        <tr>
            <td>Địa điểm công trình:</td>
            <td>
                {{ $certificate->request->customer->project_address ?? '' }}
            </td>
        </tr>

        <tr>
            <td>3 Ngày xuất hàng:</td>
            <td>
                {{ $certificate->request->delivery_date ? $certificate->request->delivery_date->format('d/m/Y') : '' }}
            </td>
        </tr>
    </table>

    <table class="product-table">
        <thead>
            <tr>
                <th style="width: 5%;">TT</th>
                <th style="width: 34%;">Tên sản phẩm</th>
                <th style="width: 10%;">Số lượng</th>
                <th style="width: 15%;">Kích thước danh nghĩa</th>
                <th style="width: 16%;">Yêu cầu kỹ thuật</th>
                <th style="width: 20%;">Tiêu chuẩn sản phẩm</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($certificate->details as $detail)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $detail->product->product_name ?? '' }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($detail->quantity, 2, '.', ''), '0'), '.') }}
                    </td>
                    <td class="text-center">{{ $detail->nominal_size }}</td>
                    <td class="text-center">{{ $detail->technical_requirements }}</td>
                    <td class="text-center">{{ $detail->quality_standard }}</td>
                </tr>
            @endforeach

            @for ($i = $certificate->details->count() + 1; $i <= 15; $i++)
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
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Sản phẩm đạt yêu cầu theo tiêu chuẩn sản phẩm công ty đã công bố
    </div>

    @if (!($hardCopy ?? false) && $certificate->signed_at)
        <div class="signed-box">
            Ký bởi {{ $certificate->signed_by }}
            <br>
            Signed by {{ $certificate->signed_by }}
            <br>
            Ký ngày: {{ $certificate->signed_at->format('d/m/Y H:i') }}
        </div>
    @endif

    @if ($hardCopy ?? false)
        <div class="signature">
            <strong>PHÒNG THỬ NGHIỆM</strong>
            <br><br><br><br>
            <em>Ký, ghi rõ họ tên và đóng dấu</em>
        </div>
    @endif

    <div class="footer">
        QLCL-QT01-M06
        <span style="float:right;">www.nhuatienphong.vn</span>
    </div>

</body>

</html>
