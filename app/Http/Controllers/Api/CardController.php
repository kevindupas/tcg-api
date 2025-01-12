<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\AppMetadata;
use App\Models\Card;
use Illuminate\Http\Request;

class CardController extends Controller
{
    private function getLatestPublishedVersion()
    {
        return AppMetadata::where('published', true)
            ->latest()
            ->first();
    }

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

        $metadata = $this->getLatestPublishedVersion();

        return response()->json([
            'data' => CardResource::collection($cards),
            'metadata' => [
                'version' => $metadata ? $metadata->version : '1.0.0'
            ]
        ]);
    }

    public function updates(Request $request)
    {
        $currentVersion = $request->version;

        $newCards = Card::with(['extension', 'rarity', 'boosters'])
            ->where('version_added', '>', $currentVersion)
            ->whereExists(function ($query) use ($currentVersion) {
                $query->select('id')
                    ->from('app_metadata')
                    ->where('published', true)
                    ->where('version', '>', $currentVersion);
            })
            ->get();

        $metadata = $this->getLatestPublishedVersion();

        return response()->json([
            'data' => CardResource::collection($newCards),
            'metadata' => [
                'version' => $metadata ? $metadata->version : '1.0.0'
            ]
        ]);
    }

    public function metadata()
    {
        $metadata = $this->getLatestPublishedVersion();

        return response()->json([
            'version' => $metadata ? $metadata->version : '1.0.0',
            'total_cards' => Card::count()
        ]);
    }
}
