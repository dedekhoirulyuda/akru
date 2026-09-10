<?php

namespace App\Modules\Platform\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'is_secret',
        'description',
    ];

    protected $casts = [
        'is_secret' => 'boolean',
    ];

    /**
     * Get setting value by key with optional default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key
     */
    public static function set(string $key, mixed $value, string $group = 'general', bool $isSecret = false, ?string $description = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'is_secret' => $isSecret,
                'description' => $description,
            ]
        );
    }
}
