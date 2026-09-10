<?php

namespace App\Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AiChatUsage extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'usage_date',
        'message_count',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'message_count' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the number of AI chat messages used today by a company
     */
    public static function getTodayUsage(int $companyId): int
    {
        $today = now()->format('Y-m-d');

        $record = static::where('company_id', $companyId)
            ->whereDate('usage_date', $today)
            ->first();

        return $record ? (int) $record->message_count : 0;
    }

    /**
     * Increment and record an AI chat usage for today
     */
    public static function recordUsage(int $companyId, ?int $userId = null): int
    {
        $today = now()->format('Y-m-d');

        $record = static::where('company_id', $companyId)
            ->whereDate('usage_date', $today)
            ->first();

        if ($record) {
            $record->increment('message_count');
            return (int) $record->message_count;
        }

        try {
            $record = static::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'usage_date' => $today,
                'message_count' => 1,
            ]);
            return 1;
        } catch (\Throwable $e) {
            // In case of race condition / concurrent insert, fallback to query and increment
            $record = static::where('company_id', $companyId)
                ->whereDate('usage_date', $today)
                ->first();
            if ($record) {
                $record->increment('message_count');
                return (int) $record->message_count;
            }
            throw $e;
        }
    }
}
