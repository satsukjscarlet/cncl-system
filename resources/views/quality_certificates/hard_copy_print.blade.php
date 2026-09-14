<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>{{ $certificate->certificate_no }} - In phôi</title>

    <style>
        @font-face {
            font-family: "TimesNewRomanPdf";
            font-style: normal;
            font-weight: normal;
            src: url("{{ str_replace('\\', '/', public_path('fonts/times.ttf')) }}") format("truetype");
        }

        @font-face {
            font-family: "TimesNewRomanPdf";
            font-style: normal;
            font-weight: bold;
            src: url("{{ str_replace('\\', '/', public_path('fonts/timesbd.ttf')) }}") format("truetype");
        }

        @font-face {
            font-family: "TimesNewRomanPdf";
            font-style: italic;
            font-weight: normal;
            src: url("{{ str_replace('\\', '/', public_path('fonts/timesi.ttf')) }}") format("truetype");
        }

        @font-face {
            font-family: "TimesNewRomanPdf";
            font-style: italic;
            font-weight: bold;
            src: url("{{ str_replace('\\', '/', public_path('fonts/timesbi.ttf')) }}") format("truetype");
        }

        @page {
            size: A4 portrait;
            margin: 104pt 34pt 0 24pt;
        }

        body {
            margin: 0;
            color: #000;
            font-family: "TimesNewRomanPdf", "Times New Roman", Times, serif;
            font-size: 13pt;
        }

        * {
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .page {
            width: 100%;
            height: 650pt;
            position: relative;
        }

        .print-area {
            width: 100%;
        }

        .cert-no {
            text-align: center;
            font-size: 13pt;
            margin-top: 14pt;
            margin-bottom: 4pt;
        }

        .info-table {
            width: 100%;
            margin-bottom: 11pt;
        }

        .info-table td {
            border: none;
            padding: 1.5pt 2pt;
            line-height: 1.2;
            vertical-align: top;
        }

        .info-table .index {
            width: 14pt;
            text-align: right;
            padding-right: 4pt;
        }

        .info-table .label {
            width: 91pt;
            white-space: nowrap;
        }

        .value {
            color: #f00;
        }

        .product-table {
            table-layout: fixed;
            margin-top: 4px;
            border: 1pt solid #111;
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
            background: #d6ffff;
            border: 1pt solid #111;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 4px 3px;
            line-height: 1.15;
            font-size: 12pt;
        }

        .product-table td {
            border-left: 1pt solid #111;
            border-right: 1pt solid #111;
            border-bottom: 1px solid #d6d6d6;
            height: 18pt;
            padding: 2px 4px;
            line-height: 1.18;
            font-size: 12pt;
            vertical-align: top;
            white-space: normal;
            word-break: break-word;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .product-table td:nth-child(1),
        .product-table td:nth-child(3),
        .product-table td:nth-child(4),
        .product-table td:nth-child(5),
        .product-table td:nth-child(6),
        .product-table td:nth-child(7) {
            text-align: center;
            vertical-align: middle;
        }

        .product-table tbody tr:last-child td {
            border-bottom: 1pt solid #111;
        }

        .text-center {
            text-align: center;
        }

        .note {
            position: absolute;
            left: 0;
            right: 0;
            top: 430pt;
            margin: 0;
            font-size: 11pt;
            line-height: 1.35;
        }

        .note .second-line {
            display: block;
            text-align: left;
        }

        .signer-name {
            position: absolute;
            left: 75pt;
            top: 630pt;
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
        }

        .page-number {
            position: absolute;
            right: 0;
            top: 474pt;
            margin: 0;
            font-size: 10pt;
            color: #000;
        }
    </style>
</head>

<body>
    @php
        $details = $certificate->details->values();
        $pageCapacity = 9;
        $pages = collect();
        $currentRows = [];
        $currentUnits = 0;

        $formatNumber = static function ($quantity) {
            if ($quantity === null || $quantity === '') {
                return '';
            }

            return rtrim(rtrim(number_format((float) $quantity, 2, '.', ''), '0'), '.');
        };

        $breakableText = static function ($value): string {
            $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

            return preg_replace('/([&\/;,:\-])(?=\S)/u', '$1 ', $value) ?? $value;
        };

        $estimateLines = static function ($value, int $charsPerLine) use ($breakableText): int {
            $value = $breakableText($value);

            if ($value === '') {
                return 1;
            }

            return max(1, (int) ceil(mb_strlen($value) / max(1, $charsPerLine)));
        };

        $rowUnit = static function ($detail) use ($estimateLines, $formatNumber) {
            $product = $detail->product;

            $estimatedLines = max(
                1,
                $estimateLines($product->product_name ?? '', 42),
                $estimateLines($product->unit ?? '', 8),
                $estimateLines($formatNumber($detail->quantity), 8),
                $estimateLines($detail->nominal_size, 15),
                $estimateLines($detail->technical_requirements, 15),
                $estimateLines($detail->quality_standard, 22)
            );

            return min(5, $estimatedLines);
        };

        foreach ($details as $detail) {
            $units = $rowUnit($detail);

            if (!empty($currentRows) && ($currentUnits + $units) > $pageCapacity) {
                $pages->push(collect($currentRows));
                $currentRows = [];
                $currentUnits = 0;
            }

            $currentRows[] = $detail;
            $currentUnits += $units;
        }

        if (!empty($currentRows)) {
            $pages->push(collect($currentRows));
        }

        if ($pages->isEmpty()) {
            $pages = collect([collect()]);
        }

        $totalPages = $pages->count();

        $customer = $certificate->request->customer ?? null;
        $deliveryDate = $certificate->request?->delivery_date
            ? $certificate->request->delivery_date->format('d/m/Y')
            : '';
        $signerName = 'Vũ Thị Diệu Thúy';
    @endphp

    @foreach ($pages as $pageIndex => $pageDetails)
        @php
            $rowOffset = $pages->take($pageIndex)->sum(fn ($page) => $page->count());
        @endphp

        <div class="page" style="{{ $loop->last ? '' : 'page-break-after: always;' }}">
            <div class="print-area">
                <div class="cert-no">Số {{ $certificate->certificate_no }}</div>

                <table class="info-table">
                    <tr>
                        <td class="index">1.</td>
                        <td class="label">Tên khách hàng:</td>
                        <td class="value">{{ $customer->customer_name ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="index">2.</td>
                        <td class="label">Tên công trình :</td>
                        <td class="value">{{ $customer->project_name ?? '' }}</td>
                    </tr>
                    @if ($customer?->project_address)
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="value">Địa điểm công trình: {{ $customer->project_address }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="index">3.</td>
                        <td class="label">Ngày xuất hàng:</td>
                        <td class="value">{{ $deliveryDate }}</td>
                    </tr>
                </table>

                <table class="product-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">TT</th>
                            <th style="width: 31%;">Tên sản phẩm</th>
                            <th style="width: 6%;">ĐVT</th>
                            <th style="width: 8%;">Số lượng</th>
                            <th style="width: 14%;">Kích thước<br>danh nghĩa</th>
                            <th style="width: 14%;">Yêu cầu kỹ<br>thuật</th>
                            <th style="width: 22%;">Tiêu chuẩn sản phẩm</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pageDetails as $detail)
                            <tr>
                                <td class="text-center">{{ $rowOffset + $loop->iteration }}</td>
                                <td>{{ $breakableText($detail->product->product_name ?? '') }}</td>
                                <td class="text-center">{{ $breakableText($detail->product->unit ?? '') }}</td>
                                <td class="text-center">{{ $formatNumber($detail->quantity) }}</td>
                                <td class="text-center">{{ $breakableText($detail->nominal_size) }}</td>
                                <td class="text-center">{{ $breakableText($detail->technical_requirements) }}</td>
                                <td class="text-center">{{ $breakableText($detail->quality_standard) }}</td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>

                <div class="note">
                    <strong>Ghi chú:</strong> Phiếu này thay thế cho phiếu chứng nhận xuất xưởng hàng hóa
                    <span class="second-line">Sản phẩm đạt yêu cầu theo tiêu chuẩn sản phẩm công ty đã công bố</span>
                </div>

                <div class="page-number">Trang {{ $pageIndex + 1 }}/{{ $totalPages }}</div>
                <div class="signer-name">{{ $signerName }}</div>
            </div>
        </div>
    @endforeach
</body>

</html>
