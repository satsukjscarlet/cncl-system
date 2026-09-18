<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function sortInput(
        Request $request,
        array $allowedSorts,
        string $defaultSort = 'created_at',
        string $defaultDirection = 'desc'
    ): array {
        $sort = in_array($request->input('sort'), $allowedSorts, true)
            ? $request->input('sort')
            : $defaultSort;

        $direction = strtolower((string) $request->input('direction')) === 'asc'
            ? 'asc'
            : $defaultDirection;

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        return [$sort, $direction];
    }
}
