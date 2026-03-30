<?php

namespace App\Models\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlindSystem extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'has_zebra_variant',
        'base_cost_price',
        'base_retail_price',
    ];

    protected $casts = [
        'has_zebra_variant' => 'boolean',
        'base_cost_price' => 'decimal:2',
        'base_retail_price' => 'decimal:2',
    ];

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(BlindComponent::class, 'blind_component_system')
            ->withPivot(['id', 'position', 'created_at', 'updated_at'])
            ->withTimestamps()
            ->orderBy('blind_component_system.position');
    }

    public function compatibilities(): HasMany
    {
        return $this->hasMany(BlindComponentCompatibility::class)->orderBy('component_type');
    }
}
