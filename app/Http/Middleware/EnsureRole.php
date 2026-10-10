<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $employee = $request->user();
        $employee?->loadMissing('role');

        if (! $employee || ! $employee->hasRole(...$roles)) {
            abort(403, 'Your role cannot access this page.');
        }

        return $next($request);
    }
}
