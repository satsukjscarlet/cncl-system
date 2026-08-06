<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportProductsFromExcel extends Command
{
    protected $signature = 'products:import-file
        {file : Duong dan file Excel can import}
        {--memory=1024M : Gioi han bo nho PHP khi import}
        {--timeout=0 : Gioi han thoi gian chay, 0 la khong gioi han}
        {--no-progress : Khong hien thanh tien trinh}';

    protected $description = 'Import danh muc san pham tu file Excel bang CLI de tranh timeout web';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!is_file($file)) {
            $this->error('Khong tim thay file: ' . $file);

            return self::FAILURE;
        }

        @ini_set('memory_limit', (string) $this->option('memory'));
        @set_time_limit((int) $this->option('timeout'));

        $this->info('Dang import san pham tu: ' . $file);

        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $headerRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, true)[1] ?? [];
        $headers = [];

        foreach ($headerRow as $column => $heading) {
            $heading = trim((string) $heading);

            if ($heading !== '') {
                $headers[$column] = $heading;
            }
        }

        $requiredHeaders = ['ma_nhom_san_pham', 'ten_nhom_san_pham', 'ma_san_pham', 'ten_san_pham'];
        $missingHeaders = array_diff($requiredHeaders, array_values($headers));

        if ($missingHeaders) {
            $this->error('File thieu cot bat buoc: ' . implode(', ', $missingHeaders));

            return self::FAILURE;
        }

        $groupCache = DB::table('product_groups')->pluck('id', 'code')->all();
        $groupNameCache = DB::table('product_groups')->pluck('id', 'name')->all();
        $standardCache = DB::table('quality_standards')->pluck('id', 'code')->all();
        $batch = [];
        $batchSize = 1000;
        $now = now();
        $createdOrUpdated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        $bar = null;

        if (!$this->option('no-progress')) {
            $bar = $this->output->createProgressBar(max(0, $highestRow - 1));
            $bar->start();
        }

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            try {
                $rawRow = $sheet->rangeToArray('A' . $rowNumber . ':' . $highestColumn . $rowNumber, null, true, true, true)[$rowNumber] ?? [];
                $row = [];

                foreach ($headers as $column => $heading) {
                    $row[$heading] = $this->nullableString($rawRow[$column] ?? null);
                }

                if ($this->looksLikeShiftedProductTemplate($row)) {
                    $row = $this->normalizeShiftedProductRow($rawRow);
                }

                $productCode = $row['ma_san_pham'] ?? null;
                $productName = $row['ten_san_pham'] ?? null;

                if (!$productCode || !$productName) {
                    $skipped++;
                    $bar?->advance();

                    continue;
                }

                $groupId = $this->resolveProductGroupId(
                    $row['ma_nhom_san_pham'] ?? null,
                    $row['ten_nhom_san_pham'] ?? null,
                    $groupCache,
                    $groupNameCache
                );

                if (!$groupId) {
                    $skipped++;
                    $bar?->advance();

                    continue;
                }

                $standardId = $this->resolveQualityStandardId(
                    $row['tieu_chuan_san_pham'] ?? null,
                    $standardCache
                );

                $batch[$productCode] = [
                    'product_group_id' => $groupId,
                    'quality_standard_id' => $standardId,
                    'product_code' => $productCode,
                    'product_name' => $productName,
                    'unit' => $row['don_vi_tinh'] ?? null,
                    'nominal_size' => $row['kich_thuoc_danh_nghia'] ?? null,
                    'technical_requirements' => $row['yeu_cau_ky_thuat'] ?? null,
                    'certificate_type' => $row['loai_phieu'] ?? 'CNCL',
                    'certificate_template' => $row['mau_phieu'] ?? null,
                    'note' => $row['ghi_chu'] ?? null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    $createdOrUpdated += $this->flushProducts($batch);
                    $batch = [];
                }
            } catch (\Throwable $e) {
                $failed++;

                if (count($errors) < 20) {
                    $errors[] = 'Dong ' . $rowNumber . ': ' . $e->getMessage();
                }
            }

            $bar?->advance();
        }

        if ($batch) {
            $createdOrUpdated += $this->flushProducts($batch);
        }

        $bar?->finish();
        $spreadsheet->disconnectWorksheets();

        $this->newLine();
        $this->info('Import xong.');
        $this->line('Tao moi/cap nhat: ' . $createdOrUpdated);
        $this->line('Bo qua: ' . $skipped);
        $this->line('Loi: ' . $failed);

        if ($errors) {
            $this->warn('Mot so loi dau tien:');

            foreach ($errors as $error) {
                $this->line('- ' . $error);
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveProductGroupId(?string $code, ?string $name, array &$codeCache, array &$nameCache): ?int
    {
        if ($code && isset($codeCache[$code])) {
            return (int) $codeCache[$code];
        }

        if ($name && isset($nameCache[$name])) {
            return (int) $nameCache[$name];
        }

        if (!$code && !$name) {
            return null;
        }

        $now = now();
        $code = $code ?: strtoupper(Str::slug($name, '-'));

        DB::table('product_groups')->insertOrIgnore([
            'code' => $code,
            'name' => $name ?: $code,
            'description' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = DB::table('product_groups')
            ->where('code', $code)
            ->value('id');

        if (!$id && $name) {
            $id = DB::table('product_groups')
                ->where('name', $name)
                ->value('id');
        }

        if ($id) {
            $codeCache[$code] = (int) $id;

            if ($name) {
                $nameCache[$name] = (int) $id;
            }
        }

        return $id ? (int) $id : null;
    }

    private function resolveQualityStandardId(?string $code, array &$cache): ?int
    {
        if (!$code) {
            return null;
        }

        if (isset($cache[$code])) {
            return (int) $cache[$code];
        }

        $now = now();

        DB::table('quality_standards')->insertOrIgnore([
            'code' => $code,
            'name' => $code,
            'description' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = DB::table('quality_standards')
            ->where('code', $code)
            ->value('id');

        if ($id) {
            $cache[$code] = (int) $id;
        }

        return $id ? (int) $id : null;
    }

    private function flushProducts(array $products): int
    {
        DB::table('products')->upsert(
            array_values($products),
            ['product_code'],
            [
                'product_group_id',
                'quality_standard_id',
                'product_name',
                'unit',
                'nominal_size',
                'technical_requirements',
                'certificate_type',
                'certificate_template',
                'note',
                'is_active',
                'updated_at',
            ]
        );

        return count($products);
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function looksLikeShiftedProductTemplate(array $row): bool
    {
        $candidateCode = $row['ma_nhom_san_pham'] ?? null;
        $candidateProductCode = $row['ma_san_pham'] ?? null;

        if (!$candidateCode || !$candidateProductCode) {
            return false;
        }

        return preg_match('/^[A-Za-z]{1,5}[A-Za-z0-9.\\-_\\/]*\\d/', $candidateCode) === 1
            && in_array(mb_strtolower($candidateProductCode), ['m', 'met', 'mét', 'cuộn', 'đoạn', 'cây', 'cái', 'bộ', 'kg'], true);
    }

    private function normalizeShiftedProductRow(array $rawRow): array
    {
        return [
            'ma_nhom_san_pham' => null,
            'ten_nhom_san_pham' => $this->nullableString($rawRow['D'] ?? null)
                ?: $this->nullableString($rawRow['E'] ?? null),
            'ma_san_pham' => $this->nullableString($rawRow['A'] ?? null),
            'ten_san_pham' => $this->nullableString($rawRow['B'] ?? null),
            'don_vi_tinh' => $this->nullableString($rawRow['C'] ?? null),
            'kich_thuoc_danh_nghia' => $this->nullableString($rawRow['F'] ?? null),
            'yeu_cau_ky_thuat' => $this->nullableString($rawRow['G'] ?? null),
            'tieu_chuan_san_pham' => $this->nullableString($rawRow['H'] ?? null),
            'mau_phieu' => $this->nullableString($rawRow['I'] ?? null),
            'ghi_chu' => $this->nullableString($rawRow['J'] ?? null),
        ];
    }
}
