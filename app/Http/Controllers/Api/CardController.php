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
        // On récupère d'abord la dernière version publiée
        $latestVersion = $this->getLatestPublishedVersion();

        if (!$latestVersion) {
            return response()->json([
                'error' => 'No published version available'
            ], 404);
        }

        $cards = Card::with(['extension', 'rarity', 'boosters'])
            ->where('version_added', '<=', $latestVersion->version)
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

        return response()->json([
            'data' => CardResource::collection($cards),
            'metadata' => [
                'version' => $latestVersion->version,
                'total_cards' => $cards->count()
            ]
        ]);
    }

    public function updates(Request $request)
    {
        $currentVersion = $request->query('version');

        if (!$currentVersion) {
            return response()->json([
                'error' => 'Version parameter is required'
            ], 400);
        }

        $latestVersion = $this->getLatestPublishedVersion();

        if (!$latestVersion) {
            return response()->json([
                'error' => 'No published version available'
            ], 404);
        }

        // Si la version actuelle est plus récente ou égale à la dernière version publiée
        if (version_compare($currentVersion, $latestVersion->version, '>=')) {
            return response()->json([
                'data' => [],
                'metadata' => [
                    'version' => $latestVersion->version,
                    'needs_update' => false
                ]
            ]);
        }

        // Récupérer toutes les cartes ajoutées après la version actuelle du client
        // jusqu'à la dernière version publiée
        $newCards = Card::with(['extension', 'rarity', 'boosters'])
            ->where('version_added', '>', $currentVersion)
            ->where('version_added', '<=', $latestVersion->version)
            ->get();

        Log::info("Mise à jour demandée", [
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion->version,
            'new_cards_count' => $newCards->count()
        ]);

        return response()->json([
            'data' => CardResource::collection($newCards),
            'metadata' => [
                'version' => $latestVersion->version,
                'needs_update' => true,
                'new_cards_count' => $newCards->count()
            ]
        ]);
    }

    public function metadata()
    {
        $latestVersion = $this->getLatestPublishedVersion();

        if (!$latestVersion) {
            return response()->json([
                'error' => 'No published version available'
            ], 404);
        }

        return response()->json([
            'version' => $latestVersion->version,
            'total_cards' => Card::where('version_added', '<=', $latestVersion->version)->count()
        ]);
    }
}
