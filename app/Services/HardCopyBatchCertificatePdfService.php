<?php

namespace App\Services;

use App\Models\QualityCertificate;
use TCPDF;
use TCPDF_FONTS;

class HardCopyBatchCertificatePdfService
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;

    // Coordinates are tuned for the new pre-printed hard-copy stock:
    // header and green footer are already printed on paper, so this PDF only
    // draws the middle certificate content.
    private const LEFT = 24.0;
    private const RIGHT = 34.0;
    private const TITLE_TOP = 92.0;
    private const NORMAL_TABLE_BOTTOM = 690.0;
    private const LAST_TABLE_BOTTOM = 505.0;
    private const FOOTER_SAFE_TOP = 705.0;

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
            $isLastPage = $pageIndex === $totalPages - 1;

            $this->pdf->AddPage('P', [self::PAGE_WIDTH, self::PAGE_HEIGHT]);
            $this->drawPage($certificate, $rows, $pageIndex, $totalPages, $rowOffset, $isLastPage);
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
        $normalLimit = $this->pageBodyLimit($certificate, false);
        $lastLimit = $this->pageBodyLimit($certificate, true);

        $pages = [];
        $current = [];
        $currentHeight = 0.0;

        foreach ($details as $detail) {
            $rowHeight = $this->rowHeight($detail);

            if ($current !== [] && ($currentHeight + $rowHeight) > $normalLimit) {
                $pages[] = $current;
                $current = [];
                $currentHeight = 0.0;
            }

            $current[] = $detail;
            $currentHeight += $rowHeight;
        }

        if ($current !== [] || $pages === []) {
            $pages[] = $current;
        }

        $pages = $this->reserveLastPageSpace($pages, $lastLimit);

        return $this->pullRowsToPreviousPages($pages, $normalLimit, $lastLimit) ?: [[]];
    }

    private function reserveLastPageSpace(array $pages, float $lastLimit): array
    {
        while (count($pages) > 0) {
            $lastIndex = count($pages) - 1;
            $lastRows = $pages[$lastIndex];
            $lastHeight = $this->rowsHeight($lastRows);

            if ($lastHeight <= $lastLimit || count($lastRows) <= 1) {
                break;
            }

            $movedRows = [];

            while ($lastHeight > $lastLimit && count($lastRows) > 1) {
                $moved = array_pop($lastRows);
                array_unshift($movedRows, $moved);
                $lastHeight -= $this->rowHeight($moved);
            }

            $pages[$lastIndex] = $lastRows;
            $pages[] = $movedRows;
        }

        return $pages;
    }

    private function pullRowsToPreviousPages(array $pages, float $normalLimit, float $lastLimit): array
    {
        for ($index = count($pages) - 1; $index > 0; $index--) {
            $currentRows = $pages[$index];
            $previousRows = $pages[$index - 1];
            $previousLimit = $index - 1 === count($pages) - 1 ? $lastLimit : $normalLimit;
            $previousHeight = $this->rowsHeight($previousRows);

            while (count($currentRows) > 1) {
                $candidate = $currentRows[0];
                $candidateHeight = $this->rowHeight($candidate);

                if ($previousHeight + $candidateHeight > $previousLimit) {
                    break;
                }

                $previousRows[] = array_shift($currentRows);
                $previousHeight += $candidateHeight;
            }

            $pages[$index - 1] = $previousRows;
            $pages[$index] = $currentRows;
        }

        return array_values(array_filter($pages, static fn (array $rows): bool => $rows !== []));
    }

    private function drawPage(QualityCertificate $certificate, array $rows, int $pageIndex, int $totalPages, int $rowOffset, bool $isLastPage): void
    {
        $tableY = $this->drawHeaderContent($certificate);
        $tableBottom = $this->drawProductTable($rows, $rowOffset, $tableY);

        if ($isLastPage) {
            $this->drawLastPageNote($tableBottom + 7);
        } else {
            $this->drawContinueText();
        }

        $this->drawPageNumber($pageIndex, $totalPages);
    }

    private function drawHeaderContent(QualityCertificate $certificate): float
    {
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->SetFont($this->fontBold, '', 16);
        $this->pdf->SetXY(self::LEFT, self::TITLE_TOP);
        $this->pdf->Cell($this->contentWidth(), 18, 'PHIẾU CHỨNG NHẬN CHẤT LƯỢNG', 0, 1, 'C');

        $this->pdf->SetFont($this->fontRegular, '', 13);
        $this->pdf->SetXY(self::LEFT, self::TITLE_TOP + 22);
        $this->pdf->Cell($this->contentWidth(), 16, 'Số ' . $certificate->certificate_no, 0, 1, 'C');

        return $this->drawInfo($certificate);
    }

    private function drawInfo(QualityCertificate $certificate): float
    {
        $customer = $certificate->request->customer ?? null;
        $deliveryDate = $certificate->request?->delivery_date
            ? $certificate->request->delivery_date->format('d/m/Y')
            : '';

        $rows = [
            ['1.', 'Tên khách hàng:', $customer->customer_name ?? '', true],
            ['2.', 'Tên công trình:', $customer->project_name ?? '', true],
        ];

        if ($customer?->project_address) {
            $rows[] = ['', '', 'Địa điểm công trình: ' . $customer->project_address, true];
        }

        $rows[] = ['3.', 'Ngày xuất hàng:', $deliveryDate, true];

        $indexW = 20.0;
        $labelW = 94.0;
        $valueW = $this->contentWidth() - $indexW - $labelW;
        $x = self::LEFT;
        $y = self::TITLE_TOP + 46;

        foreach ($rows as [$index, $label, $value, $bold]) {
            $height = $this->infoRowHeight($value, $valueW);

            $this->pdf->SetFont($this->fontRegular, '', 13);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetXY($x, $y);
            $this->pdf->MultiCell($indexW, $height, $index, 0, 'R', false, 0, '', '', true, 0, false, true, $height, 'T');
            $this->pdf->SetXY($x + $indexW, $y);
            $this->pdf->MultiCell($labelW, $height, $label, 0, 'L', false, 0, '', '', true, 0, false, true, $height, 'T');

            $this->pdf->SetFont($bold ? $this->fontBold : $this->fontRegular, '', 13);
            $this->pdf->SetXY($x + $indexW + $labelW, $y);
            $this->pdf->MultiCell($valueW, $height, $this->normalizeText($value), 0, 'L', false, 1, '', '', true, 0, false, true, $height, 'T');

            $y += $height;
        }

        return $y + 12;
    }

    private function drawProductTable(array $rows, int $rowOffset, float $y): float
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

        return $y;
    }

    private function drawLastPageNote(float $y): void
    {
        $y = min($y, self::LAST_TABLE_BOTTOM + 12);

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont($this->fontBold, '', 12);
        $this->pdf->SetXY(self::LEFT, $y);
        $this->pdf->Write(15, 'Ghi chú: ', '', false, '', false);
        $this->pdf->SetFont($this->fontRegular, '', 12);
        $this->pdf->Write(15, 'Phiếu này thay thế cho phiếu chứng nhận xuất xưởng hàng hóa', '', false, '', true);
        $this->pdf->SetX(self::LEFT);
        $this->pdf->Write(15, 'Sản phẩm đạt yêu cầu theo tiêu chuẩn sản phẩm công ty đã công bố', '', false, '', true);
    }

    private function drawContinueText(): void
    {
        $this->pdf->SetTextColor(90, 90, 90);
        $this->pdf->SetFont($this->fontRegular, 'I', 9);
        $this->pdf->SetXY(self::PAGE_WIDTH - self::RIGHT - 105, self::FOOTER_SAFE_TOP - 22);
        $this->pdf->Cell(105, 12, 'Còn tiếp trang sau', 0, 0, 'R');
    }

    private function drawPageNumber(int $pageIndex, int $totalPages): void
    {
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont($this->fontRegular, '', 10);
        $this->pdf->SetXY(self::PAGE_WIDTH - self::RIGHT - 70, self::FOOTER_SAFE_TOP - 8);
        $this->pdf->Cell(70, 12, ($pageIndex + 1) . '/' . $totalPages, 0, 0, 'R');
    }

    private function pageBodyLimit(QualityCertificate $certificate, bool $lastPage): float
    {
        $tableStartY = $this->tableStartY($certificate);
        $bottom = $lastPage ? self::LAST_TABLE_BOTTOM : self::NORMAL_TABLE_BOTTOM;

        return max(90.0, $bottom - $tableStartY - $this->tableHeaderHeight());
    }

    private function tableStartY(QualityCertificate $certificate): float
    {
        return max(210.0, $this->drawInfoDryRunY($certificate));
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

        return self::TITLE_TOP + 46 + $height + 12;
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

    private function rowsHeight(array $rows): float
    {
        return array_sum(array_map(fn ($row): float => $this->rowHeight($row), $rows));
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
            ['label' => 'Tên sản phẩm', 'w' => 174.0],
            ['label' => 'ĐVT', 'w' => 34.0],
            ['label' => 'Số lượng', 'w' => 43.0],
            ['label' => "Kích thước\ndanh nghĩa", 'w' => 75.0],
            ['label' => "Yêu cầu\nkỹ thuật", 'w' => 65.0],
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

    private function tableHeaderHeight(): float
    {
        return 44.0;
    }
}
