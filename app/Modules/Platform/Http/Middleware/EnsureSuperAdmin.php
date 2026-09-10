<?php

namespace App\Modules\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Akses Ditolak: Portal ini hanya dapat diakses oleh Pemilik Platform (Super Admin).');
        }

        return $next($request);
    }
}
