<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\AppMetadata;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // Si pas de version fournie, on renvoie une erreur
        if (empty($currentVersion)) {
            return response()->json([
                'error' => 'Version parameter is required'
            ], 400);
        }

        Log::info('Version reçue: ' . $currentVersion);

        $allCards = Card::with(['extension', 'rarity', 'boosters'])->get();

        $newCards = $allCards->filter(function ($card) use ($currentVersion) {
            // S'assurer que version_added n'est pas vide
            if (empty($card->version_added)) {
                $card->version_added = '0.0.1';
            }

            Log::info("Comparaison: {$card->id} - {$card->name_fr} - Version: {$card->version_added} vs {$currentVersion}");
            $comparison = version_compare($card->version_added, $currentVersion, '>');
            Log::info("Résultat de la comparaison: " . ($comparison ? 'true' : 'false'));
            return $comparison;
        });

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
