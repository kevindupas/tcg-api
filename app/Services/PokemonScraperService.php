<?php

namespace App\Services;

use App\Models\Card;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use App\Models\Carte;
use App\Models\CarteRarete;
use App\Models\Extension;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// class PokemonScraperService
// {
//     private Client $client;
//     private const BASE_URL = 'https://www.pokekalos.fr';
//     private const MEDIA_URL = 'https://www.media.pokekalos.fr';

//     private array $rarityMapping = [
//         'diamant' => ['min' => 1, 'max' => 4],
//         'etoile' => ['min' => 1, 'max' => 3],
//         'couronne' => ['min' => 1, 'max' => 1],
//     ];

//     public function __construct()
//     {
//         $this->client = new Client([
//             'verify' => false,
//             'timeout' => 30,
//             'http_errors' => false
//         ]);
//     }

//     /**
//      * Point d'entrée principal pour le scraping
//      */

//     public function scrapeAll(array $options = []): array
//     {
//         $extensions = $this->getExtensionsList();
//         $results = [];

//         // Filtre des extensions
//         if (!empty($options['extension'])) {
//             $extensions = array_filter($extensions, function ($ext) use ($options) {
//                 return $ext['nom_francais'] === $options['extension'];
//             });
//         }

//         foreach ($extensions as $extension) {
//             $results[$extension['nom_francais']] = $this->scrapeExtension($extension);
//         }

//         return $results;
//     }

//     /**
//      * Récupère la liste des extensions disponibles
//      */
//     private function getExtensionsList(): array
//     {
//         $response = $this->client->get(self::BASE_URL . '/jeux/mobile/pocket/cartodex/extensions.html');
//         $crawler = new Crawler((string) $response->getBody());
//         $extensions = [];

//         // Traiter toutes les tables (extensions normales et promos)
//         $crawler->filter('#content table')->each(function (Crawler $table) use (&$extensions) {
//             $table->filter('tr')->each(function (Crawler $row, $i) use (&$extensions) {
//                 if ($i === 0) return; // Skip header

//                 $cells = $row->filter('td');
//                 if ($cells->count() >= 5) {
//                     $logo = $cells->eq(0)->filter('img')->attr('src');
//                     $nomFrancais = trim($cells->eq(1)->text());
//                     $url = $cells->eq(1)->filter('a')->attr('href');

//                     $extensions[] = [
//                         'logo' => $logo,
//                         'nom_francais' => $nomFrancais,
//                         'nom_anglais' => trim($cells->eq(2)->text()),
//                         'nombre_cartes' => (int) $cells->eq(3)->text(),
//                         'date_sortie' => $this->formatDate($cells->eq(4)->text()),
//                         'url' => $url,
//                         'is_promo' => Str::contains(strtolower($nomFrancais), 'promo')
//                     ];
//                 }
//             });
//         });

//         return $extensions;
//     }

//     /**
//      * Scrape une extension spécifique
//      */
//     private function scrapeExtension(array $extensionData): array
//     {
//         $extensionSlug = $this->getExtensionSlug($extensionData['nom_francais']);

//         // Créer ou mettre à jour l'extension
//         $extension = Extension::updateOrCreate(
//             ['nom_francais' => $extensionData['nom_francais']],
//             [
//                 'nom_anglais' => $extensionData['nom_anglais'],
//                 'nombre_cartes' => $extensionData['nombre_cartes'],
//                 'date_sortie' => $extensionData['date_sortie'],
//                 'logo_path' => $this->downloadImage(
//                     $extensionData['logo'],
//                     'extensions',
//                     "logo_{$extensionSlug}.png"
//                 )
//             ]
//         );

//         // Récupérer les cartes de l'extension
//         $cartes = $this->getCartesList($extensionData['url'], $extensionSlug);

//         foreach ($cartes as $carteData) {
//             $this->processCarteData($carteData, $extension);
//         }

//         return [
//             'extension' => $extension,
//             'cartes_count' => count($cartes)
//         ];
//     }

//     /**
//      * Récupère la liste des cartes d'une extension
//      */
//     private function getCartesList(string $url, string $extensionSlug): array
//     {
//         $fullUrl = self::BASE_URL . '/jeux/mobile/pocket/cartodex/' . $url;
//         $response = $this->client->get($fullUrl);
//         $crawler = new Crawler((string) $response->getBody());
//         $cartes = [];

//         $crawler->filter('.carte')->each(function (Crawler $carte) use (&$cartes, $extensionSlug) {
//             try {
//                 // Extraire les infos de base
//                 $nom = trim($carte->filter('.carte_nom')->text());
//                 $numero = null;
//                 $rarete = [];
//                 $obtention = null;

//                 // Analyser la div de rareté
//                 $rareteDiv = $carte->filter('.carte_rarete');

//                 // Premier div pour le numéro
//                 $numeroText = trim($rareteDiv->filter('div')->first()->text());
//                 $numero = $this->extractNumero($numeroText);

//                 // Deuxième div pour la rareté ou l'obtention
//                 $infoDiv = $rareteDiv->filter('div')->last();

//                 // Test s'il y a des icônes de rareté
//                 $icones = $infoDiv->filter('img.carte_icone');
//                 if ($icones->count() > 0) {
//                     $rarete = $this->processRarete($infoDiv); // Utiliser la méthode existante
//                     $obtention = null;
//                 } else {
//                     // Si pas d'icône, c'est une obtention
//                     $obtention = trim($infoDiv->text());
//                     $rarete = [];
//                 }

