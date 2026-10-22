<?php

namespace Tests\Feature;

use App\Models\SalesUnit;
use App\Models\User;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesUnitListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, DistributionCenterSeeder::class, UserSeeder::class]);
    }

    public function test_search_and_combined_filters_work_with_center_join(): void
    {
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
        $np = User::where('username', 'trungtam_np')->firstOrFail()->distribution_center_id;
        $tp = User::where('username', 'trungtam_tp')->firstOrFail()->distribution_center_id;
        $unit = SalesUnit::create([
            'distribution_center_id' => $np, 'code' => 'BRAVO-UNIQUE', 'name' => 'Unique seller',
            'address' => 'Unique address', 'phone' => '0987654321', 'tax_code' => 'TAX-UNIQUE',
            'bank_account' => 'BANK-UNIQUE', 'representative' => 'Unique director',
            'note' => 'Unique note', 'is_active' => true,
        ]);
        SalesUnit::create(['distribution_center_id' => $tp, 'code' => 'OTHER', 'name' => 'Other seller', 'is_active' => false]);
        foreach (['BRAVO-UNIQUE', 'Unique seller', 'Unique address', '0987654321', 'TAX-UNIQUE', 'BANK-UNIQUE', 'Unique director', 'Unique note'] as $keyword) {
            $response = $this->get(route('sales-units.index', ['keyword' => $keyword]));
            $response->assertOk();
            $items = $response->viewData('salesUnits');
            $this->assertSame([$unit->id], $items->pluck('id')->all());
            $this->assertSame(0, $items->first()->certificate_requests_total_count);
        }
        $this->get(route('sales-units.index', ['keyword' => 'BRAVO', 'distribution_center_id' => $np, 'status' => '1']))
            ->assertOk()->assertViewHas('salesUnits', fn ($items) => $items->total() === 1);
        $this->get(route('sales-units.index', ['keyword' => 'BRAVO', 'distribution_center_id' => $tp, 'status' => '1']))
            ->assertOk()->assertViewHas('salesUnits', fn ($items) => $items->total() === 0);
        $this->get(route('sales-units.index', ['status' => '0']))
            ->assertOk()->assertViewHas('salesUnits', fn ($items) => $items->total() === 1 && $items->first()->code === 'OTHER');
    }

    public function test_center_cannot_search_sales_units_of_another_center(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureLoginDeviceIsApproved::class);
        $user = User::where('username', 'trungtam_np')->firstOrFail();
        $other = User::where('username', 'trungtam_tp')->firstOrFail()->distribution_center_id;
        $own = SalesUnit::create(['distribution_center_id' => $user->distribution_center_id, 'code' => 'SHARED', 'name' => 'Own seller', 'is_active' => true]);
        SalesUnit::create(['distribution_center_id' => $other, 'code' => 'SHARED', 'name' => 'Other seller', 'is_active' => true]);
        $this->actingAs($user)->get(route('sales-units.index', [
            'keyword' => 'SHARED', 'status' => '1', 'distribution_center_id' => $other,
        ]))->assertOk()->assertViewHas('salesUnits', fn ($items) => $items->pluck('id')->all() === [$own->id]);
    }
}
