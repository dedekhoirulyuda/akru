<?php

namespace App\Modules\Partner\Models;

use App\Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerWorkspace extends Model
{
    protected $guarded = ['id'];

    public function clients(): HasMany
    {
        return $this->hasMany(PartnerClient::class);
    }
}
