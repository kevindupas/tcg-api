<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AppMetadata;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    /**
     * Obtenir la dernière version publiée
     */
    public function getLatestVersion(): JsonResponse
    {
        $latestVersion = AppMetadata::where('published', true)
            ->latest()
            ->first();

        if (!$latestVersion) {
            return response()->json([
                'error' => 'Aucune version publiée disponible'
            ], 404);
        }

        return response()->json([
            'version' => $latestVersion->version,
            'total_cards' => Card::where('version_added', '<=', $latestVersion->version)->count(),
            'published_at' => $latestVersion->updated_at
        ]);
    }
}
