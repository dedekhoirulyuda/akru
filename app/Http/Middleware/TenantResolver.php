<?php

namespace App\Http\Middleware;

use App\Modules\Core\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantResolver — resolves and binds the active company context.
 *
 * Blueprint §11.3: Every request goes through tenant resolver and
 * company/branch scope middleware.
 *
 * This middleware ensures that all subsequent database queries
 * are automatically scoped to the active company.
 */
class TenantResolver
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $this->resolveCompanyId($request);

        if ($companyId) {
            // Bind to container for HasCompanyScope trait
            app()->instance('akru.company_id', $companyId);
            session(['active_company_id' => $companyId, 'current_company_id' => $companyId]);

            // Verify user has access to this company if authenticated
            if (auth()->check()) {
                $user = auth()->user();
                $hasAccess = $user->companies()
                    ->where('companies.id', $companyId)
                    ->where('company_users.is_active', true)
                    ->exists();

                if (!$hasAccess) {
                    abort(403, 'Anda tidak memiliki akses ke perusahaan ini.');
                }

                $currentCompany = Company::find($companyId);
                view()->share('currentCompany', $currentCompany);
                view()->share('userCompanies', $user->companies);
            }
        }

        return $next($request);
    }

    /**
     * Resolve company ID from various sources.
     */
    private function resolveCompanyId(Request $request): ?int
    {
        // 1. Explicit request header
        if ($request->hasHeader('X-Company-Id')) {
            return (int) $request->header('X-Company-Id');
        }

        // 2. Route parameter (e.g. /companies/{company})
        if ($request->route('company')) {
            $comp = $request->route('company');
            return is_object($comp) ? $comp->id : (int) $comp;
        }

        // 3. Session
        if (session()->has('active_company_id')) {
            return (int) session('active_company_id');
        }

        if (session()->has('current_company_id')) {
            return (int) session('current_company_id');
        }

        // 4. Authenticated user's preference or first company
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->current_company_id) {
                return (int) $user->current_company_id;
            }

            $firstCompany = $user->companies()->first();
            if ($firstCompany) {
                return (int) $firstCompany->id;
            }
        }

        return null;
    }
}
