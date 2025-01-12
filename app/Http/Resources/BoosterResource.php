<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoosterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_fr' => $this->name_fr,
            'name_en' => $this->name_en,
            'image' => asset('storage/' . $this->image),
            'is_promo' => (bool)$this->promo,
        ];
    }
}
