<?php

namespace App\Models\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlindComponentVariant extends Model
{
    protected $fillable = [
        'blind_component_id',
        'name',
        'color',
        'side',
        'material',
        'is_default',
        'price_opt',
        'price_opt1',
        'price_opt2',
        'price_opt3',
        'price_opt4',
        'price_vip',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'price_opt' => 'decimal:2',
        'price_opt1' => 'decimal:2',
        'price_opt2' => 'decimal:2',
        'price_opt3' => 'decimal:2',
        'price_opt4' => 'decimal:2',
        'price_vip' => 'decimal:2',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(BlindComponent::class, 'blind_component_id');
    }
}

