<?php

namespace App\Modules\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('impersonated_by')) {
            view()->share('isImpersonating', true);
            view()->share('impersonatorId', session('impersonated_by'));
            view()->share('impersonatorName', session('impersonator_name', 'Super Admin'));
        } else {
            view()->share('isImpersonating', false);
        }

        return $next($request);
    }
}
