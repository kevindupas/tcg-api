<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booster extends Model
{
    protected $fillable = [
        'extension_id',
        'image',
        'name_fr',
        'name_en',
        'url',
        'promo'
    ];

    protected $casts = [
        'promo' => 'boolean'
    ];

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }


    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class);
    }

    public function promoCards(): BelongsToMany
    {
        return $this->belongsToMany(PromoCard::class, 'booster_promo_card');
    }
}
