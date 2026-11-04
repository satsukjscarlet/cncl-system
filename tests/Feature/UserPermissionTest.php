<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLoginDeviceIsApproved;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\UserPermissionService;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, DistributionCenterSeeder::class, UserSeeder::class]);
        $this->withoutMiddleware(EnsureLoginDeviceIsApproved::class);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
    }

    public function test_admin_can_grant_block_and_restore_inheritance_without_changing_any_roles(): void
    {
        $target = User::where('username', 'ptn')->firstOrFail();
        $oldRolePermissions = Role::with('permissions')->get()->mapWithKeys(fn ($role) => [$role->id => $role->permissions->pluck('name')->sort()->values()->all()])->all();
        $payload = $this->payload($target, ['product.view' => 'deny', 'device.manage' => 'allow']);
        $this->put(route('users.permissions.update', $target), $payload)->assertRedirect(route('users.permissions.edit', $target));
        $target = $target->fresh();
        $this->assertFalse($target->can('product.view'));
        $this->assertFalse($target->hasPermissionTo('product.view'));
        $this->assertTrue($target->hasDirectPermission('device.manage'));
        $this->assertTrue($target->can('device.manage'));
        $this->assertSame(['PTN'], $target->getRoleNames()->all());
        $this->assertSame($oldRolePermissions, Role::with('permissions')->get()->mapWithKeys(fn ($role) => [$role->id => $role->permissions->pluck('name')->sort()->values()->all()])->all());
        $this->assertDatabaseHas('activity_log', ['log_name' => 'Phân quyền tài khoản', 'subject_id' => $target->id]);
        $this->put(route('users.permissions.update', $target), $this->payload($target, ['product.view' => 'inherit', 'device.manage' => 'inherit']))->assertRedirect();
        $this->assertTrue($target->fresh()->can('product.view'));
        $this->assertFalse($target->fresh()->can('device.manage'));
        $this->assertEmpty($target->fresh()->permission_denials);
    }

    public function test_denials_apply_to_routes_menus_gate_and_permission_apis_even_if_directly_granted(): void
    {
        $target = User::where('username', 'ptn')->firstOrFail();
        $target->givePermissionTo('product.view');
        $target->forceFill(['permission_denials' => ['product.view', 'report.view', 'certificate.sign']])->save();
        $target = $target->fresh();
        $this->assertFalse($target->hasDirectPermission('product.view'));
        $this->assertFalse($target->hasAnyPermission(['product.view', 'certificate.sign']));
        $this->assertFalse($target->hasAllPermissions(['product.view']));
        $this->assertNotContains('product.view', $target->getAllPermissions()->pluck('name')->all());
        Gate::define('product.view', fn () => true);
        $this->assertFalse(Gate::forUser($target)->allows('product.view'));
        $this->actingAs($target)->get('/products')->assertForbidden();
        $this->get('/reports/summary')->assertForbidden();
        $this->get('/reports/work')->assertForbidden();
        $this->get('/dashboard')->assertOk()->assertDontSee('href="http://localhost/products"', false);
        $this->assertFalse($target->can('permission.that.does.not.exist'));
    }

    public function test_every_catalog_permission_can_be_individually_granted_and_denied(): void
    {
        $target = User::factory()->create();
        $permissions = Permission::where('guard_name', 'web')->get();
        $target->givePermissionTo($permissions);
        foreach ($permissions as $permission) {
            $this->assertTrue($target->can($permission->name), $permission->name);
        }
        $target->forceFill(['permission_denials' => $permissions->pluck('name')->all()])->save();
        foreach ($permissions as $permission) {
            $this->assertFalse($target->can($permission->name), $permission->name);
            $this->assertFalse($target->hasPermissionTo($permission), $permission->name);
        }
        $this->assertCount(0, $target->getAllPermissions());
    }

    public function test_non_admin_and_protected_admin_accounts_cannot_be_changed(): void
    {
        $target = User::where('username', 'ptn')->firstOrFail();
        $payload = $this->payload($target, ['device.manage' => 'allow']);
        $actor = User::where('username', 'dvkh')->firstOrFail();
        $actor->givePermissionTo('role_permission.manage');
        $this->actingAs($actor)->get(route('users.permissions.edit', $target))->assertForbidden();
        $this->put(route('users.permissions.update', $target), $payload)->assertForbidden();
        $admin = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin)->get(route('users.permissions.edit', $admin))->assertOk()->assertSee('Tài khoản Admin được bảo vệ');
        $this->put(route('users.permissions.update', $admin), $this->payload($admin, ['role_permission.manage' => 'deny']))->assertForbidden();
        $this->assertTrue($admin->fresh()->can('role_permission.manage'));
    }

    public function test_stale_partial_or_unknown_permission_updates_are_rejected_without_writing(): void
    {
        $target = User::where('username', 'ptn')->firstOrFail();
        $payload = $this->payload($target, ['device.manage' => 'allow']);
        $target->roles->first()->givePermissionTo('device.manage');
        $this->put(route('users.permissions.update', $target), $payload)->assertSessionHasErrors('version');
        $this->assertFalse($target->fresh()->hasDirectPermission('device.manage'));
        $payload = $this->payload($target->fresh());
        unset($payload['overrides']['product.view']);
        $this->put(route('users.permissions.update', $target), $payload)->assertSessionHasErrors('overrides');
        $payload = $this->payload($target->fresh());
        $payload['overrides']['unknown.permission'] = 'allow';
        $this->put(route('users.permissions.update', $target), $payload)->assertSessionHasErrors('overrides');
        $this->assertSame(0, Activity::where('log_name', 'Phân quyền tài khoản')->count());
    }

    public function test_seed_group_updates_preserve_exceptions_and_other_guards_are_untouched(): void
    {
        $target = User::where('username', 'ptn')->firstOrFail();
        $api = Permission::create(['name' => 'device.manage', 'guard_name' => 'api']);
        $target->permissions()->attach($api->id);
        $this->put(route('users.permissions.update', $target), $this->payload($target->fresh(), ['product.view' => 'deny', 'device.manage' => 'allow']))->assertRedirect();
        $this->seed(PermissionSeeder::class);
        $this->assertFalse($target->fresh()->can('product.view'));
        $this->assertTrue($target->fresh()->can('device.manage'));
        $this->assertTrue($target->fresh()->permissions->contains('id', $api->id));
    }

    public function test_notification_permission_recipients_exclude_denied_accounts(): void
    {
        $target = User::where('username', 'dvkh')->firstOrFail();
        $target->forceFill(['permission_denials' => ['dvkh.process']])->save();
        $allowed = User::factory()->create();
        $allowed->givePermissionTo('dvkh.process');
        app(NotificationService::class)->sendToPermission('dvkh.process', ['type' => 'test', 'title' => 'Test', 'message' => 'Test', 'url' => '/dvkh/requests']);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $target->id, 'type' => 'test']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $allowed->id, 'type' => 'test']);
    }

    public function test_profile_and_generic_user_forms_cannot_change_permission_exceptions(): void
    {
        $target = User::where('username', 'ptn')->firstOrFail();
        $target->forceFill(['permission_denials' => ['product.view']])->save();
        $this->actingAs($target)->patch('/profile', ['name' => $target->name, 'email' => $target->email, 'permission_denials' => [], 'permissions' => ['role_permission.manage']])->assertRedirect();
        $this->assertSame(['product.view'], $target->fresh()->permission_denials);
        $this->assertFalse($target->fresh()->can('role_permission.manage'));
    }

    public function test_permission_screen_renders_and_migration_is_idempotent(): void
    {
        $target = User::where('username', 'ptn')->firstOrFail();
        $target->forceFill(['permission_denials' => ['product.view']])->save();
        $migration = require database_path('migrations/2026_11_03_000002_add_permission_denials_to_users.php');
        $migration->up();
        $this->assertSame(['product.view'], $target->fresh()->permission_denials);
        $response = $this->get(route('users.permissions.edit', $target))->assertOk()->assertSee('Phân quyền tài khoản')->assertSee('overrides[product.view]', false);
        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            file_put_contents($directory.'/user-permissions.html', $response->getContent());
        }
    }

    public function test_center_account_cannot_be_given_internal_workflow_or_account_management_access(): void
    {
        $center = User::where('username', 'trungtam_np')->firstOrFail();
        $this->put(route('users.permissions.update', $center), $this->payload($center, ['dvkh.process' => 'allow']))->assertSessionHasErrors('overrides');
        $center->givePermissionTo(['dvkh.process', 'ptn.process', 'certificate.sign', 'user.view']);
        $this->actingAs($center->fresh());
        foreach (['/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue', '/users'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get('/certificate-requests')->assertOk();
    }

    public function test_denied_dashboard_login_uses_profile_and_dashboard_workflow_uses_effective_permissions(): void
    {
        $target = User::factory()->create(['username' => 'account-denied-dashboard']);
        $target->forceFill(['permission_denials' => ['dashboard.view']])->save();
        $this->post('/logout');
        $this->post('/login', ['username' => $target->username, 'password' => 'password'])->assertRedirect('/profile');
        $worker = User::where('username', 'dvkh')->firstOrFail();
        $worker->forceFill(['permission_denials' => ['dvkh.process']])->save();
        $this->actingAs($worker->fresh())->get('/dashboard')->assertOk()->assertViewHas('role', 'Viewer');
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['dashboard.view', 'dvkh.process']);
        $this->actingAs($viewer)->get('/dashboard')->assertOk()->assertViewHas('role', 'DVKH');
        $viewer->givePermissionTo('certificate.print');
        $this->get('/print-logs')->assertOk();
    }

    private function payload(User $target, array $changes = []): array
    {
        $service = app(UserPermissionService::class);
        $target = $target->fresh();
        $state = $service->state($target, $service->catalog());
        $modes = array_map(fn ($row) => $row['mode'], $state['rows']);

        return ['version' => $state['version'], 'overrides' => array_replace($modes, $changes)];
    }
}
