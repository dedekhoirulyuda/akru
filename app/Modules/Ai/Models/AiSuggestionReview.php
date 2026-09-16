<?php

namespace App\Modules\Ai\Models;

use App\Models\User;
use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSuggestionReview extends Model
{
    use HasCompanyScope;

    protected $table = 'ai_suggestion_reviews';

    protected $fillable = [
        'company_id',
        'suggestion_id',
        'reviewer_id',
        'decision',
        'edited_payload_json',
        'comment',
        'converted_draft_type',
        'converted_draft_id',
    ];

    protected $casts = [
        'edited_payload_json' => 'array',
    ];

    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(AiSuggestion::class, 'suggestion_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
