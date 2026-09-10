<?php

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * HasCompanyScope — enforces mandatory company_id isolation.
 *
 * Applied as a global scope so every query on the model is automatically
 * filtered by the active company. This is a critical security measure
 * to prevent data leakage between companies (Blueprint §1.9, §11.6).
 */
trait HasCompanyScope
{
    /**
     * Boot the trait — add global scope for company isolation.
     */
    protected static function bootHasCompanyScope(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if ($companyId = static::resolveCompanyId()) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $model->company_id = static::resolveCompanyId();
            }
        });
    }

    /**
     * Resolve the current company ID from context.
     */
    protected static function resolveCompanyId(): ?int
    {
        // Priority: explicit context > session > null
        if (app()->bound('akru.company_id')) {
            return app('akru.company_id');
        }

        if (session()->has('active_company_id')) {
            return session('active_company_id');
        }

        return null;
    }

    /**
     * Scope query to a specific company (bypass global scope).
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->withoutGlobalScope('company')
            ->where($this->getTable() . '.company_id', $companyId);
    }
}
