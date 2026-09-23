<?php

namespace App\Services;

use App\Models\QualityCertificate;
use TCPDF;
use TCPDF_FONTS;

class SignedCertificatePdfService
{
    private const PAGE_WIDTH = 595.28;
    private const PAGE_HEIGHT = 841.89;

    // ===== Layout tuning notes =====
    // All numbers below are PDF points, not CSS pixels. A4 portrait is about
    // 595 x 842 pt. Increase Y to move an element down, decrease Y to move it up.
    // Increase X to move right, decrease X to move left.
    private const LEFT = 22.0;
    private const RIGHT = 22.0;
    private const TOP = 16.0;

    // Header: company logo and ISO image.
    private const COMPANY_LOGO_X = self::LEFT;
    private const COMPANY_LOGO_Y = self::TOP + 3;
    private const COMPANY_LOGO_W = 88.0;
    private const ISO_IMAGE_X = 486.0;
    private const ISO_IMAGE_Y = self::TOP + 2;
    private const ISO_IMAGE_W = 88.0;
    private const ISO_IMAGE_H = 58.0;

    // Chân trang: dải màu xanh lá. Tổng FOOTER_BAND_Y + FOOTER_BAND_H cần gần với PAGE_HEIGHT.
    // Nếu dải màu xanh lá chưa đủ gần mép dưới trang, hãy tăng giá trị FOOTER_BAND_Y.
    private const FOOTER_BAND_H = 72.0;
    private const FOOTER_BAND_Y = self::PAGE_HEIGHT - self::FOOTER_BAND_H;
    private const FOOTER_WEBSITE_Y = self::FOOTER_BAND_Y - 22.0;

    // Các vùng an toàn cho bảng/chữ ký. Chỉ di chuyển các phần này xuống dưới nếu phần chân trang vẫn còn đủ chỗ.
    // Nội dung các trang thông thường phải kết thúc trước dòng ghi chú "tiếp theo ở trang sau" hoặc số trang.
    // Nếu đặt quá thấp, dòng chữ "tiếp theo ở trang sau" và số trang có thể bị chồng lên bảng.
    private const TABLE_BOTTOM_NORMAL = 715.0;
    private const TABLE_BOTTOM_UNSIGNED_LAST = 650.0;
    private const TABLE_BOTTOM_SIGNED_LAST = 560.0;
    private const SIGNATURE_Y = 676.0;
    private const CONTINUED_NOTE_Y = 725.0;
    private const PAGE_NUMBER_Y = 742.0;

    private TCPDF $pdf;
    private string $fontRegular = 'dejavuserif';
    private string $fontBold = 'dejavuserif';

