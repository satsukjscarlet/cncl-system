<?php

namespace App\Services;

use App\Models\QualityCertificate;
use TCPDF;
use TCPDF_FONTS;

class HardCopyCertificatePdfService
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;
    private const CONTENT_TOP = 104.0;
    private const LEFT = 24.0;
    private const RIGHT = 34.0;
    private const NOTE_TOP = 490.0;
    private const NOTE_SAFE_GAP = 10.0;
    private const SIGNER_TOP = 630.0;

    private TCPDF $pdf;
    private string $fontRegular = 'times';
    private string $fontBold = 'times';

    public function render(QualityCertificate $certificate): string
    {
        $this->pdf = new TCPDF('P', 'pt', 'A4', true, 'UTF-8', false);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins(0, 0, 0);
        $this->pdf->SetAutoPageBreak(false, 0);
        $this->pdf->setFontSubsetting(true);

        $this->loadFonts();

        $pages = $this->paginate($certificate);
        $totalPages = max(1, count($pages));

        $rowOffset = 0;

        foreach ($pages as $pageIndex => $rows) {
            $this->pdf->AddPage('P', [self::PAGE_WIDTH, self::PAGE_HEIGHT]);
            $this->drawPage($certificate, $rows, $pageIndex, $totalPages, $rowOffset);
            $rowOffset += count($rows);
        }

        return $this->pdf->Output('', 'S');
    }

    private function loadFonts(): void
    {
        $regular = $this->fontAliasFile('times.ttf', 'timesnewromanpdfnormal.ttf');
        $bold = $this->fontAliasFile('timesbd.ttf', 'timesnewromanpdfbold.ttf');

        if ($regular !== null) {
            $this->fontRegular = TCPDF_FONTS::addTTFfont($regular, 'TrueTypeUnicode', '', 32) ?: 'dejavuserif';
        }

        if ($bold !== null) {
            $this->fontBold = TCPDF_FONTS::addTTFfont($bold, 'TrueTypeUnicode', '', 32) ?: $this->fontRegular;
        }
    }

    private function fontAliasFile(string $sourceName, string $aliasName): ?string
    {
        $source = public_path('fonts/' . $sourceName);

        if (!is_file($source)) {
            return null;
        }

        $directory = storage_path('app/tcpdf-font-source');

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $target = $directory . DIRECTORY_SEPARATOR . $aliasName;

        if (!is_file($target) || filesize($target) !== filesize($source)) {
            copy($source, $target);
        }

        return $target;
    }

    private function paginate(QualityCertificate $certificate): array
    {
        $details = $certificate->details->values();
        $tableStartY = $this->tableStartY($certificate);
        $bodyLimit = $this->noteY() - self::NOTE_SAFE_GAP - $tableStartY - $this->tableHeaderHeight();
        $bodyLimit = max(90.0, $bodyLimit);

        $pages = [];
        $current = [];
        $currentHeight = 0.0;

        foreach ($details as $detail) {
            $rowHeight = $this->rowHeight($detail);

            if ($current !== [] && ($currentHeight + $rowHeight) > $bodyLimit) {
                $pages[] = $current;
                $current = [];
                $currentHeight = 0.0;
            }

            $current[] = $detail;
            $currentHeight += $rowHeight;
        }

        if ($current !== []) {
            $pages[] = $current;
        }

        return $pages ?: [[]];
    }

    private function drawPage(QualityCertificate $certificate, array $rows, int $pageIndex, int $totalPages, int $rowOffset): void
    {
        $this->drawCertificateNo($certificate);
        $tableY = $this->drawInfo($certificate);
        $this->drawProductTable($rows, $rowOffset, $tableY);
        $this->drawFixedFooter($certificate, $pageIndex, $totalPages);
    }

    private function drawCertificateNo(QualityCertificate $certificate): void
    {
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont($this->fontRegular, '', 13);
        $this->pdf->SetXY(self::LEFT, self::CONTENT_TOP + 14);
        $this->pdf->Cell($this->contentWidth(), 16, 'Số ' . $certificate->certificate_no, 0, 1, 'C');
    }

    private function drawInfo(QualityCertificate $certificate): float
    {
        $customer = $certificate->request->customer ?? null;
        $deliveryDate = $certificate->request?->delivery_date
            ? $certificate->request->delivery_date->format('d/m/Y')
            : '';

        $rows = [
            ['1.', 'Tên khách hàng:', $customer->customer_name ?? ''],
            ['2.', 'Tên công trình :', $customer->project_name ?? ''],
        ];

        if ($customer?->project_address) {
            $rows[] = ['', '', 'Địa điểm công trình: ' . $customer->project_address];
        }

        $rows[] = ['3.', 'Ngày xuất hàng:', $deliveryDate];

        $indexW = 20.0;
        $labelW = 94.0;
        $valueW = $this->contentWidth() - $indexW - $labelW;
        $x = self::LEFT;
        $y = self::CONTENT_TOP + 38;

        foreach ($rows as [$index, $label, $value]) {
            $height = $this->infoRowHeight($value, $valueW);

            $this->pdf->SetFont($this->fontRegular, '', 13);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetXY($x, $y);
            $this->pdf->MultiCell($indexW, $height, $index, 0, 'R', false, 0, '', '', true, 0, false, true, $height, 'T');
            $this->pdf->SetXY($x + $indexW, $y);
            $this->pdf->MultiCell($labelW, $height, $label, 0, 'L', false, 0, '', '', true, 0, false, true, $height, 'T');

            $this->pdf->SetTextColor(255, 0, 0);
            $this->pdf->SetXY($x + $indexW + $labelW, $y);
            $this->pdf->MultiCell($valueW, $height, $value, 0, 'L', false, 1, '', '', true, 0, false, true, $height, 'T');

            $y += $height;
        }

        return $y + 14;
    }

    private function drawProductTable(array $rows, int $rowOffset, float $y): void
    {
        $columns = $this->columns();
        $x = self::LEFT;

        $this->pdf->SetFillColor(214, 255, 255);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(17, 17, 17);
        $this->pdf->SetLineWidth(0.6);
        $this->pdf->SetFont($this->fontBold, '', 12);

        foreach ($columns as $column) {
            $this->pdf->SetXY($x, $y);
            $this->pdf->MultiCell($column['w'], $this->tableHeaderHeight(), $column['label'], 1, 'C', true, 0, '', '', true, 0, false, true, $this->tableHeaderHeight(), 'M');
            $x += $column['w'];
        }

        $y += $this->tableHeaderHeight();
        $rowNo = $rowOffset + 1;

        $this->pdf->SetFont($this->fontRegular, '', 12);

        foreach ($rows as $detail) {
            $height = $this->rowHeight($detail);
            $x = self::LEFT;
            $values = [
                (string) $rowNo,
                $this->normalizeText($detail->product->product_name ?? ''),
                $this->normalizeText($detail->product->unit ?? ''),
                $this->formatNumber($detail->quantity),
                $this->normalizeText($detail->nominal_size),
                $this->normalizeText($detail->technical_requirements),
                $this->normalizeText($detail->quality_standard),
            ];

            foreach ($columns as $index => $column) {
                $align = $index === 1 ? 'L' : 'C';
                $this->pdf->SetXY($x, $y);
                $this->pdf->MultiCell($column['w'], $height, $values[$index], 1, $align, false, 0, '', '', true, 0, false, true, $height, 'M');
                $x += $column['w'];
            }

            $y += $height;
            $rowNo++;
        }

        $remaining = $this->noteY() - self::NOTE_SAFE_GAP - $y;
        $blankRows = max(0, min(3, (int) floor($remaining / 18)));

        for ($i = 0; $i < $blankRows; $i++) {
            $x = self::LEFT;
            foreach ($columns as $column) {
                $this->pdf->SetXY($x, $y);
                $this->pdf->Cell($column['w'], 18, '', 1, 0, 'C');
                $x += $column['w'];
            }
            $y += 18;
        }
    }

    private function drawFixedFooter(QualityCertificate $certificate, int $pageIndex, int $totalPages): void
    {
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont($this->fontBold, '', 11);
        $this->pdf->SetXY(self::LEFT, $this->noteY());
        $this->pdf->Write(14, 'Ghi chú: ', '', false, '', false);
        $this->pdf->SetFont($this->fontRegular, '', 11);
        $this->pdf->Write(14, 'Phiếu này thay thế cho phiếu chứng nhận xuất xưởng hàng hóa', '', false, '', true);
        $this->pdf->SetX(self::LEFT);
        $this->pdf->Write(14, 'Sản phẩm đạt yêu cầu theo tiêu chuẩn sản phẩm công ty đã công bố', '', false, '', true);

        $this->pdf->SetFont($this->fontRegular, '', 10);
        $this->pdf->SetXY(self::PAGE_WIDTH - self::RIGHT - 70, $this->noteY());
        $this->pdf->Cell(70, 12, 'Trang ' . ($pageIndex + 1) . '/' . $totalPages, 0, 0, 'R');

        $this->pdf->SetFont($this->fontBold, '', 13);
        $this->pdf->SetXY(self::LEFT + 75, self::CONTENT_TOP + self::SIGNER_TOP);
        $this->pdf->Cell(160, 16, 'Vũ Thị Diệu Thúy', 0, 0, 'L');
    }

    private function tableStartY(QualityCertificate $certificate): float
    {
        return max(214.0, $this->drawInfoDryRunY($certificate));
    }

    private function drawInfoDryRunY(QualityCertificate $certificate): float
    {
        $customer = $certificate->request->customer ?? null;
        $deliveryDate = $certificate->request?->delivery_date
            ? $certificate->request->delivery_date->format('d/m/Y')
            : '';

        $valueW = $this->contentWidth() - 20.0 - 94.0;
        $height = $this->infoRowHeight($customer->customer_name ?? '', $valueW)
            + $this->infoRowHeight($customer->project_name ?? '', $valueW)
            + ($customer?->project_address ? $this->infoRowHeight('Địa điểm công trình: ' . $customer->project_address, $valueW) : 0)
            + $this->infoRowHeight($deliveryDate, $valueW);

        return self::CONTENT_TOP + 38 + $height + 14;
    }

    private function rowHeight($detail): float
    {
        $this->pdf->SetFont($this->fontRegular, '', 12);

        $columns = $this->columns();
        $values = [
            '1',
            $this->normalizeText($detail->product->product_name ?? ''),
            $this->normalizeText($detail->product->unit ?? ''),
            $this->formatNumber($detail->quantity),
            $this->normalizeText($detail->nominal_size),
            $this->normalizeText($detail->technical_requirements),
            $this->normalizeText($detail->quality_standard),
        ];

        $height = 18.0;

        foreach ($columns as $index => $column) {
            $textHeight = $this->pdf->getStringHeight($column['w'] - 6, $values[$index], false, true, '', 1);
            $height = max($height, $textHeight + 5);
        }

        return min(92.0, ceil($height));
    }

    private function infoRowHeight(string $value, float $valueW): float
    {
        $this->pdf->SetFont($this->fontRegular, '', 13);
        $textHeight = $this->pdf->getStringHeight($valueW, $this->normalizeText($value), false, true, '', 1);

        return max(18.0, ceil($textHeight + 3));
    }

    private function columns(): array
    {
        return [
            ['label' => 'TT', 'w' => 27.0],
            ['label' => 'Tên sản phẩm', 'w' => 166.0],
            ['label' => 'ĐVT', 'w' => 32.0],
            ['label' => 'Số lượng', 'w' => 43.0],
            ['label' => "Kích thước\ndanh nghĩa", 'w' => 75.0],
            ['label' => "Yêu cầu kỹ\nthuật", 'w' => 75.0],
            ['label' => 'Tiêu chuẩn sản phẩm', 'w' => $this->contentWidth() - 418.0],
        ];
    }

    private function normalizeText($value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return preg_replace('/([&\/;,:\-])(?=\S)/u', '$1 ', $value) ?? $value;
    }

    private function formatNumber($quantity): string
    {
        if ($quantity === null || $quantity === '') {
            return '';
        }

        return rtrim(rtrim(number_format((float) $quantity, 2, '.', ''), '0'), '.');
    }

    private function contentWidth(): float
    {
        return self::PAGE_WIDTH - self::LEFT - self::RIGHT;
    }

    private function noteY(): float
    {
        return self::CONTENT_TOP + self::NOTE_TOP;
    }

    private function tableHeaderHeight(): float
    {
        return 44.0;
    }
}
