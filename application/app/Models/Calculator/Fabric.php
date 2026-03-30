<?php

namespace App\Models\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fabric extends Model
{
    protected $fillable = [
        'fabric_collection_id',
        'name',
        'weight_factor',
        'price_per_m2',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(FabricCollection::class, 'fabric_collection_id');
    }
}
