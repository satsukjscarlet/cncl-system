@extends('adminlte::page')
@section('title', 'Dung lượng hệ thống')
@section('css')
<style>
    #storage-results .table { min-width: 620px; }
    #storage-results .table td { vertical-align: middle; }
</style>
@endsection
@section('content_header')
<div class="d-flex flex-wrap align-items-center justify-content-between">
    <h1 class="h4 mb-2 mr-3">Dung lượng hệ thống</h1>
    <button type="button" id="scan-storage" class="btn btn-primary mb-2"><i class="fas fa-sync-alt mr-1"></i> Quét lại</button>
</div>
@endsection
@section('content')
<div id="scan-status" role="status" aria-live="polite" class="alert alert-info">Đang chuẩn bị thống kê…</div>
<div id="scan-errors" role="alert"></div>
<div id="storage-results" hidden>
    <div class="row">
        @foreach(['files' => 'Tổng file trong storage', 'bytes' => 'Dung lượng storage', 'pdf_files' => 'File PDF đang lưu', 'pdf_bytes' => 'Dung lượng PDF'] as $key => $label)
        <div class="col-6 col-lg-3 mb-3"><div class="border rounded bg-white p-3 h-100"><div class="text-muted small">{{ $label }}</div><strong class="h4 d-block mt-2 mb-0" id="metric-{{ $key }}">—</strong></div></div>
        @endforeach
    </div>
    <section class="mb-4">
        <h2 class="h5">Ổ đĩa chứa thư mục storage</h2>
        <p id="disk-summary"></p><div class="progress" style="height:18px"><div id="disk-progress" class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div></div>
        <p class="text-muted small mt-2">Dung lượng ổ đĩa bao gồm các ứng dụng khác trên cùng phân vùng, không chỉ hệ thống CNCL.</p>
    </section>
    <section class="mb-4">
        <h2 class="h5">Thư mục chứa PDF</h2>
        <div class="table-responsive"><table class="table table-sm table-striped bg-white"><thead><tr><th>Thư mục trong storage</th><th>Loại PDF</th><th class="text-right">Số file</th><th class="text-right">Dung lượng</th><th class="text-right">Tỷ lệ PDF</th></tr></thead><tbody id="pdf-directory-rows"></tbody></table></div>
        <p class="text-muted small">Phân loại theo liên kết phiếu và cấu trúc lưu trữ SmartCA đã biết, không kiểm định chữ ký trong nội dung file. File chuẩn bị ký là file trung gian; PDF khác không tự coi là chưa ký. Các thư mục giao dịch được gộp theo vùng lưu trữ.</p>
    </section>
    <section class="mb-4">
        <h2 class="h5">Biến động dung lượng trong tháng <span id="history-month"></span></h2>
        <p id="history-summary"></p>
        <div class="table-responsive"><table class="table table-sm table-striped bg-white"><thead><tr><th>Chỉ tiêu</th><th class="text-right">So với lần quét trước trong tháng</th><th class="text-right">So với mốc đầu tiên trong tháng</th></tr></thead><tbody id="growth-rows"></tbody></table></div>
        <h3 class="h6">Dung lượng PDF theo ngày có quét</h3>
        <div id="history-chart" class="mb-3"></div>
        <div class="table-responsive"><table class="table table-sm table-striped bg-white"><thead><tr><th>Lần quét cuối trong ngày</th><th class="text-right">Storage</th><th class="text-right">File PDF</th><th class="text-right">Dung lượng PDF</th><th class="text-right">Database</th></tr></thead><tbody id="history-rows"></tbody></table></div>
        <p class="text-muted small">Chỉ lưu mốc khi quét file đầy đủ. Không có dữ liệu trước lần quét đầu tiên; ngày không quét không được nội suy. Biến động là chênh lệch dung lượng/số file đang lưu, không phải tổng file phát sinh (file có thể bị xóa hoặc thay thế). Database là ước tính. Lịch sử thuộc riêng cài đặt hiện tại, không bao gồm dung lượng của chính file thống kê.</p>
    </section>
    <section class="mb-4">
        <h2 class="h5">Dung lượng theo thư mục</h2>
        <div class="table-responsive"><table class="table table-sm table-striped bg-white"><thead><tr><th>Thư mục</th><th class="text-right">Dung lượng</th></tr></thead><tbody id="directory-rows"></tbody></table></div>
        <p class="text-muted small">Chỉ thống kê trong storage, gồm file riêng tư, file công khai, log, cache và bản sao lưu tại đây. Không đi theo liên kết thư mục; không bao gồm file lưu bên ngoài storage hoặc dịch vụ lưu trữ đám mây. PDF là số file thực tế, không phải số phiếu.</p>
    </section>
    <section class="mb-4">
        <h2 class="h5">Database</h2><p id="database-summary"></p>
        <div class="table-responsive"><table class="table table-sm table-striped bg-white"><thead><tr><th>Bảng</th><th class="text-right">Dữ liệu</th><th class="text-right">Chỉ mục</th><th class="text-right">Tổng</th></tr></thead><tbody id="database-rows"></tbody></table></div>
        <p class="text-muted small">MySQL/MariaDB cung cấp dung lượng ước tính của dữ liệu và chỉ mục; không bao gồm binary log, file backup và dung lượng trống dùng chung của dịch vụ database. Dung lượng PDF đã nằm trong tổng storage, không cộng thêm lần nữa.</p>
    </section>
