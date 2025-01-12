<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_fr' => $this->name_fr,
            'name_en' => $this->name_en,
            'number' => $this->number,
            'image' => asset('storage/' . $this->image),
            'rarity_number' => $this->rarity_number,
            'extension' => new ExtensionResource($this->whenLoaded('extension')),
            'rarity' => new RarityResource($this->whenLoaded('rarity')),
            'boosters' => BoosterResource::collection($this->whenLoaded('boosters')),
        ];
    }
}
