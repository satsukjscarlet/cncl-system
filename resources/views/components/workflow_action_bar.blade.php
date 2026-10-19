<style>
    .workflow-action-bar {
        position: fixed; bottom: 0; left: 0; right: 0; z-index: 1030;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 12px 20px calc(12px + env(safe-area-inset-bottom, 0px));
        background: #fff; border-top: 1px solid #d9e1e9;
        box-shadow: 0 -3px 12px rgba(0, 0, 0, .08);
    }
    .workflow-action-summary { min-width: 0; overflow-wrap: anywhere; }
    .workflow-action-buttons { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
    .workflow-action-spacer { height: 180px; }
    .workflow-action-buttons form { margin: 0; }
    .workflow-secondary-actions > summary { cursor: pointer; list-style: none; }
    .workflow-secondary-actions > summary::-webkit-details-marker { display: none; }
    .workflow-secondary-actions[open] { flex-basis: 100%; }
    .workflow-secondary-actions[open] > summary { margin-bottom: 8px; }
    .workflow-secondary-actions-body { display: flex; flex-wrap: wrap; gap: 8px; }
    .workflow-detail-table thead th { position: sticky; top: 0; z-index: 1; }
    .workflow-detail-table { max-height: 65vh; overflow: auto; }
    .workflow-detail-table table { min-width: 1050px; }
    .workflow-detail-table tbody td { vertical-align: middle; overflow-wrap: anywhere; }
    .workflow-product-name { min-width: 260px; }
    .workflow-product-search { width: 280px; max-width: 100%; }
    .workflow-detail-summary { display: flex; flex-wrap: wrap; gap: 8px 20px; padding: 12px 0; }
    .workflow-info p, .workflow-info code { overflow-wrap: anywhere; }
    @media (max-width: 767.98px) {
        .workflow-action-bar { flex-direction: column; align-items: stretch; }
        .workflow-action-buttons { justify-content: flex-start; }
        .workflow-action-buttons > .btn, .workflow-action-buttons > form { flex: 1 1 160px; }
        .workflow-action-buttons form > .btn { width: 100%; }
        .workflow-action-buttons .btn { white-space: normal; }
    }
    @media print {
        .workflow-action-bar, .workflow-action-spacer, .workflow-product-search { display: none; }
        .workflow-detail-table { max-height: none; overflow: visible; }
    }
</style>
<div class="workflow-action-spacer" id="{{ $spacerId ?? 'workflowActionSpacer' }}" aria-hidden="true"></div>
<div class="workflow-action-bar" id="{{ $barId ?? 'workflowActionBar' }}" role="region" aria-label="Thao tác xử lý">
    <div class="workflow-action-summary">
        <div class="font-weight-bold">{{ $number }}</div>
        <span class="badge {{ $status['class'] }}">{{ $status['text'] }}</span>
        @if(!empty($warning))
            <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> {{ $warning }}</span>
        @endif
    </div>
    <div class="workflow-action-buttons">
        <a href="{{ $backUrl }}" class="btn btn-default"><i class="fas fa-arrow-left"></i> Quay lại</a>
        {{ $slot }}
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const bar = document.querySelector('.workflow-action-bar');
    const spacer = document.querySelector('.workflow-action-spacer');
    const content = document.querySelector('.content-wrapper');
    if (bar && spacer && content) {
        function syncBar() {
            const bounds = content.getBoundingClientRect();
            bar.style.left = Math.max(0, bounds.left) + 'px';
            bar.style.right = Math.max(0, window.innerWidth - bounds.right) + 'px';
            spacer.style.height = (bar.getBoundingClientRect().height + 16) + 'px';
        }
        syncBar();
        window.addEventListener('resize', syncBar);
        if (window.ResizeObserver) {
            const observer = new ResizeObserver(syncBar);
            observer.observe(content);
            observer.observe(bar);
        }
        if (window.jQuery) window.jQuery(document).on('shown.lte.pushmenu collapsed.lte.pushmenu', syncBar);
    }
    document.querySelectorAll('[data-product-search]').forEach(function (input) {
        const table = document.getElementById(input.dataset.productSearch);
        if (!table) return;
        const rows = Array.from(table.querySelectorAll('tbody tr[data-product-text]'));
        const counter = document.getElementById(input.dataset.productSearch + 'Count');
        const emptyRow = document.createElement('tr');
        const emptyCell = document.createElement('td');
        emptyCell.colSpan = table.querySelectorAll('thead th').length;
        emptyCell.className = 'text-center text-muted py-3';
        emptyCell.textContent = 'Không tìm thấy sản phẩm phù hợp.';
        emptyRow.appendChild(emptyCell);
        emptyRow.hidden = true;
        table.querySelector('tbody').appendChild(emptyRow);
        input.addEventListener('input', function () {
            const keyword = input.value.trim().toLocaleLowerCase('vi');
            let visible = 0;
            rows.forEach(function (row) {
                row.hidden = !row.dataset.productText.toLocaleLowerCase('vi').includes(keyword);
                if (!row.hidden) visible++;
            });
            if (counter) counter.textContent = visible + ' / ' + rows.length + ' dòng';
            emptyRow.hidden = visible > 0 || keyword === '';
        });
    });
});
</script>
