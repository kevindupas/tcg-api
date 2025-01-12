<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\Card;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $cards = Card::with(['extension', 'rarity', 'boosters'])
            ->when($request->filled('extension'), function ($query) use ($request) {
                $query->where('extension_id', $request->extension);
            })
            ->when($request->filled('booster'), function ($query) use ($request) {
                $query->whereHas('boosters', function ($q) use ($request) {
                    $q->where('booster_id', $request->booster);
                });
            })
            ->when($request->filled('rarity'), function ($query) use ($request) {
                $query->where('rarity_type', $request->rarity);
            })
            ->get();

        return CardResource::collection($cards);
    }

    public function show(Card $card)
    {
        $card->load(['extension', 'rarity', 'boosters']);
        return new CardResource($card);
    }
}
