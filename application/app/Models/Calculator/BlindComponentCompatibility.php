<?php

namespace App\Models\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlindComponentCompatibility extends Model
{
    protected $fillable = [
        'blind_system_id',
        'component_type',
        'value',
        'label',
        'cost_price',
        'retail_price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(BlindSystem::class, 'blind_system_id');
    }

    public function getOptionLabelAttribute(): string
    {
        return $this->label ?: $this->value;
    }
}
