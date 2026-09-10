<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'timezone',
        'locale',
        'is_superadmin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    // --- AKRU Relationships ---

    /**
     * Companies this user belongs to (multi-company support).
     * Blueprint §2.1: One account can access multiple companies.
     */
    public function companies()
    {
        return $this->belongsToMany(
            \App\Modules\Core\Models\Company::class,
            'company_users'
        )->withPivot('role_id', 'is_active')->withTimestamps();
    }

    /**
     * Get the user's active company (from session/context).
     */
    public function activeCompany()
    {
        $companyId = session('active_company_id');
        if (!$companyId) return null;

        return $this->companies()->where('companies.id', $companyId)->first();
    }
}