</div>
@endsection
@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('scan-storage');
    const status = document.getElementById('scan-status');
    const errors = document.getElementById('scan-errors');
    const size = value => {
        if (value === false || value === null) return 'Không xác định';
        let n = Number(value), unit = 0;
        const units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
        while (n >= 1024 && unit < units.length - 1) { n /= 1024; unit++; }
        return n.toLocaleString('vi-VN', {maximumFractionDigits: 2}) + ' ' + units[unit];
    };
    function row(target, cells) {
        const tr = document.createElement('tr');
        cells.forEach((value, i) => {
            const td = document.createElement('td');
            td.textContent = value;
            if (i) td.className = 'text-right';
            else td.style.overflowWrap = 'anywhere';
            tr.appendChild(td);
        });
        target.appendChild(tr);
    }
    const types = {signed: 'Đã ký', unsigned: 'Chưa ký', intermediate: 'Trung gian chuẩn bị ký', other: 'PDF khác'};
    const date = at => new Date(at).toLocaleString('vi-VN');
    function history(data) {
        const h = data.history;
        const growth = document.getElementById('growth-rows');
        const rows = document.getElementById('history-rows');
        const chart = document.getElementById('history-chart');
        growth.replaceChildren(); rows.replaceChildren(); chart.replaceChildren();
        document.getElementById('history-month').textContent = h?.month || '';
        document.getElementById('history-summary').textContent = !h ? 'Chưa lấy được lịch sử.'
            : !h.first ? 'Chưa có lần quét đầy đủ để làm mốc.'
            : 'Mốc đầu tiên: ' + date(h.first.at) + '. ' + (h.saved ? 'Đã lưu lần quét hiện tại.' : 'Lượt quét chưa đầy đủ, không lưu làm mốc tăng trưởng.');
        if (!h) return;
        const delta = (current, baseline, bytes) => current == null || baseline == null ? 'Chưa có mốc so sánh'
            : (current - baseline > 0 ? '+' : current - baseline < 0 ? '−' : '') + (bytes ? size(Math.abs(current - baseline)) : Math.abs(current - baseline).toLocaleString('vi-VN'));
        if (h.current) {
            [['Storage', 'bytes', true], ['Số file PDF', 'pdf_files', false], ['Dung lượng PDF', 'pdf_bytes', true], ['Database', 'database_bytes', true]].forEach(([label, key, bytes]) => {
                row(growth, [label, delta(h.current[key], h.previous?.[key], bytes), delta(h.current[key], h.first?.[key], bytes)]);
            });
            Object.entries(types).forEach(([type, label]) => {
                const current = h.current.types[type] || {files: 0, bytes: 0};
                const previous = h.previous ? h.previous.types[type] || {files: 0, bytes: 0} : null;
                const first = h.first ? h.first.types[type] || {files: 0, bytes: 0} : null;
                row(growth, ['PDF ' + label.toLowerCase(), delta(current.files, previous?.files, false) + ' file / ' + delta(current.bytes, previous?.bytes, true), delta(current.files, first?.files, false) + ' file / ' + delta(current.bytes, first?.bytes, true)]);
            });
        }
        const max = Math.max(1, ...h.daily.map(item => item.pdf_bytes));
        h.daily.forEach(item => {
            row(rows, [date(item.at), size(item.bytes), Number(item.pdf_files).toLocaleString('vi-VN'), size(item.pdf_bytes), size(item.database_bytes)]);
            const line = document.createElement('div'); line.className = 'd-flex align-items-center mb-2';
            const label = document.createElement('span'); label.style.width = '95px'; label.style.flexShrink = '0'; label.textContent = item.at.slice(0, 10).split('-').reverse().slice(0, 2).join('/');
            const track = document.createElement('div'); track.className = 'flex-grow-1 bg-light';
            const bar = document.createElement('div'); bar.className = 'bg-info'; bar.style.height = '14px'; bar.style.width = (item.pdf_bytes / max * 100) + '%'; track.appendChild(bar);
            const value = document.createElement('span'); value.className = 'small ml-2'; value.style.minWidth = '85px'; value.textContent = size(item.pdf_bytes);
            line.append(label, track, value); chart.appendChild(line);
        });
    }
    async function scan() {
        button.disabled = true;
        status.className = 'alert alert-info';
        status.textContent = 'Đang quét dung lượng, vui lòng chờ…';
        errors.replaceChildren();
        try {
            const response = await fetch(@json(route('reports.storage-usage.scan')), {
                method: 'POST', headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'}, credentials: 'same-origin'
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Không thể thống kê. Vui lòng thử lại.');
            ['files', 'pdf_files', 'bytes', 'pdf_bytes'].forEach(key => {
                document.getElementById('metric-' + key).textContent = key.includes('bytes') ? size(data[key]) : Number(data[key]).toLocaleString('vi-VN');
            });
            const percent = data.disk.used_percent;
            document.getElementById('disk-summary').textContent = 'Tổng: ' + size(data.disk.total) + ' · Còn trống: ' + size(data.disk.free) + ' · Đã dùng: ' + (percent ?? '—') + '%';
            const progress = document.getElementById('disk-progress');
            progress.style.width = (percent ?? 0) + '%';
            progress.setAttribute('aria-valuenow', percent ?? 0);
            progress.className = 'progress-bar ' + (percent >= 90 ? 'bg-danger' : 'bg-info');
            const directories = document.getElementById('directory-rows');
            directories.replaceChildren();
            Object.entries(data.groups).forEach(([name, bytes]) => row(directories, [name, size(bytes)]));
            const pdfDirectories = document.getElementById('pdf-directory-rows');
            pdfDirectories.replaceChildren();
            Object.values(data.pdf_directories || {}).sort((a, b) => b.bytes - a.bytes).forEach(group => row(pdfDirectories, [group.directory, types[group.type] || types.other, Number(group.files).toLocaleString('vi-VN'), size(group.bytes), (data.pdf_bytes ? group.bytes / data.pdf_bytes * 100 : 0).toFixed(1) + '%']));
            if (!data.pdf_files) row(pdfDirectories, ['Không tìm thấy file PDF trong phạm vi quét.', '', '', '', '']);
            history(data);
            const tables = document.getElementById('database-rows');
            tables.replaceChildren();
            data.database.tables.forEach(table => row(tables, [table.name, size(table.data_bytes), size(table.index_bytes), size(Number(table.data_bytes) + Number(table.index_bytes))]));
            document.getElementById('database-summary').textContent = data.database.available
                ? 'Dữ liệu: ' + size(data.database.data) + ' · Chỉ mục: ' + size(data.database.indexes) + ' · Tổng: ' + size(data.database.data + data.database.indexes)
                : 'Chưa lấy được thống kê database.';
            const warnings = [...data.warnings];
            if (!data.complete) warnings.unshift('Quét chưa đầy đủ hoặc đã đạt giới hạn thời gian. Các số liệu file bên dưới chỉ là phần đã quét.');
            if (percent >= 90) warnings.push('Ổ đĩa đã sử dụng từ 90% trở lên. Cần kiểm tra dung lượng trống.');
            warnings.forEach(message => {
                const alert = document.createElement('div'); alert.className = 'alert alert-warning'; alert.textContent = message; errors.appendChild(alert);
            });
            document.getElementById('storage-results').hidden = false;
            status.className = 'alert ' + (data.complete ? 'alert-success' : 'alert-warning');
            status.textContent = 'Thời điểm quét: ' + data.scanned_at + ' · Thời gian: ' + data.duration + ' giây.';
        } catch (error) {
            status.className = 'alert alert-danger';
            status.textContent = 'Không hoàn tất lượt quét. Số liệu cũ (nếu có) chưa được cập nhật. ' + error.message;
        } finally { button.disabled = false; }
    }
    button.addEventListener('click', scan);
    scan();
});
</script>
@endsection
