<?php

namespace App\Modules\MasterData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DimensionValue extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(Dimension::class);
    }
}
