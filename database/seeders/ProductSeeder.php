<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\QualityStandard;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $group = ProductGroup::firstOrCreate(
            ['name' => 'Ống HDPE100'],
            [
                'code' => 'HDPE100',
                'description' => 'Ống HDPE PE100',
                'is_active' => true,
            ]
        );

        $standard = QualityStandard::where('code', 'ISO 4427-2:2019')->first();

        $products = [

            [
                'product_code' => 'PE90850',
                'product_name' => 'Ống HDPE(PE100) DN 90 PN8 dài 50m',
                'unit' => 'm',
                'nominal_size' => 'DN90',
                'technical_requirements' => 'PN8',
                'certificate_template' => '1',
            ],

            [
                'product_code' => 'PE2516100',
                'product_name' => 'Ống HDPE(PE100) DN 25 PN16 dài 100m',
                'unit' => 'm',
                'nominal_size' => 'DN25',
                'technical_requirements' => 'PN16',
                'certificate_template' => '1',
            ],

            [
                'product_code' => 'PE2520',
                'product_name' => 'Ống HDPE(PE100) DN 25 PN20',
                'unit' => 'm',
                'nominal_size' => 'DN25',
                'technical_requirements' => 'PN20',
                'certificate_template' => '1',
            ],

            [
                'product_code' => 'PE2512.5',
                'product_name' => 'Ống HDPE(PE100) DN 25 PN12.5',
                'unit' => 'm',
                'nominal_size' => 'DN25',
                'technical_requirements' => 'PN12.5',
                'certificate_template' => '1',
            ],

            [
                'product_code' => 'PE2512.5200',
                'product_name' => 'Ống HDPE(PE100) DN 25 PN12.5 dài 200m',
                'unit' => 'm',
                'nominal_size' => 'DN25',
                'technical_requirements' => 'PN12.5',
                'certificate_template' => '1',
            ],

            [
                'product_code' => 'PE2516',
                'product_name' => 'Ống HDPE(PE100) DN 25 PN16',
                'unit' => 'm',
                'nominal_size' => 'DN25',
                'technical_requirements' => 'PN16',
                'certificate_template' => '1',
            ],

            [
                'product_code' => 'PE3210',
                'product_name' => 'Ống HDPE(PE100) DN 32 PN10',
                'unit' => 'm',
                'nominal_size' => 'DN32',
                'technical_requirements' => 'PN10',
                'certificate_template' => '1',
            ],

            [
                'product_code' => 'PE3212.5',
                'product_name' => 'Ống HDPE(PE100) DN 32 PN12.5',
                'unit' => 'm',
                'nominal_size' => 'DN32',
                'technical_requirements' => 'PN12.5',
                'certificate_template' => '1',
            ],

        ];

        foreach ($products as $item) {

            Product::updateOrCreate(
                [
                    'product_code' => $item['product_code']
                ],
                [
                    'product_group_id' => $group->id,
                    'quality_standard_id' => $standard?->id,

                    'product_name' => $item['product_name'],
                    'unit' => $item['unit'],
                    'nominal_size' => $item['nominal_size'],
                    'technical_requirements' => $item['technical_requirements'],

                    'certificate_type' => 'CNCL',
                    'certificate_template' => $item['certificate_template'],

                    'note' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}