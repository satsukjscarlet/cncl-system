<?php

namespace Database\Seeders;

use App\Models\ProductGroup;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductGroupSeeder extends Seeder
{
    public function run(): void
    {
        $file = $this->templatePath();

        if (!is_file($file)) {
            $this->command?->warn('Khong tim thay file template_nhom_san_pham.xlsx de seed nhom san pham.');

            return;
        }

        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $sequence = 1;

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $row = $sheet->rangeToArray(
                'A' . $rowNumber . ':' . $highestColumn . $rowNumber,
                null,
                true,
                true,
                true
            )[$rowNumber] ?? [];

            [$name, $standard] = $this->resolveRow($row);

            if (!$name) {
                continue;
            }

            ProductGroup::updateOrCreate(
                ['code' => $this->makeCode($sequence)],
                [
                    'name' => $name,
                    'description' => $standard,
                    'is_active' => true,
                ]
            );

            $sequence++;
        }

        $spreadsheet->disconnectWorksheets();
    }

    private function templatePath(): string
    {
        $rootTemplate = base_path('template_nhom_san_pham.xlsx');

        if (is_file($rootTemplate)) {
            return $rootTemplate;
        }

        return storage_path('app/templates/template_nhom_san_pham.xlsx');
    }

    private function resolveRow(array $row): array
    {
        $first = $this->nullableString($row['A'] ?? null);
        $second = $this->nullableString($row['B'] ?? null);
        $third = $this->nullableString($row['C'] ?? null);

        if ($third) {
            return [$second, $third];
        }

        return [$first, $second];
    }

    private function makeCode(int $sequence): string
    {
        return 'NTP-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
