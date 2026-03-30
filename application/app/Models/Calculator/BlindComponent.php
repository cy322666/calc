<?php

namespace App\Models\Calculator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlindComponent extends Model
{
    protected $fillable = [
        'name',
        'note',
        'cost_price',
        'retail_price',
        'price_opt',
        'price_opt1',
        'price_opt2',
        'price_opt3',
        'price_opt4',
        'price_vip',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'price_opt' => 'decimal:2',
        'price_opt1' => 'decimal:2',
        'price_opt2' => 'decimal:2',
        'price_opt3' => 'decimal:2',
        'price_opt4' => 'decimal:2',
        'price_vip' => 'decimal:2',
    ];

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(BlindSystem::class, 'blind_component_system')
            ->withPivot(['id', 'position', 'created_at', 'updated_at'])
            ->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(BlindComponentVariant::class)->orderBy('name');
    }
}
