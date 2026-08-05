<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToModel, WithHeadingRow
{
    public function __construct(private ?int $distributionCenterId = null)
    {
    }

    public function model(array $row)
    {
        $customerName = trim($row['ten_khach_hang'] ?? '');

        if (empty($customerName)) {
            return null;
        }

        $code = trim($row['ma_khach_hang'] ?? '');

        if (empty($code)) {
            $code = 'KH-' . strtoupper(Str::slug(Str::limit($customerName, 30, ''), '-'));
        }

        if ($this->distributionCenterId) {
            $existing = Customer::where('customer_code', $code)->first();

            if ($existing && (int) $existing->distribution_center_id !== (int) $this->distributionCenterId) {
                $code = $code . '-TT' . $this->distributionCenterId;
            }
        }

        return Customer::updateOrCreate(
            ['customer_code' => $code],
            [
                'distribution_center_id' => $this->distributionCenterId,
                'customer_name' => $customerName,
                'customer_address' => $row['dia_chi_khach_hang'] ?? null,
                'tax_code' => $row['ma_so_thue'] ?? null,
                'contact_person' => $row['nguoi_lien_he'] ?? null,
                'phone' => $row['dien_thoai'] ?? null,
                'email' => $row['email'] ?? null,
                'project_name' => $row['ten_cong_trinh'] ?? null,
                'project_address' => $row['dia_diem_cong_trinh'] ?? null,
                'is_active' => true,
            ]
        );
    }
}
