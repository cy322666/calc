<?php

namespace App\Models\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FabricCollection extends Model
{
    protected $fillable = [
        'name',
        'type',
        'weight_factor',
    ];

    public function fabrics(): HasMany
    {
        return $this->hasMany(Fabric::class);
    }
}
