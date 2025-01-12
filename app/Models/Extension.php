<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Extension extends Model
{
    protected $fillable = [
        'image',
        'name_fr',
        'name_en',
        'card_number',
        'release_date',
        'promo',
        'url'
    ];

    protected $casts = [
        'release_date' => 'date',
        'promo' => 'boolean'
    ];

    public function boosters(): HasMany
    {
        return $this->hasMany(Booster::class);
    }
}
