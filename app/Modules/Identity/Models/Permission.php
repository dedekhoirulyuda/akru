<?php

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Permission — granular action permission.
 *
 * Blueprint §8: view, create, edit_draft, submit, approve, reject,
 * post, reverse, void, close_period, reopen_period, import, export,
 * manage_settings per module.
 */
class Permission extends Model
{
    protected $fillable = [
        'module',
        'action',
        'name',
        'description',
    ];

    public $timestamps = false;

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
