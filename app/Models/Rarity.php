<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rarity extends Model
{
    protected $fillable = [
        'name',
        'image'
    ];

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'rarity_type');
    }

    public function promoCards(): HasMany
    {
        return $this->hasMany(PromoCard::class, 'rarity_type');
    }
}
