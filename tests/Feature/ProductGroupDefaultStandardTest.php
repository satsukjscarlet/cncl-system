<?php

namespace Tests\Feature;

use App\Imports\ProductsImport;
use App\Imports\ProductGroupsImport;
use App\Exports\ProductGroupsExport;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductUnit;
use App\Models\QualityStandard;
use App\Models\User;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductGroupDefaultStandardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, DistributionCenterSeeder::class, UserSeeder::class]);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
    }

    private function standard(string $code, bool $active = true): QualityStandard
    {
        return QualityStandard::create(['code' => $code, 'name' => $code, 'is_active' => $active]);
    }

    private function group(?QualityStandard $standard = null): ProductGroup
    {
        return ProductGroup::create([
            'code' => 'GROUP', 'name' => 'Test group', 'description' => 'Existing note',
            'is_active' => true, 'default_quality_standard_id' => $standard?->id,
        ]);
    }

    private function payload(ProductGroup $group): array
    {
        $unit = ProductUnit::firstOrCreate(['code' => 'M'], ['name' => 'm', 'is_active' => true]);
        return [
            'product_group_id' => $group->id, 'unit_id' => $unit->id,
            'product_code' => 'PRODUCT', 'product_name' => 'Test product',
            'certificate_type' => 'CNCL', 'certificate_template' => 'default', 'is_active' => 1,
        ];
    }

    public function test_group_default_is_optional_and_does_not_rewrite_existing_products(): void
    {
        $a = $this->standard('STD-A');
        $b = $this->standard('STD-B');
        $this->get(route('product-groups.create'))->assertOk()->assertSee('Tiêu chuẩn chất lượng mặc định');
        $this->post(route('product-groups.store'), [
            'code' => 'GROUP', 'name' => 'Test group', 'is_active' => 1,
            'description' => 'Existing note', 'default_quality_standard_id' => $a->id,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $group = ProductGroup::where('code', 'GROUP')->firstOrFail();
        $this->post(route('products.store'), $this->payload($group))->assertSessionHasNoErrors();
        $product = Product::where('product_code', 'PRODUCT')->firstOrFail();
        $this->assertEquals($a->id, $product->quality_standard_id);
        $this->put(route('product-groups.update', $group), [
            'code' => 'GROUP', 'name' => 'Test group', 'is_active' => 1,
            'description' => 'Existing note', 'default_quality_standard_id' => $b->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals($a->id, $product->fresh()->quality_standard_id);
        $this->assertSame('Existing note', $group->fresh()->description);
        $this->get(route('product-groups.index'))->assertOk()->assertSee('STD-B');
        $this->get(route('products.edit', $product))->assertOk();
        $this->put(route('product-groups.update', $group), [
            'code' => 'GROUP', 'name' => 'Test group', 'is_active' => 1, 'default_quality_standard_id' => '',
        ])->assertSessionHasNoErrors();
        $this->assertNull($group->fresh()->default_quality_standard_id);
    }

    public function test_product_allows_override_and_explicit_blank_and_keeps_inactive_existing_standard(): void
    {
        $a = $this->standard('STD-A');
        $b = $this->standard('STD-B');
        $group = $this->group($a);
        $payload = $this->payload($group);
        $this->post(route('products.store'), $payload + ['quality_standard_id' => $b->id])->assertSessionHasNoErrors();
        $product = Product::where('product_code', 'PRODUCT')->firstOrFail();
        $this->assertEquals($b->id, $product->quality_standard_id);
        $b->update(['is_active' => false]);
        $this->get(route('products.edit', $product))->assertOk()->assertSee('STD-B');
        $this->put(route('products.update', $product), $payload + ['quality_standard_id' => $b->id])->assertSessionHasNoErrors();
        $this->assertEquals($b->id, $product->fresh()->quality_standard_id);
        $payload['product_code'] = 'EMPTY';
        $this->post(route('products.store'), $payload + ['quality_standard_id' => ''])->assertSessionHasNoErrors();
        $this->assertNull(Product::where('product_code', 'EMPTY')->firstOrFail()->quality_standard_id);
    }

    public function test_invalid_inactive_and_deleted_defaults_are_rejected_and_linked_standard_cannot_be_deleted(): void
    {
        $inactive = $this->standard('INACTIVE', false);
        $deleted = $this->standard('DELETED');
        $deleted->delete();
        foreach ([999999, $inactive->id, $deleted->id] as $id) {
            $this->post(route('product-groups.store'), [
                'code' => 'INVALID', 'name' => 'Invalid', 'default_quality_standard_id' => $id,
            ])->assertSessionHasErrors('default_quality_standard_id');
        }
        $a = $this->standard('STD-A');
        $group = $this->group($a);
        $this->delete(route('quality-standards.force-destroy', $a))->assertSessionHas('error');
        $group->delete();
        $this->delete(route('quality-standards.force-destroy', $a))->assertSessionHas('error');
        $this->assertDatabaseHas('quality_standards', ['id' => $a->id]);
    }

    public function test_import_defaults_new_products_preserves_existing_and_rejects_unknown_standard(): void
    {
        $a = $this->standard('STD-A');
        $b = $this->standard('STD-B');
        $group = $this->group($a);
        $import = new ProductsImport;
        $row = ['ma_san_pham' => 'IMPORT', 'ten_san_pham' => 'Imported product', 'ma_nhom_san_pham' => $group->code];
        $product = $import->model($row);
        $this->assertEquals($a->id, $product->quality_standard_id);
        $product->update(['quality_standard_id' => $b->id]);
        $this->assertEquals($b->id, $import->model($row)->quality_standard_id);
        $row['tieu_chuan_san_pham'] = $a->code;
        $this->assertEquals($a->id, $import->model($row)->quality_standard_id);
        $row['tieu_chuan_san_pham'] = 'UNKNOWN';
        $this->assertNull($import->model($row));
        $this->assertSame(1, $import->failed);
        $this->assertStringContainsString('UNKNOWN', $import->errors[0]);
        $this->assertEquals($a->id, $product->fresh()->quality_standard_id);
        $this->assertDatabaseMissing('quality_standards', ['code' => 'UNKNOWN']);
        $a->update(['is_active' => false]);
        unset($row['tieu_chuan_san_pham']);
        $row['ma_san_pham'] = 'NO-DEFAULT';
        $this->assertNull($import->model($row)->quality_standard_id);
    }

    public function test_product_forms_render_defaults_and_preserve_existing_selection(): void
    {
        $a = $this->standard('STD-A');
        $b = $this->standard('STD-B');
        $this->standard('STD-C');
        $group = $this->group($a);
        ProductGroup::create(['code' => 'GROUP-B', 'name' => 'Group B', 'is_active' => true, 'default_quality_standard_id' => $b->id]);
        ProductGroup::create(['code' => 'GROUP-EMPTY', 'name' => 'Group empty', 'is_active' => true]);
        $create = $this->get(route('products.create'))->assertOk()->assertSee('data-default-standard="' . $a->id . '"', false);
        $this->post(route('products.store'), $this->payload($group) + ['quality_standard_id' => $b->id])->assertSessionHasNoErrors();
        $product = Product::where('product_code', 'PRODUCT')->firstOrFail();
        $edit = $this->get(route('products.edit', $product))->assertOk();
        if ($directory = getenv('CNCL_PRODUCT_UI_SNAPSHOT_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0777, true);
            file_put_contents($directory . '/product-default-create.html', $create->getContent());
            file_put_contents($directory . '/product-default-edit.html', $edit->getContent());
            file_put_contents($directory . '/group-default-edit.html', $this->get(route('product-groups.edit', $group))->assertOk()->getContent());
            file_put_contents($directory . '/group-default-create.html', $this->get(route('product-groups.create'))->assertOk()->getContent());
        }
    }

    public function test_bulk_import_used_by_web_applies_the_same_rules(): void
    {
        $a = $this->standard('STD-A');
        $b = $this->standard('STD-B');
        $group = $this->group($a);
        $this->post(route('products.store'), $this->payload($group) + ['quality_standard_id' => $b->id])->assertSessionHasNoErrors();
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([
            ['ma_nhom_san_pham', 'ten_nhom_san_pham', 'ma_san_pham', 'ten_san_pham', 'tieu_chuan_san_pham'],
            ['GROUP', 'Test group', 'PRODUCT', 'Existing', ''],
            ['GROUP', 'Test group', 'NEW', 'New', ''],
            ['GROUP', 'Test group', 'EXPLICIT', 'Explicit', 'STD-B'],
            ['GROUP', 'Test group', 'EXPLICIT', 'Duplicate blank', ''],
            ['GROUP', 'Test group', 'INVALID', 'Invalid', 'UNKNOWN'],
        ]);
        $file = tempnam(sys_get_temp_dir(), 'cncl-products-');
        try {
            (new Xlsx($book))->save($file);
            $this->assertSame(1, Artisan::call('products:import-file', ['file' => $file, '--no-progress' => true]));
            $this->assertStringContainsString('UNKNOWN', Artisan::output());
            $this->assertEquals($b->id, Product::where('product_code', 'PRODUCT')->firstOrFail()->quality_standard_id);
            $this->assertEquals($a->id, Product::where('product_code', 'NEW')->firstOrFail()->quality_standard_id);
            $this->assertEquals($b->id, Product::where('product_code', 'EXPLICIT')->firstOrFail()->quality_standard_id);
            $this->assertDatabaseMissing('products', ['product_code' => 'INVALID']);
            $this->assertDatabaseMissing('quality_standards', ['code' => 'UNKNOWN']);
        } finally {
            $book->disconnectWorksheets();
            if (is_file($file)) unlink($file);
        }
    }

    public function test_group_excel_export_can_be_imported_without_losing_settings(): void
    {
        $a = $this->standard('STD-A');
        $group = $this->group($a);
        $group->update(['is_active' => false]);
        $export = new ProductGroupsExport;
        $this->assertSame('Ghi chú', $export->headings()[2]);
        $this->assertSame('Mã tiêu chuẩn mặc định', $export->headings()[4]);
        $this->assertSame('STD-A', $export->collection()->first()[4]);
        $file = tempnam(sys_get_temp_dir(), 'cncl-group-');
        try {
            file_put_contents($file, Excel::raw($export, ExcelFormat::XLSX));
            Excel::import(new ProductGroupsImport, $file, null, ExcelFormat::XLSX);
            $this->assertEquals($a->id, $group->fresh()->default_quality_standard_id);
            $this->assertSame('Existing note', $group->fresh()->description);
            $this->assertFalse((bool) $group->fresh()->is_active);
            $this->assertSame(1, ProductGroup::where('code', 'GROUP')->count());
            $this->assertCount(0, (new ProductGroupsExport(true))->collection());
            $this->get(route('product-groups.template'))->assertOk()->assertDownload('template_nhom_san_pham.xlsx');
            $this->get(route('product-groups.export'))->assertOk()->assertDownload('danh_muc_nhom_san_pham.xlsx');
        } finally {
            if (is_file($file)) unlink($file);
        }
    }

    public function test_group_import_updates_by_code_preserves_blank_default_and_reads_legacy_notes(): void
    {
        $a = $this->standard('STD-A');
        $b = $this->standard('STD-B');
        $group = $this->group($a);
        $import = new ProductGroupsImport;
        $import->collection(collect([[
            'ma_nhom_san_pham' => 'GROUP', 'ten_nhom_san_pham' => 'Renamed group',
            'ma_tieu_chuan_mac_dinh' => $b->code, 'ghi_chu' => 'New note', 'trang_thai' => '0',
        ]]));
        $this->assertEquals($b->id, $group->fresh()->default_quality_standard_id);
        $this->assertSame('Renamed group', $group->fresh()->name);
        $import->collection(collect([[
            'ma_nhom_san_pham' => 'GROUP', 'ten_nhom_san_pham' => 'Renamed group',
            'ma_tieu_chuan_mac_dinh' => '', 'tieu_chuan' => 'Legacy note',
        ]]));
        $this->assertEquals($b->id, $group->fresh()->default_quality_standard_id);
        $this->assertSame('Legacy note', $group->fresh()->description);
        $this->assertFalse((bool) $group->fresh()->is_active);
        $import->collection(collect([['ma_nhom_san_pham' => 'NEW', 'ten_nhom_san_pham' => 'New group']]));
        $this->assertNull(ProductGroup::where('code', 'NEW')->firstOrFail()->default_quality_standard_id);
    }

    public function test_group_import_errors_are_line_specific_and_do_not_save_any_rows(): void
    {
        $a = $this->standard('STD-A');
        $this->standard('INACTIVE', false);
        $group = $this->group($a);
        foreach (['UNKNOWN', 'INACTIVE'] as $code) {
            try {
                (new ProductGroupsImport)->collection(collect([
                    ['ma_nhom_san_pham' => 'GROUP', 'ten_nhom_san_pham' => 'Must not save'],
                    ['ma_nhom_san_pham' => 'NEW', 'ten_nhom_san_pham' => 'New', 'ma_tieu_chuan_mac_dinh' => $code],
                ]));
                $this->fail('Invalid standard accepted');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('Dòng 3', $e->errors()['file'][0]);
                $this->assertStringContainsString($code, $e->errors()['file'][0]);
            }
        }
        $this->assertSame('Test group', $group->fresh()->name);
        $this->assertDatabaseMissing('product_groups', ['code' => 'NEW']);
        $this->get(route('product-groups.index'))->assertOk();
    }

    public function test_invalid_group_upload_displays_errors_and_duplicate_codes_are_rejected(): void
    {
        $this->group($this->standard('STD-A'));
        $file = UploadedFile::fake()->createWithContent('groups.csv',
            "ma_nhom_san_pham,ten_nhom_san_pham,ma_tieu_chuan_mac_dinh\nGROUP,Changed,UNKNOWN\n");
        $this->from(route('product-groups.index'))->post(route('product-groups.import'), ['file' => $file])
            ->assertRedirect(route('product-groups.index'))->assertSessionHasErrors('file');
        $this->get(route('product-groups.index'))->assertOk()->assertSee('Dòng 2')->assertSee('UNKNOWN');
        $this->assertDatabaseHas('product_groups', ['code' => 'GROUP', 'name' => 'Test group']);
        try {
            (new ProductGroupsImport)->collection(collect([
                ['ma_nhom_san_pham' => 'NEW', 'ten_nhom_san_pham' => 'First'],
                ['ma_nhom_san_pham' => 'NEW', 'ten_nhom_san_pham' => 'Second'],
            ]));
            $this->fail('Duplicate code accepted');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('bị lặp', $e->errors()['file'][0]);
        }
        $this->assertDatabaseMissing('product_groups', ['code' => 'NEW']);
    }
}
