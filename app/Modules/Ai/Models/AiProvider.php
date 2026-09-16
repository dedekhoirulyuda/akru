<?php

namespace App\Modules\Ai\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProvider extends Model
{
    protected $table = 'ai_providers';

    protected $fillable = [
        'code',
        'name',
        'adapter_class',
        'is_active',
        'capability_flags_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capability_flags_json' => 'array',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(AiProviderModel::class, 'provider_id');
    }
}
