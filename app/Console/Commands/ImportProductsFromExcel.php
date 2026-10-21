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
        $standardCache = DB::table('quality_standards')->where('is_active', true)->whereNull('deleted_at')->pluck('id', 'code')->all();
        $unitCache = DB::table('product_units')->pluck('id', 'name')->all();
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

                $standardId = $this->resolveQualityStandardId(
                    $row['tieu_chuan_san_pham'] ?? null,
                    $standardCache
                );

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

                $preserveStandard = empty($row['tieu_chuan_san_pham']);
                if ($preserveStandard && isset($batch[$productCode])) {
                    $standardId = $batch[$productCode]['quality_standard_id'];
                    $preserveStandard = $batch[$productCode]['_preserve_standard'];
                }
                $unitName = $row['don_vi_tinh'] ?? null;
                $unitId = $this->resolveProductUnitId($unitName, $unitCache);

                $batch[$productCode] = [
                    'product_group_id' => $groupId,
                    'quality_standard_id' => $standardId,
                    '_preserve_standard' => $preserveStandard,
                    'unit_id' => $unitId,
                    'product_code' => $productCode,
                    'product_name' => $productName,
                    'unit' => $unitName,
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

        throw new \InvalidArgumentException('Tiêu chuẩn "' . $code . '" không tồn tại hoặc đã ngừng sử dụng.');
    }

    private function resolveProductUnitId(?string $name, array &$cache): ?int
    {
        $name = $this->nullableString($name);

        if (!$name) {
            return null;
        }

        if (isset($cache[$name])) {
            return (int) $cache[$name];
        }

        $now = now();
        $baseCode = strtoupper(Str::slug($name, '_')) ?: 'DVT';
        $code = substr($baseCode, 0, 40);
        $index = 2;

        while (DB::table('product_units')->where('code', $code)->exists()) {
            $code = substr($baseCode, 0, 36) . '_' . $index;
            $index++;
        }

        DB::table('product_units')->insertOrIgnore([
            'code' => $code,
            'name' => $name,
            'description' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = DB::table('product_units')
            ->where('name', $name)
            ->value('id');

        if ($id) {
            $cache[$name] = (int) $id;
        }

        return $id ? (int) $id : null;
    }

    private function flushProducts(array $products): int
    {
        $existing = DB::table('products')->whereIn('product_code', array_keys($products))
            ->pluck('quality_standard_id', 'product_code')->all();
        $defaults = DB::table('product_groups as groups')
            ->join('quality_standards as standards', 'standards.id', '=', 'groups.default_quality_standard_id')
            ->whereIn('groups.id', array_column($products, 'product_group_id'))
            ->where('standards.is_active', true)->whereNull('standards.deleted_at')
            ->pluck('standards.id', 'groups.id')->all();
        foreach ($products as $code => &$product) {
            if ($product['_preserve_standard']) {
                $product['quality_standard_id'] = array_key_exists($code, $existing)
                    ? $existing[$code] : ($defaults[$product['product_group_id']] ?? null);
            }
            unset($product['_preserve_standard']);
        }
        unset($product);

        DB::table('products')->upsert(
            array_values($products),
            ['product_code'],
            [
                'product_group_id',
                'quality_standard_id',
                'unit_id',
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
