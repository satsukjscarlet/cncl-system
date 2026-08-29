<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $permissionGroups = $this->permissionGroups($permissions);
        $roleLabels = $this->roleLabels();

        return view('role_permissions.index', compact('roles', 'permissionGroups', 'roleLabels'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $permissions = collect($data['permissions'] ?? []);

        if ($role->name === 'Admin') {
            $permissions = $permissions->merge(Permission::pluck('name'));
        }

        $oldPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

        $role->syncPermissions($permissions->unique()->values()->all());

        ActivityLogger::log(
            'Phân quyền',
            'update',
            'Cập nhật phân quyền vai trò: ' . $role->name,
            ['permissions' => $oldPermissions],
            ['permissions' => $role->fresh()->permissions()->pluck('name')->sort()->values()->all()]
        );

        return redirect()
            ->route('role-permissions.index')
            ->with('success', 'Cập nhật phân quyền thành công.');
    }

    private function permissionGroups($permissions): array
    {
        $labels = $this->permissionLabels();

        return $permissions
            ->groupBy(fn ($permission) => explode('.', $permission->name)[0])
            ->mapWithKeys(function ($items, string $group) use ($labels) {
                return [
                    $this->groupLabel($group) => $items->map(function ($permission) use ($labels) {
                        return [
                            'name' => $permission->name,
                            'label' => $labels[$permission->name] ?? $permission->name,
                        ];
                    })->values(),
                ];
            })
            ->toArray();
    }

    private function groupLabel(string $group): string
    {
        return [
            'dashboard' => 'Dashboard',
            'distribution_center' => 'Trung tâm phân phối',
            'product_group' => 'Nhóm sản phẩm',
            'product' => 'Sản phẩm',
            'quality_standard' => 'Tiêu chuẩn chất lượng',
            'urgent_reason' => 'Lý do yêu cầu gấp',
            'customer' => 'Khách hàng - Công trình',
            'request' => 'Yêu cầu cấp phiếu',
            'dvkh' => 'DVKH',
            'ptn' => 'PTN',
            'certificate' => 'Phiếu CNCL',
            'report' => 'Báo cáo',
            'sla' => 'SLA',
            'setting' => 'Cấu hình hệ thống',
            'user' => 'Người dùng',
            'role_permission' => 'Phân quyền',
            'log' => 'Nhật ký',
        ][$group] ?? $group;
    }

    private function permissionLabels(): array
    {
        return [
            'dashboard.view' => 'Xem dashboard',

            'distribution_center.view' => 'Xem trung tâm phân phối',
            'distribution_center.create' => 'Thêm trung tâm phân phối',
            'distribution_center.update' => 'Sửa trung tâm phân phối',
            'distribution_center.delete' => 'Xóa trung tâm phân phối',

            'product_group.view' => 'Xem nhóm sản phẩm',
            'product_group.create' => 'Thêm nhóm sản phẩm',
            'product_group.update' => 'Sửa nhóm sản phẩm',
            'product_group.delete' => 'Xóa nhóm sản phẩm',
            'product_group.import' => 'Import nhóm sản phẩm',
            'product_group.export' => 'Export nhóm sản phẩm',

            'product.view' => 'Xem sản phẩm',
            'product.create' => 'Thêm sản phẩm',
            'product.update' => 'Sửa sản phẩm',
            'product.delete' => 'Xóa sản phẩm',
            'product.import' => 'Import sản phẩm',
            'product.export' => 'Export sản phẩm',

            'quality_standard.view' => 'Xem tiêu chuẩn chất lượng',
            'quality_standard.create' => 'Thêm tiêu chuẩn chất lượng',
            'quality_standard.update' => 'Sửa tiêu chuẩn chất lượng',
            'quality_standard.delete' => 'Xóa tiêu chuẩn chất lượng',
            'quality_standard.import' => 'Import tiêu chuẩn chất lượng',
            'quality_standard.export' => 'Export tiêu chuẩn chất lượng',

            'urgent_reason.view' => 'Xem danh mục lý do gấp',
            'urgent_reason.create' => 'Thêm lý do gấp',
            'urgent_reason.update' => 'Sửa lý do gấp',
            'urgent_reason.delete' => 'Ngừng sử dụng lý do gấp',

            'customer.view' => 'Xem khách hàng - công trình',
            'customer.create' => 'Thêm khách hàng - công trình',
            'customer.update' => 'Sửa khách hàng - công trình',
            'customer.delete' => 'Xóa khách hàng - công trình',
            'customer.import' => 'Import khách hàng - công trình',
            'customer.export' => 'Export khách hàng - công trình',

            'request.view' => 'Xem yêu cầu cấp phiếu',
            'request.create' => 'Tạo yêu cầu cấp phiếu',
            'request.update' => 'Sửa yêu cầu cấp phiếu',
            'request.delete' => 'Xóa yêu cầu cấp phiếu',

            'dvkh.process' => 'DVKH xử lý yêu cầu',
            'ptn.process' => 'PTN lập phiếu CNCL',

            'certificate.view' => 'Xem phiếu CNCL',
            'certificate.create' => 'Tạo phiếu CNCL',
            'certificate.sign' => 'Ký/phát hành phiếu',
            'certificate.reject' => 'Từ chối ký/trả lại phiếu',
            'certificate.print' => 'In ký tươi',
            'certificate.email' => 'Gửi email phiếu',

            'report.view' => 'Xem báo cáo',
            'report.export' => 'Xuất báo cáo',

            'sla.view' => 'Xem SLA',
            'sla.create' => 'Thêm SLA',
            'sla.update' => 'Sửa SLA',
            'sla.delete' => 'Xóa SLA',
            'sla.import' => 'Import SLA',
            'sla.export' => 'Export SLA',

            'setting.view' => 'Xem cấu hình hệ thống',
            'setting.update' => 'Sửa cấu hình hệ thống',

            'user.view' => 'Xem người dùng',
            'user.create' => 'Thêm người dùng',
            'user.update' => 'Sửa người dùng',
            'user.delete' => 'Xóa người dùng',
            'user.reset_password' => 'Reset mật khẩu người dùng',
            'user.toggle_active' => 'Khóa/mở khóa người dùng',

            'role_permission.manage' => 'Quản lý phân quyền',
            'log.view' => 'Xem nhật ký',
        ];
    }

    private function roleLabels(): array
    {
        return [
            'Admin' => 'Quản trị viên',
            'LanhDao' => 'Lãnh đạo',
            'TrungTam' => 'Trung tâm phân phối',
            'DVKH' => 'Dịch vụ khách hàng',
            'PTN' => 'Phòng thử nghiệm',
            'TruongPTN' => 'Trưởng phòng thử nghiệm',
            'Viewer' => 'Chỉ xem',
        ];
    }
}
