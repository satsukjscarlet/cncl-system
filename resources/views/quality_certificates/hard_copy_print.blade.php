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
            padding: 1.5px 3px;
            line-height: 1.1;
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
            top: 490pt;
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
            top: 490pt;
            margin: 0;
            font-size: 10pt;
            color: #000;
        }
    </style>
</head>

<body>
    @php
        $details = $certificate->details->values();
        // Point coordinates are matched to the pre-printed hard-copy stock.
        $noteTop = 490;
        $safeGapBeforeNote = 14;
        $certNoBlockHeight = 34;
        $infoLineHeight = 15.6;
        $infoVerticalPadding = 3;
        $infoMarginBottom = 11;
        $tableMarginTop = 3;
        $tableHeaderHeight = 44;
        $blankRowHeight = 18;
        $pages = collect();
        $currentRows = [];
        $currentHeight = 0;
        $customer = $certificate->request->customer ?? null;
        $deliveryDate = $certificate->request?->delivery_date
            ? $certificate->request->delivery_date->format('d/m/Y')
            : '';
        $signerName = 'Vũ Thị Diệu Thúy';

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

        $estimateInfoRowHeight = static function ($value) use ($estimateLines, $infoLineHeight, $infoVerticalPadding): float {
            return max(18.6, ($estimateLines($value, 62) * $infoLineHeight) + $infoVerticalPadding);
        };

        $infoRowsHeight = $estimateInfoRowHeight($customer->customer_name ?? '')
            + $estimateInfoRowHeight($customer->project_name ?? '')
            + ($customer?->project_address ? $estimateInfoRowHeight('Dia diem cong trinh: ' . $customer->project_address) : 0)
            + $estimateInfoRowHeight($deliveryDate);

        $calculatedTableStartY = $certNoBlockHeight + $infoRowsHeight + $infoMarginBottom + $tableMarginTop;
        $tableStartY = max(214, $calculatedTableStartY);
        $tableMaxHeight = max(110, $noteTop - $safeGapBeforeNote - $tableStartY - $tableHeaderHeight);

        $estimateRowHeight = static function ($detail) use ($estimateLines, $formatNumber) {
            $product = $detail->product;

            $estimatedLines = max(
                1,
                $estimateLines($product->product_name ?? '', 30),
                $estimateLines($product->unit ?? '', 5),
                $estimateLines($formatNumber($detail->quantity), 5),
                $estimateLines($detail->nominal_size, 10),
                $estimateLines($detail->technical_requirements, 10),
                $estimateLines($detail->quality_standard, 16)
            );

            return max(18, min(86, 20 + (($estimatedLines - 1) * 16)));
        };

        foreach ($details as $detail) {
            $rowHeight = $estimateRowHeight($detail);

            if (!empty($currentRows) && ($currentHeight + $rowHeight) > $tableMaxHeight) {
                $pages->push([
                    'details' => collect($currentRows),
                    'height' => $currentHeight,
                ]);
                $currentRows = [];
                $currentHeight = 0;
            }

            $currentRows[] = $detail;
            $currentHeight += $rowHeight;
        }

        if (!empty($currentRows)) {
            $pages->push([
                'details' => collect($currentRows),
                'height' => $currentHeight,
            ]);
        }

        if ($pages->isEmpty()) {
            $pages = collect([[
                'details' => collect(),
                'height' => 0,
            ]]);
        }

        $totalPages = $pages->count();
    @endphp

    @foreach ($pages as $pageIndex => $page)
        @php
            $pageDetails = $page['details'];
            $blankRows = $loop->last
                ? min(4, max(0, (int) floor(($tableMaxHeight - $page['height']) / $blankRowHeight)))
                : 0;
            $rowOffset = $pages->take($pageIndex)->sum(fn ($item) => $item['details']->count());
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

                        @for ($i = 0; $i < $blankRows; $i++)
                            <tr>
                                <td>&nbsp;</td>
                                <td></td>
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
                    <span class="second-line">Sản phẩm đạt yêu cầu theo tiêu chuẩn sản phẩm công ty đã công bố</span>
                </div>

                <div class="page-number">Trang {{ $pageIndex + 1 }}/{{ $totalPages }}</div>
                <div class="signer-name">{{ $signerName }}</div>
            </div>
        </div>
    @endforeach
</body>

</html>
