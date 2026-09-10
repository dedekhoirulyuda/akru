<?php

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Traits\HasCompanyScope;

/**
 * Role — RBAC role within a company.
 *
 * Blueprint §8: Permissions separate view, create, edit_draft,
 * submit, approve, reject, post, reverse, void, close_period,
 * reopen_period, import, export, manage_settings.
 */
class Role extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users()
    {
        return $this->hasMany(\App\Models\User::class, 'company_users', 'role_id');
    }
}
