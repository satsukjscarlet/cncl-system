<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Customer::orderBy('customer_name')
            ->get()
            ->map(function ($customer) {
                return [
                    'ma_khach_hang' => $customer->customer_code,
                    'ten_khach_hang' => $customer->customer_name,
                    'dia_chi_khach_hang' => $customer->customer_address,
                    'ma_so_thue' => $customer->tax_code,
                    'nguoi_lien_he' => $customer->contact_person,
                    'dien_thoai' => $customer->phone,
                    'email' => $customer->email,
                    'ten_cong_trinh' => $customer->project_name,
                    'dia_diem_cong_trinh' => $customer->project_address,
                    'trang_thai' => $customer->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ma_khach_hang',
            'ten_khach_hang',
            'dia_chi_khach_hang',
            'ma_so_thue',
            'nguoi_lien_he',
            'dien_thoai',
            'email',
            'ten_cong_trinh',
            'dia_diem_cong_trinh',
            'trang_thai',
        ];
    }
}