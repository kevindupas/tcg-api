<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use App\Models\Carte;
use App\Models\CarteRarete;
use App\Models\Extension;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PromoScraperService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'verify' => false,
            'timeout' => 30,
        ]);
    }

    private function downloadImage(string $url, string $folder, string $filename): string
    {
        $storagePath = "public/{$folder}";
        $fullPath = "{$storagePath}/{$filename}";

        // Créer le dossier s'il n'existe pas
        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
        }

        // Télécharger l'image si elle n'existe pas déjà
        if (!Storage::exists($fullPath)) {
            $response = $this->client->get($url);
            Storage::put($fullPath, (string) $response->getBody());
        }

        return $filename;
    }

    public function scrapePromo(string $extensionId)
    {
        $extensionSlug = 'promo-' . strtolower($extensionId);
        $response = $this->client->get('https://www.pokekalos.fr/jeux/mobile/pocket/cartodex/extensions/' . $extensionSlug . '.html');
        $html = (string) $response->getBody();
        $crawler = new Crawler($html);

        // Créer l'extension si elle n'existe pas
        $extension = Extension::updateOrCreate(
            ['nom_francais' => 'Promo-' . strtoupper($extensionId)],
            [
                'nom_anglais' => 'Promo-' . strtoupper($extensionId),
                'nombre_cartes' => 33,
                'date_sortie' => '2024-10-30',
                'logo_path' => $this->downloadImage(
                    'https://www.media.pokekalos.fr/img/jeux/pocket/extensions/' . $extensionSlug . '/logo.png',
                    'extensions',
                    'logo_' . $extensionSlug . '.png'
                )
            ]
        );

        // Récupérer toutes les cartes
        $crawler->filter('#liste_cartes .carte')->each(function ($carte) use ($extension, $extensionSlug) {
            $nom = trim($carte->filter('.carte_nom')->text());
            $imageUrl = $carte->filter('.carte_image img')->attr('src');
            $numero = trim($carte->filter('.carte_rarete div')->first()->text());
            $numero = preg_replace('/[^0-9]/', '', $numero);

            // Télécharger l'image de la carte
            $imagePath = $this->downloadImage(
                $imageUrl,
                'cartes',
                'carte_' . $extensionSlug . '_' . str_pad($numero, 3, '0', STR_PAD_LEFT) . '.png'
            );

            // Récupérer l'obtention ou la rareté
            $infoNode = $carte->filter('.carte_rarete div')->eq(1);
            $rarete = [];
            $obtention = null;

            if ($infoNode->filter('.carte_icone')->count() > 0) {
                // C'est une rareté
                $nbDiamants = $infoNode->filter('.carte_icone[src*="diamant"]')->count();
                if ($nbDiamants > 0) {
                    $rarete = ['type' => 'diamant', 'nombre' => $nbDiamants];
                }

                // Télécharger les icônes si besoin
                $infoNode->filter('.carte_icone')->each(function ($icone) {
                    $iconeSrc = $icone->attr('src');
                    if (str_contains($iconeSrc, 'diamant')) {
                        $this->downloadImage(
                            $iconeSrc,
                            'icones',
                            'icone_diamant.png'
                        );
                    }
                });
            } else {
                // C'est une obtention
                $obtention = trim($infoNode->text());
            }

            // Sauvegarder la carte
            $carteModel = Carte::updateOrCreate(
                [
                    'extension_id' => $extension->id,
                    'numero' => $numero
                ],
                [
                    'nom' => $nom,
                    'obtention' => $obtention,
                    'image_path' => $imagePath
                ]
            );

            // Supprimer les anciennes raretés avant d'ajouter les nouvelles
            CarteRarete::where('carte_id', $carteModel->id)->delete();

            // Sauvegarder la rareté si elle existe
            if (!empty($rarete)) {
                CarteRarete::create([
                    'carte_id' => $carteModel->id,
                    'type_rarete' => $rarete['type'],
                    'nombre' => $rarete['nombre']
                ]);
            }
        });
    }
}
