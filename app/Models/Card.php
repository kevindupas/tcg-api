<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Card extends Model
{
    protected $fillable = [
        'extension_id',
        'name_fr',
        'name_en',
        'image',
        'number',
        'rarity_type',
        'rarity_number'
    ];

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    public function boosters(): BelongsToMany
    {
        return $this->belongsToMany(Booster::class, 'booster_card');
    }

    public function rarity(): BelongsTo
    {
        return $this->belongsTo(Rarity::class, 'rarity_type');
    }
}
