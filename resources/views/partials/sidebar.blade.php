<div class="sidebar">
    <div class="p-4 border-bottom border-secondary">
        <h5 class="mb-0">CNCL NTP</h5>
        <small>Quan ly phieu chat luong</small>
    </div>

    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
        Dashboard
    </a>

    @role('Admin')
        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Quan ly nguoi dung</a>
        <a href="{{ route('role-permissions.index') }}" class="{{ request()->routeIs('role-permissions.*') ? 'active' : '' }}">Phan quyen</a>
        <a href="{{ route('sla-configs.index') }}" class="{{ request()->routeIs('sla-configs.*') ? 'active' : '' }}">Cau hinh SLA</a>
        <a href="{{ route('distribution-centers.index') }}" class="{{ request()->routeIs('distribution-centers.*') ? 'active' : '' }}">
            Trung tam phan phoi
        </a>
        <a href="{{ route('product-groups.index') }}" class="{{ request()->routeIs('product-groups.*') ? 'active' : '' }}">
            Nhom san pham
        </a>
    @endrole

    @hasanyrole('Admin|TrungTam')
        <a href="{{ route('certificate-requests.index') }}" class="{{ request()->routeIs('certificate-requests.*') ? 'active' : '' }}">Yeu cau cap phieu</a>
    @endhasanyrole

    @hasanyrole('Admin|DVKH')
        <a href="{{ route('dvkh.requests.index') }}" class="{{ request()->routeIs('dvkh.requests.*') ? 'active' : '' }}">DVKH kiem tra</a>
    @endhasanyrole

    @hasanyrole('Admin|PTN')
        <a href="{{ route('ptn.requests.index') }}" class="{{ request()->routeIs('ptn.requests.*') ? 'active' : '' }}">PTN lap phieu</a>
        <a href="{{ route('quality-certificates.index') }}" class="{{ request()->routeIs('quality-certificates.*') ? 'active' : '' }}">Phieu ky tuoi</a>
    @endhasanyrole

    @hasanyrole('Admin|Viewer|DVKH|PTN|TruongPTN')
        <a href="{{ route('reports.summary') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">Bao cao</a>
    @endhasanyrole

    @role('Admin')
        <a href="{{ route('activity-logs.index') }}" class="{{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">Lich su thao tac</a>
        <a href="#">Lich su dang nhap</a>
    @endrole
</div>
