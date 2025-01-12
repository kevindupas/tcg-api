<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PromoCard extends Model
{
    protected $fillable = [
        'name_fr',
        'name_en',
        'image',
        'number',
        'extension_id',
        'rarity_type',
        'rarity_number',
        'obtention'
    ];

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    public function boosters(): BelongsToMany
    {
        return $this->belongsToMany(Booster::class, 'booster_promo_card');
    }

    public function rarity(): BelongsTo
    {
        return $this->belongsTo(Rarity::class, 'rarity_type');
    }
}
