<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExtensionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_fr' => $this->name_fr,
            'name_en' => $this->name_en,
            'image' => asset('storage/' . $this->image),
            'card_number' => $this->card_number,
            'release_date' => Carbon::parse($this->release_date)->format('d-m-Y'),
            'is_promo' => (bool)$this->promo,
        ];
    }
}
