<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\AppMetadata;
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

        $metadata = AppMetadata::first();

        return response()->json([
            'data' => CardResource::collection($cards),
            'metadata' => [
                'version' => $metadata ? $metadata->version : '1.0.0'
            ]
        ]);
    }

    public function metadata()
    {
        $metadata = AppMetadata::first();

        return response()->json([
            'version' => $metadata ? $metadata->version : '1.0.0'
        ]);
    }
}
