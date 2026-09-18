@php
    $currentSort = request('sort', $defaultSort ?? 'created_at');
    $currentDirection = request('direction', $defaultDirection ?? 'desc');
    $isActive = $currentSort === $column;
    $nextDirection = $isActive && $currentDirection === 'asc' ? 'desc' : 'asc';
    $query = array_merge(request()->except(['sort', 'direction', 'page']), [
        'sort' => $column,
        'direction' => $nextDirection,
    ]);
    $url = url()->current() . '?' . http_build_query($query);
    $icon = $isActive
        ? ($currentDirection === 'asc' ? 'fa-sort-up text-primary' : 'fa-sort-down text-primary')
        : 'fa-sort text-muted';
@endphp

<a class="sort-link" href="{{ $url }}">
    {{ $label }} <i class="fas {{ $icon }}"></i>
</a>
