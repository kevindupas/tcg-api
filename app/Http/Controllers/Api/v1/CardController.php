<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Models\AppMetadata;
use App\Models\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardController extends Controller
{
    /**
     * Obtenir toutes les cartes de la version actuelle
     */
    public function index(): JsonResponse
    {
        $latestVersion = AppMetadata::where('published', true)
            ->latest()
            ->first();

        if (!$latestVersion) {
            return response()->json([
                'error' => 'Aucune version publiée disponible'
            ], 404);
        }

        $cards = Card::with(['extension', 'rarity', 'boosters'])
            ->where('version_added', '<=', $latestVersion->version)
            ->get();

        return response()->json([
            'data' => CardResource::collection($cards),
            'metadata' => [
                'version' => $latestVersion->version,
                'total_cards' => $cards->count(),
                'generated_at' => now()->toIso8601String()
            ]
        ]);
    }

    /**
     * Obtenir les mises à jour depuis une version spécifique
     */
    public function updates(Request $request): JsonResponse
    {
        $request->validate([
            'version' => 'required|string'
        ]);

        $currentVersion = $request->version;
        $latestVersion = AppMetadata::where('published', true)
            ->latest()
            ->first();

        if (!$latestVersion) {
            return response()->json([
                'error' => 'Aucune version publiée disponible'
            ], 404);
        }

        // Si la version actuelle est plus récente ou égale
        if (version_compare($currentVersion, $latestVersion->version, '>=')) {
            return response()->json([
                'data' => [],
                'metadata' => [
                    'version' => $latestVersion->version,
                    'needs_update' => false,
                    'message' => 'Vous avez déjà la dernière version'
                ]
            ]);
        }

        // Récupérer les nouvelles cartes
        $newCards = Card::with(['extension', 'rarity', 'boosters'])
            ->where('version_added', '>', $currentVersion)
            ->where('version_added', '<=', $latestVersion->version)
            ->get();

        Log::info('Mise à jour demandée', [
            'client_version' => $currentVersion,
            'server_version' => $latestVersion->version,
            'new_cards' => $newCards->count()
        ]);

        return response()->json([
            'data' => CardResource::collection($newCards),
            'metadata' => [
                'version' => $latestVersion->version,
                'needs_update' => true,
                'new_cards_count' => $newCards->count(),
                'generated_at' => now()->toIso8601String()
            ]
        ]);
    }
}