//                 Log::info("Carte traitée", [
//                     'numero' => $numero,
//                     'nom' => $nom,
//                     'rarete_count' => count($rarete),
//                     'obtention' => $obtention
//                 ]);

//                 $cartes[] = [
//                     'nom' => $nom,
//                     'numero' => $numero,
//                     'image_url' => $carte->filter('.carte_image img')->attr('src'),
//                     'rarete' => $rarete,
//                     'obtention' => $obtention,
//                     'image_path' => $this->getImagePath($extensionSlug, $numero)
//                 ];
//             } catch (\Exception $e) {
//                 Log::error("Erreur lors du traitement d'une carte: " . $e->getMessage());
//             }
//         });

//         return $cartes;
//     }

//     /**
//      * Traite les données d'une carte et les sauvegarde
//      */
//     private function processCarteData(array $carteData, Extension $extension): void
//     {
//         try {
//             // Debug log pour voir les données entrantes
//             Log::info("Traitement carte " . $carteData['numero'], [
//                 'rarete' => $carteData['rarete'],
//                 'obtention' => $carteData['obtention']
//             ]);

//             // Télécharger l'image
//             $imagePath = $this->downloadImage(
//                 $carteData['image_url'],
//                 'cartes',
//                 $carteData['image_path']
//             );

//             // Créer ou mettre à jour la carte
//             $carte = Card::updateOrCreate(
//                 [
//                     'extension_id' => $extension->id,
//                     'numero' => $carteData['numero']
//                 ],
//                 [
//                     'nom' => $carteData['nom'],
//                     'image_path' => $imagePath,
//                     'obtention' => $carteData['obtention']
//                 ]
//             );

//             // Gérer les raretés
//             if (!empty($carteData['rarete'])) {
//                 Log::info("Ajout des raretés pour la carte " . $carteData['numero'], [
//                     'rarete' => $carteData['rarete']
//                 ]);

//                 // Supprimer les anciennes raretés
//                 $deleted = Ra::where('carte_id', $carte->id)->delete();
//                 Log::info("Anciennes raretés supprimées: " . $deleted);

//                 foreach ($carteData['rarete'] as $type => $nombre) {
//                     $rarete = CarteRarete::create([
//                         'carte_id' => $carte->id,
//                         'type_rarete' => $type,
//                         'nombre' => $nombre
//                     ]);
//                     Log::info("Rareté créée", [
//                         'carte_id' => $carte->id,
//                         'type' => $type,
//                         'nombre' => $nombre,
//                         'id' => $rarete->id
//                     ]);
//                 }
//             } else {
//                 Log::info("Pas de rareté pour la carte " . $carteData['numero']);
//             }
//         } catch (\Exception $e) {
//             Log::error("Erreur lors du traitement de la carte {$carteData['numero']}: " . $e->getMessage());
//             throw $e;
//         }
//     }

//     /**
//      * Traite les icônes de rareté
//      */
//     private function processRarete(Crawler $infoDiv): array
//     {
//         $rarete = [];
//         // Vérifier chaque type de rareté
//         foreach (['diamant', 'etoile', 'couronne'] as $type) {
//             $count = $infoDiv->filter("img.carte_icone[src*='{$type}']")->count();
//             if ($count > 0) {
//                 $rarete[$type] = $count;
//                 // Télécharger l'icône
//                 $this->downloadImage(
//                     self::MEDIA_URL . "/img/jeux/pocket/icones/{$type}.png",
//                     'icones',
//                     "icone_{$type}.png"
//                 );
//             }
//         }
//         return $rarete;
//     }

//     /**
//      * Utilitaires
//      */
//     private function formatDate(string $date): string
//     {
//         $parts = explode('/', $date);
//         return count($parts) === 3 ? "{$parts[2]}-{$parts[1]}-{$parts[0]}" : $date;
//     }

//     private function getExtensionSlug(string $nom): string
//     {
//         return Str::slug(preg_replace('/^\[[^\]]+\]\s*/', '', $nom));
//     }

//     private function extractNumero(string $text): string
//     {
//         if (preg_match('/^(\d+)\s*\//', $text, $matches)) {
//             return $matches[1];
//         }
//         // Fallback au cas où
//         return preg_replace('/[^0-9]/', '', $text);
//     }

//     private function getImagePath(string $extensionSlug, string $numero): string
//     {
//         return sprintf(
//             'carte_%s_%s.png',
//             $extensionSlug,
//             str_pad($numero, 3, '0', STR_PAD_LEFT)
//         );
//     }

//     private function downloadImage(string $url, string $folder, string $filename): string
//     {
//         try {
//             if (!Str::startsWith($url, ['http://', 'https://'])) {
//                 $url = self::MEDIA_URL . $url;
//             }

//             $storagePath = "public/{$folder}";
//             $fullPath = "{$storagePath}/{$filename}";

//             if (!Storage::exists($storagePath)) {
//                 Storage::makeDirectory($storagePath);
//             }

//             if (!Storage::exists($fullPath)) {
//                 $response = $this->client->get($url);
//                 if ($response->getStatusCode() === 200) {
//                     Storage::put($fullPath, (string) $response->getBody());
//                 }
//             }

//             return $filename;
//         } catch (\Exception $e) {
//             Log::error("Erreur de téléchargement pour {$url}: " . $e->getMessage());
//             throw $e;
//         }
//     }
// }
