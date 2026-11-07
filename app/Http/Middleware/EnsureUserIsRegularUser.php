<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsRegularUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'user' || ! $request->user()->is_active) {
            abort(403);
        }

        return $next($request);
    }
}
