<?php

namespace App\Modules\MasterData\Models;

use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'unit_id',
        'sku',
        'name',
        'type', // goods, service
        'buy_price',
        'sell_price',
        'is_stockable',
        'is_active',
        'inventory_account_id',
        'sales_account_id',
        'cogs_account_id',
        'expense_account_id',
        'description',
    ];

    protected $casts = [
        'is_stockable' => 'boolean',
        'is_active' => 'boolean',
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
    ];

    public function inventoryBalances()
    {
        return $this->hasMany(\App\Modules\Inventory\Models\InventoryBalance::class, 'item_id');
    }

    public function salesAccount()
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function cogsAccount()
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function inventoryAccount()
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }
}
