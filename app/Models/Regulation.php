<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'level',
        'code',
        'number',
        'year',
        'title',
        'about',
        'status',
        'effective_date',
        'summary',
        'key_points',
        'accounting_implications',
        'tax_implications',
        'keywords',
        'official_source_url',
    ];

    protected $casts = [
        'key_points' => 'array',
        'keywords' => 'array',
        'effective_date' => 'date',
        'year' => 'integer',
    ];

    /**
     * Scope query by category.
     */
    public function scopeCategory($query, ?string $category)
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    /**
     * Scope query by search keyword.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        $term = '%' . strtolower(trim($search)) . '%';
        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', $term)
              ->orWhere('title', 'like', $term)
              ->orWhere('about', 'like', $term)
              ->orWhere('summary', 'like', $term)
              ->orWhere('accounting_implications', 'like', $term)
              ->orWhere('tax_implications', 'like', $term)
              ->orWhereJsonContains('keywords', strtolower(trim($term, '%')));
        });
    }
}
