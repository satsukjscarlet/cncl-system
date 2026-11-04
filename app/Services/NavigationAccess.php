<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class NavigationAccess
{
    // Navigation filtering only. Controllers and middleware remain the authority for data/state checks.
    public function allows(User $user, string $url): bool
    {
        try {
            $route = app('router')->getRoutes()->match(Request::create($url, 'GET'));
        } catch (HttpExceptionInterface) {
            return false;
        }
        foreach ($route->gatherMiddleware() as $middleware) {
            [$name, $arguments] = array_pad(explode(':', $middleware, 2), 2, '');
            $ability = explode(',', $arguments)[0];
            if ($name === 'permission' && ! $user->canAny(explode('|', $ability))) {
                return false;
            }
            if ($name === 'can' && ! $user->can($ability)) {
                return false;
            }
            if ($name === 'role' && ! $user->hasAnyRole(explode('|', $ability))) {
                return false;
            }
        }

        return true;
    }
}