    public function render(QualityCertificate $certificate, ?bool $showSignature = null, bool $reserveSignatureSpace = false): string
    {
        $certificate->loadMissing([
            'request.distributionCenter',
            'request.customer',
            'details.product',
            'creator',
        ]);

        $showSignature ??= (bool) $certificate->signed_at;

        $this->pdf = new TCPDF('P', 'pt', 'A4', true, 'UTF-8', false);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins(0, 0, 0);
        $this->pdf->SetAutoPageBreak(false, 0);
        $this->pdf->setFontSubsetting(true);
        $this->loadFonts();

        $reserveLastPageForSignature = $showSignature || $reserveSignatureSpace;
        $pages = $this->paginate($certificate, $reserveLastPageForSignature);
        $totalPages = max(1, count($pages));
        $rowOffset = 0;

        foreach ($pages as $pageIndex => $rows) {
            $isLastPage = $pageIndex === $totalPages - 1;

            $this->pdf->AddPage('P', [self::PAGE_WIDTH, self::PAGE_HEIGHT]);
            $tableY = $this->drawHeaderAndInfo($certificate);
            $tableEndY = $this->drawProductTable($rows, $rowOffset, $tableY);

            if ($isLastPage) {
                $this->drawNote($tableEndY, $reserveLastPageForSignature);

                if ($showSignature) {
                    $this->drawDigitalSignature($certificate);
                }
            } else {
                $this->drawContinuedNote();
            }

            $this->drawPageNumber($pageIndex + 1, $totalPages);
            $this->drawFooter();

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

    private function paginate(QualityCertificate $certificate, bool $showSignature): array
    {
        $details = $certificate->details->values();
        $tableY = $this->tableStartY($certificate);
        $normalBodyLimit = self::TABLE_BOTTOM_NORMAL - $tableY - $this->tableHeaderHeight();
        $lastBodyLimit = ($showSignature ? self::TABLE_BOTTOM_SIGNED_LAST : self::TABLE_BOTTOM_UNSIGNED_LAST) - $tableY - $this->tableHeaderHeight();
        $normalBodyLimit = max(80.0, $normalBodyLimit);
        $lastBodyLimit = max(60.0, $lastBodyLimit);

        $pages = [];
        $current = [];
        $currentHeight = 0.0;

        foreach ($details as $detail) {
            $rowHeight = $this->rowHeight($detail);

            if ($current !== [] && ($currentHeight + $rowHeight) > $normalBodyLimit) {
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

        if ($pages === []) {
            return [[]];
        }

        while (count($pages) > 0) {
            $lastRows = $pages[count($pages) - 1];
            $lastHeight = array_sum(array_map(fn($detail) => $this->rowHeight($detail), $lastRows));

            if ($lastHeight <= $lastBodyLimit || count($lastRows) <= 1) {
                break;
            }

            $movedRows = [];

            while ($lastHeight > $lastBodyLimit && count($lastRows) > 1) {
                $moved = array_pop($lastRows);
                array_unshift($movedRows, $moved);
                $lastHeight -= $this->rowHeight($moved);
            }

            $pages[count($pages) - 1] = $lastRows;
            $pages[] = $movedRows;
        }

        $pages = $this->avoidSingleRowPages($pages, $normalBodyLimit, $lastBodyLimit);

        return $this->balanceSparseLastPage($pages, $normalBodyLimit);
    }

    private function avoidSingleRowPages(array $pages, float $normalBodyLimit, float $lastBodyLimit): array
    {
        for ($i = 1; $i < count($pages); $i++) {
            if (count($pages[$i]) !== 1 || count($pages[$i - 1]) <= 1) {
                continue;
            }

            $currentHeight = array_sum(array_map(fn($detail) => $this->rowHeight($detail), $pages[$i]));
            $targetLimit = $i === count($pages) - 1 ? $lastBodyLimit : $normalBodyLimit;
            $candidate = end($pages[$i - 1]);

            if ($candidate && $currentHeight + $this->rowHeight($candidate) <= $targetLimit) {
                array_pop($pages[$i - 1]);
                array_unshift($pages[$i], $candidate);
            }
        }

        return $pages;
    }

    private function balanceSparseLastPage(array $pages, float $normalBodyLimit): array
    {
        if (count($pages) < 2) {
            return $pages;
        }

        $lastIndex = count($pages) - 1;
        $previousIndex = $lastIndex - 1;

        $lastPageOriginalCount = count($pages[$lastIndex]);

        if ($lastPageOriginalCount < 2) {
            return $pages;
        }

        while (count($pages[$lastIndex]) > 1) {
            $candidate = $pages[$lastIndex][0] ?? null;

            if (!$candidate) {
                break;
            }

            $previousHeight = array_sum(array_map(fn($detail) => $this->rowHeight($detail), $pages[$previousIndex]));
            $candidateHeight = $this->rowHeight($candidate);

            if ($previousHeight + $candidateHeight > $normalBodyLimit) {
                break;
            }

            $pages[$previousIndex][] = array_shift($pages[$lastIndex]);
        }

        return $pages;
    }

    private function drawHeaderAndInfo(QualityCertificate $certificate): float
    {
        $this->drawHeader($certificate);
        $this->drawTitle($certificate);

        return $this->drawInfo($certificate);
    }

    private function drawHeader(QualityCertificate $certificate): void
    {
        $logo = public_path('images/logo.png');

        if (is_file($logo)) {
            $this->pdf->Image($logo, self::COMPANY_LOGO_X, self::COMPANY_LOGO_Y, self::COMPANY_LOGO_W, 0, '', '', '', false, 300);
        }

        $this->pdf->SetTextColor(215, 25, 32);
        $this->pdf->SetFont($this->fontBold, '', 14);
        $this->pdf->SetXY(100, self::TOP + 8);
        $this->pdf->Cell(388, 13, $this->t('CÔNG TY CỔ PHẦN NHỰA THIẾU NIÊN TIỀN PHONG'), 0, 1, 'C');

        $this->pdf->SetFont($this->fontBold, '', 14.0);
        $this->pdf->SetX(100);
        $this->pdf->Cell(388, 13, 'TIEN PHONG PLASTIC JOINT STOCK COMPANY', 0, 1, 'C');

        $this->drawIsoImage();

        $this->pdf->SetTextColor(215, 25, 32);
        $this->pdf->SetFont($this->fontBold, '', 12);
        $this->pdf->SetXY(486, self::TOP + 65);
        $this->pdf->Cell(34, 11, 'PCN:', 0, 0, 'R');

        $this->pdf->SetFont($this->fontBold, '', 12);
        $this->pdf->SetXY(523, self::TOP + 64.8);
        $this->pdf->Cell(51, 13, str_pad((string) $certificate->id, 7, '0', STR_PAD_LEFT), 0, 0, 'L');
    }

    private function drawIsoImage(): void
    {
        // Replace this PNG later if you have the exact official image:
        // public/images/quacert-jas-anz-iso.png
        $png = public_path('images/quacert-jas-anz-iso.png');
        $svg = public_path('images/quacert-jas-anz-iso.svg');

        if (is_file($png)) {
            $this->pdf->Image($png, self::ISO_IMAGE_X, self::ISO_IMAGE_Y, self::ISO_IMAGE_W, self::ISO_IMAGE_H, '', '', '', false, 300);
            $this->drawTienPhongIsoYear();
            return;
        }

        if (is_file($svg)) {
            $this->pdf->ImageSVG($svg, self::ISO_IMAGE_X, self::ISO_IMAGE_Y, self::ISO_IMAGE_W, self::ISO_IMAGE_H);
            $this->drawTienPhongIsoYear();
            return;
        }

        $this->pdf->SetTextColor(0, 105, 180);
        $this->pdf->SetFont($this->fontBold, '', 7.4);
        $this->pdf->SetXY(self::ISO_IMAGE_X, self::ISO_IMAGE_Y);
        $this->pdf->MultiCell(self::ISO_IMAGE_W, 8.7, "QUACERT\nJAS-ANZ\nISO 9001:2015", 0, 'C');
        $this->drawTienPhongIsoYear();
    }

    private function drawTienPhongIsoYear(): void
    {
        // Move this line up/down with ISO_IMAGE_H or this Y offset.
        $this->pdf->SetTextColor(0, 105, 180);
        $this->pdf->SetFont($this->fontBold, '', 8.4);
        $this->pdf->SetXY(self::ISO_IMAGE_X, self::ISO_IMAGE_Y + self::ISO_IMAGE_H - 6);
        $this->pdf->Cell(self::ISO_IMAGE_W, 8.5, 'TIENPHONG : ' . now()->year, 0, 0, 'C');
    }

    private function drawTitle(QualityCertificate $certificate): void
    {
        $this->pdf->SetTextColor(17, 17, 17);
        $this->pdf->SetFont($this->fontBold, '', 18);
        $this->pdf->SetXY(self::LEFT, 96);
        $this->pdf->Cell($this->contentWidth(), 24, $this->t('PHIẾU CHỨNG NHẬN CHẤT LƯỢNG'), 0, 1, 'C');

        $this->pdf->SetFont($this->fontRegular, '', 13);
        $this->pdf->SetX(self::LEFT);
        $this->pdf->Cell($this->contentWidth(), 18, $this->t('Số ') . $certificate->certificate_no, 0, 1, 'C');
    }

    private function drawInfo(QualityCertificate $certificate): float
    {
        $customer = $certificate->request->customer ?? null;
        $deliveryDate = $certificate->request?->delivery_date
            ? $certificate->request->delivery_date->format('d/m/Y')
            : '';

        $rows = [
            ['1.', $this->t('Tên khách hàng:'), $customer->customer_name ?? ''],
            ['2.', $this->t('Tên công trình:'), $customer->project_name ?? ''],
        ];

        if ($customer?->project_address) {
            $rows[] = ['', '', $this->t('Địa điểm công trình: ') . $customer->project_address];
        }

        $rows[] = ['3.', $this->t('Ngày xuất hàng:'), $deliveryDate];

        $indexW = 18.0;
        $labelW = 112.0;
        $valueW = $this->contentWidth() - $indexW - $labelW;
        $x = self::LEFT;
        $y = 145.0;

        foreach ($rows as [$index, $label, $value]) {
            $height = $this->infoRowHeight((string) $value, $valueW);

            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFont($this->fontRegular, '', 13);
            $this->pdf->SetXY($x, $y);
            $this->pdf->MultiCell($indexW, $height, $index, 0, 'R', false, 0, '', '', true, 0, false, true, $height, 'T');
            $this->pdf->SetXY($x + $indexW, $y);
            $this->pdf->MultiCell($labelW, $height, $label, 0, 'L', false, 0, '', '', true, 0, false, true, $height, 'T');

            $this->pdf->SetFont($this->fontBold, '', 13);
            $this->pdf->SetXY($x + $indexW + $labelW, $y);
            $this->pdf->MultiCell($valueW, $height, $this->normalizeText($value), 0, 'L', false, 1, '', '', true, 0, false, true, $height, 'T');

            $y += $height;
        }

        return $y + 10;
    }

    private function drawProductTable(array $rows, int $rowOffset, float $y): float
    {
        $columns = $this->columns();
        $x = self::LEFT;

        $this->pdf->SetFillColor(217, 248, 247);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(34, 34, 34);
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
                $this->pdf->SetXY($x, $y);
                $this->pdf->MultiCell($column['w'], $height, $values[$index], 1, $index === 1 ? 'L' : 'C', false, 0, '', '', true, 0, false, true, $height, 'M');
                $x += $column['w'];
            }

            $y += $height;
            $rowNo++;
        }

        return $y;
    }

    private function drawNote(float $tableEndY, bool $reserveSignatureSpace): void
    {
        $y = $tableEndY + 8;
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont($this->fontBold, '', 12);
        $this->pdf->SetXY(self::LEFT, $y);
        $this->pdf->Write(14, $this->t('Ghi chú: '), '', false, '', false);
        $this->pdf->SetFont($this->fontRegular, '', 12);
        $this->pdf->Write(14, $this->t('Phiếu này thay thế cho phiếu chứng nhận xuất xưởng hàng hóa'), '', false, '', true);
        $this->pdf->SetX(self::LEFT + 0);
        $this->pdf->Write(14, $this->t('Sản phẩm đạt yêu cầu theo tiêu chuẩn sản phẩm công ty đã công bố'), '', false, '', true);
    }

    private function drawDigitalSignature(QualityCertificate $certificate): void
    {
        $x = 255.0;
        $y = self::SIGNATURE_Y;
        $w = 310.0;
        $h = 58.0;

        $this->pdf->SetDrawColor(216, 74, 74);
        $this->pdf->SetFillColor(255, 253, 253);
        $this->pdf->Rect($x, $y, $w, $h, 'DF');
        $this->pdf->SetTextColor(184, 34, 34);
        $this->pdf->SetFont($this->fontBold, '', 9.5);
        $this->pdf->SetXY($x + 8, $y + 6);
        $this->pdf->MultiCell($w - 16, 12, $this->t('PHIẾU ĐƯỢC KÝ ĐIỆN TỬ'), 0, 'C');

        $this->pdf->SetFont($this->fontRegular, '', 8.5);
        $signedBy = $certificate->signed_by ?: 'VNPT SmartCA';
        $signedAt = $certificate->signed_at ? $certificate->signed_at->format('d/m/Y H:i') : '';
        $text = $this->t('Ký bởi: ') . $signedBy
            . "\n" . $this->t('Số phiếu: ') . $certificate->certificate_no
            . "\n" . $this->t('Thời gian ký: ') . $signedAt;

        if ($certificate->smartca_certificate_serial) {
            $text .= "\nSerial CTS: " . $certificate->smartca_certificate_serial;
        }

        $this->pdf->SetXY($x + 8, $y + 20);
        $this->pdf->MultiCell($w - 16, 9, $text, 0, 'C');
    }

    private function drawContinuedNote(): void
    {
        $this->pdf->SetTextColor(90, 90, 90);
        $this->pdf->SetFont($this->fontRegular, '', 10);
        $this->pdf->SetXY(self::LEFT, self::CONTINUED_NOTE_Y);
        $this->pdf->Cell($this->contentWidth(), 12, $this->t('Còn tiếp trang sau'), 0, 0, 'R');
    }

    private function drawPageNumber(int $page, int $totalPages): void
    {
        $this->pdf->SetTextColor(80, 80, 80);
        $this->pdf->SetFont($this->fontRegular, '', 10);
        $this->pdf->SetXY(self::PAGE_WIDTH - self::RIGHT - 70, self::PAGE_NUMBER_Y);
        $this->pdf->Cell(70, 12, $page . '/' . $totalPages, 0, 0, 'R');
    }

    private function drawFooter(): void
    {
        $this->pdf->SetTextColor(106, 168, 66);
        $this->pdf->SetFont($this->fontRegular, '', 10.5);
        $this->pdf->SetXY(74, self::FOOTER_WEBSITE_Y + 3);
        $this->pdf->Cell(250, 14, 'Website: www.nhuatienphong.vn', 0, 0, 'L');

        $this->pdf->SetFillColor(119, 191, 53);
        $this->pdf->Rect(0, self::FOOTER_BAND_Y, self::PAGE_WIDTH, self::FOOTER_BAND_H, 'F');

        $this->pdf->SetTextColor(255, 255, 255);
        $this->drawFooterColumn(42, self::FOOTER_BAND_Y + 7, [
            ['- ' . $this->t('Trụ sở chính:'), true],
            [$this->t('Số 2 An Đà, Gia Viên, TP. Hải Phòng, Việt Nam'), false],
            ['- ' . $this->t('Văn phòng giao dịch & Nhà máy:'), true],
            [$this->t('Số 222 Mạc Đăng Doanh, P. Hưng Đạo, TP. Hải Phòng, Việt Nam'), false],
            [$this->t('ĐT: (0225) 3813979 * Fax: (0225) 3813989'), false],
        ]);

        $this->drawFooterColumn(320, self::FOOTER_BAND_Y + 7, [
            ['- Head office:', true],
            ['No 2 An Da St., Gia Vien Ward, Hai Phong City, Viet Nam', false],
            ['- Liaison office & Factory:', true],
            ['No 222 Mac Dang Doanh St., Hung Dao Ward, Hai Phong City, Viet Nam', false],
            ['Tel: (0225) 3813979 * Fax: (0225) 3813989', false],
        ]);
    }

    private function drawFooterColumn(float $x, float $y, array $lines): void
    {
        foreach ($lines as [$text, $bold]) {
            $this->pdf->SetFont($bold ? $this->fontBold : $this->fontRegular, '', $bold ? 8.3 : 7.9);
            $this->pdf->SetXY($x, $y);
            $this->pdf->Cell(252, 9.5, $text, 0, 0, 'L');
            $y += 9.5;
        }
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

        $valueW = $this->contentWidth() - 18.0 - 112.0;

        $height = $this->infoRowHeight($customer->customer_name ?? '', $valueW)
            + $this->infoRowHeight($customer->project_name ?? '', $valueW)
            + ($customer?->project_address ? $this->infoRowHeight($this->t('Địa điểm công trình: ') . $customer->project_address, $valueW) : 0)
            + $this->infoRowHeight($deliveryDate, $valueW);

        return 145.0 + $height + 10;
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

        $height = 20.0;

        foreach ($columns as $index => $column) {
            $textHeight = $this->pdf->getStringHeight($column['w'] - 6, $values[$index], false, true, '', 1);
            $height = max($height, $textHeight + 7);
        }

        return min(96.0, ceil($height));
    }

    private function infoRowHeight(string $value, float $valueW): float
    {
        $this->pdf->SetFont($this->fontRegular, '', 13);
        $textHeight = $this->pdf->getStringHeight($valueW, $this->normalizeText($value), false, true, '', 1);

        return max(18.0, ceil($textHeight + 4));
    }

    private function columns(): array
    {
        return [
            ['label' => 'TT', 'w' => 27.0],
            ['label' => $this->t('Tên sản phẩm'), 'w' => 176.0],
            ['label' => $this->t('ĐVT'), 'w' => 32.0],
            ['label' => $this->t('Số lượng'), 'w' => 48.0],
            ['label' => $this->t("Kích thước\ndanh nghĩa"), 'w' => 78.0],
            ['label' => $this->t("Yêu cầu \nkỹ thuật"), 'w' => 78.0],
            ['label' => $this->t("Tiêu chuẩn \nsản phẩm"), 'w' => $this->contentWidth() - 439.0],
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
        return 42.0;
    }

    private function t(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
}
