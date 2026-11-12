<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    public function isSalesUnit(): bool
    {
        return $this->hasRole('DonViBanHang');
    }

    public function isExternalAccount(): bool
    {
        return $this->isSalesUnit() || $this->hasRole('TrungTam');
    }

    public function salesUnit()
    {
        return $this->belongsTo(SalesUnit::class);
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles {
        hasPermissionTo as private inheritedHasPermissionTo;
        hasDirectPermission as private inheritedHasDirectPermission;
        getAllPermissions as private inheritedGetAllPermissions;
    }

    public function isPermissionDenied($permission, $guardName = null): bool
    {
        if ($permission instanceof \BackedEnum) {
            $permission = $permission->value;
        }
        if (is_int($permission)) {
            $permission = $this->filterPermission($permission, $guardName);
        }
        if ($permission instanceof Permission) {
            $guardName = $permission->guard_name;
            $permission = $permission->name;
        }

        return ($guardName ?? $this->getDefaultGuardName()) === 'web'
            && is_string($permission) && in_array($permission, (array) $this->permission_denials, true);
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        return ! $this->isPermissionDenied($permission, $guardName) && ! $this->isPermissionRestricted($permission) && $this->inheritedHasPermissionTo($permission, $guardName);
    }

    public function isPermissionRestricted($permission): bool
    {
        if ($permission instanceof \BackedEnum) {
            $permission = $permission->value;
        }
        if (is_int($permission)) {
            $permission = $this->filterPermission($permission);
        }
        if ($permission instanceof Permission) {
            $permission = $permission->name;
        }
        if ($this->isSalesUnit() && is_string($permission)) {
            return ! in_array($permission, ['dashboard.view', 'request.view', 'request.create', 'request.update', 'request.delete', 'certificate.view'], true);
        }
        if (! is_string($permission) || ! $this->hasRole('TrungTam')) {
            return false;
        }
        $group = explode('.', $permission)[0];

        return in_array($group, ['dvkh', 'ptn', 'user', 'device', 'role_permission', 'setting', 'sla', 'log', 'distribution_center', 'storage-usage'], true)
            || in_array($permission, ['certificate.create', 'certificate.sign', 'certificate.reject', 'certificate.email'], true);
    }

    public function hasDirectPermission($permission): bool
    {
        return ! $this->isPermissionDenied($permission) && ! $this->isPermissionRestricted($permission) && $this->inheritedHasDirectPermission($permission);
    }

    public function getAllPermissions(): Collection
    {
        return $this->inheritedGetAllPermissions()->reject(fn ($permission) => $this->isPermissionDenied($permission) || $this->isPermissionRestricted($permission))->values();
    }

    protected $fillable = [
        'name',
        'username',
        'email',
        'smartca_user_id',
        'password',
        'distribution_center_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'work_report_departments' => 'array',
            'permission_denials' => 'array',
        ];
    }

    public function distributionCenter()
    {
        return $this->belongsTo(DistributionCenter::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function adminlte_profile_url(): string
    {
        return route('profile.edit');
    }
}
