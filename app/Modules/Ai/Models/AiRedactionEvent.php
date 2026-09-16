<?php

namespace App\Modules\Ai\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

class AiRedactionEvent extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_redaction_events';

    protected $fillable = [
        'company_id',
        'request_id',
        'rule_code',
        'field_type',
        'action',
        'count',
    ];

    protected $casts = [
        'count' => 'integer',
    ];
}
