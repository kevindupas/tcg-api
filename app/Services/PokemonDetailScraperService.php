<?php

namespace App\Services;

use App\Models\Carte;
use App\Models\CarteDetail;
use App\Models\Type;
use App\Models\Talent;
use App\Models\Attaque;
use App\Models\Booster;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PokemonDetailScraperService
{
    private Client $client;
    private string $baseUrl = 'https://www.pokekalos.fr';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'verify' => false,
            'timeout' => 30,
        ]);
    }

    public function scrapeCarteDetails(Carte $carte, string $url): void
    {
        try {
            Log::info("Récupération des détails pour la carte ID {$carte->numero}");

            $response = $this->client->get($url);
            $html = (string) $response->getBody();
            $crawler = new Crawler($html);

            $donneesDiv = $crawler->filter('.ui.grey.message')->first();

            // Récupération des informations de base
            $nomFr = $this->extractTextAfter($donneesDiv, 'Nom français :');
            $nomEn = $this->extractTextAfter($donneesDiv, 'Nom anglais :');
            $illustrateur = $this->extractTextAfter($donneesDiv, 'Illustration :');

            // Log pour debug
            Log::info("Données extraites pour la carte {$carte->numero}:", [
                'nom_francais' => $nomFr,
                'nom_anglais' => $nomEn,
                'illustrateur' => $illustrateur,
                'html' => $donneesDiv->outerHtml()
            ]);

            // Si on ne trouve pas le nom français, on utilise le nom de la carte
            if (empty($nomFr)) {
                $nomFr = $carte->nom;
                Log::warning("Nom français non trouvé pour la carte {$carte->numero}, utilisation du nom de la carte: {$nomFr}");
            }

            // Si on ne trouve pas le nom anglais, on utilise le nom français
            if (empty($nomEn)) {
                $nomEn = $nomFr;
                Log::warning("Nom anglais non trouvé pour la carte {$carte->numero}, utilisation du nom français");
            }

            // Si on ne trouve pas l'illustrateur, on met "Inconnu"
            if (empty($illustrateur)) {
                $illustrateur = "Inconnu";
                Log::warning("Illustrateur non trouvé pour la carte {$carte->numero}");
            }

            // Détermine le type de carte (Pokémon, Dresseur: Supporter, Dresseur: Objet)
            $typeCarte = 'pokemon';
            $sousType = null;
            $carteDresseurNode = $donneesDiv->filter('.item:contains("Carte Dresseur :")');

            if ($carteDresseurNode->count() > 0) {
                $typeCarte = 'dresseur';
                $typeText = trim(str_replace('Carte Dresseur :', '', $carteDresseurNode->text()));

                if (Str::contains(strtolower($typeText), 'supporter')) {
                    $sousType = 'supporter';
                } elseif (Str::contains(strtolower($typeText), 'objet')) {
                    $sousType = 'objet';
                }
            }

            // Création ou mise à jour des détails de la carte
            $details = [
                'nom_francais' => $nomFr,
                'nom_anglais' => $nomEn,
                'illustrateur' => $illustrateur,
                'type_carte' => $typeCarte,
                'sous_type' => $sousType,
            ];

            // Si c'est un Pokémon, ajoute les détails spécifiques
            if ($typeCarte === 'pokemon') {
                $details['pv'] = (int) $this->extractTextAfter($donneesDiv, 'PV :');

                // Gestion des types
                $this->handleTypes($carte, $donneesDiv);

                // Gestion des talents
                $this->handleTalents($carte, $donneesDiv);

                // Gestion des attaques
                $this->handleAttaques($carte, $donneesDiv);
            } else {
                // Pour les cartes Dresseur/Objet, récupère la description
                $description = $donneesDiv->filter('.item[style="font-style: italic;"]')->text();
                $details['description'] = $description;
            }

            // Gestion des points boost
            $boosterPoints = $crawler->filter('.ui.grey.message')->last()->filter('.item:contains("points Booster")');
            if ($boosterPoints->count() > 0) {
                preg_match('/(\d+)\s+points Booster/', $boosterPoints->text(), $matches);
                $details['points_boost'] = isset($matches[1]) ? (int) $matches[1] : null;
            }

            // Sauvegarde les détails
            $carteDetail = CarteDetail::updateOrCreate(
                ['carte_id' => $carte->numero],
                $details
            );

            // Gestion des boosters
            $this->handleBoosters($carte, $crawler);

            // Gestion des cartes liées
            $this->handleCartesLiees($carte, $crawler);

            Log::info("Détails sauvegardés pour la carte ID {$carte->numero}");
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des détails de la carte {$carte->numero}: " . $e->getMessage());
            throw $e;
        }
    }

    private function handleTypes(Carte $carte, Crawler $div): void
    {
        // Types principaux
        $typeNode = $div->filter('.item:contains("Type :")');
        if ($typeNode->count() > 0) {
            $typeImages = $typeNode->filter('img');
            $typeImages->each(function (Crawler $img) use ($carte) {
                $src = $img->attr('src');
                preg_match('/types\/([^.]+)\.png/', $src, $matches);
                if (isset($matches[1])) {
                    $typeNom = $matches[1];
                    $type = Type::firstOrCreate(
                        ['nom' => $typeNom],
                        ['image_path' => $this->downloadImage($src, 'types', "type_{$typeNom}.png")]
                    );
                    $carte->types()->attach($type->id, ['categorie' => 'principal']);
                }
            });
        }

        // Faiblesses
        $faiblesseNode = $div->filter('.item:contains("Faiblesse :")');
        if ($faiblesseNode->count() > 0) {
            $faiblesseImages = $faiblesseNode->filter('img');
            $valeur = null;
            if (preg_match('/\+ (\d+)/', $faiblesseNode->text(), $matches)) {
                $valeur = (int) $matches[1];
            }
            $faiblesseImages->each(function (Crawler $img) use ($carte, $valeur) {
                $src = $img->attr('src');
                preg_match('/types\/([^.]+)\.png/', $src, $matches);
                if (isset($matches[1])) {
                    $typeNom = $matches[1];
                    $type = Type::firstOrCreate(
                        ['nom' => $typeNom],
                        ['image_path' => $this->downloadImage($src, 'types', "type_{$typeNom}.png")]
                    );
                    $carte->types()->attach($type->id, [
                        'categorie' => 'faiblesse',
                        'valeur' => $valeur
                    ]);
                }
            });
        }

        // Retraite
        $retraiteNode = $div->filter('.item:contains("Retraite :")');
        if ($retraiteNode->count() > 0) {
            $retraiteImages = $retraiteNode->filter('img');
            $quantite = $retraiteImages->count();
            if ($quantite > 0) {
                $src = $retraiteImages->first()->attr('src');
                preg_match('/types\/([^.]+)\.png/', $src, $matches);
                if (isset($matches[1])) {
                    $typeNom = $matches[1];
                    $type = Type::firstOrCreate(
                        ['nom' => $typeNom],
                        ['image_path' => $this->downloadImage($src, 'types', "type_{$typeNom}.png")]
                    );
                    $carte->types()->attach($type->id, [
                        'categorie' => 'retraite',
                        'quantite' => $quantite
                    ]);
                }
            }
        }
    }

    private function handleTalents(Carte $carte, Crawler $div): void
    {
        $talentNode = $div->filter('.item:contains("Talent :")');
        if ($talentNode->count() > 0) {
            $nom = trim(str_replace('Talent :', '', $talentNode->text()));
            $description = $talentNode->nextAll()->filter('.item small')->first()->text();

            Talent::updateOrCreate(
                ['carte_id' => $carte->numero],
                [
                    'nom' => $nom,
                    'description' => $description
                ]
            );
        }
    }

    private function handleAttaques(Carte $carte, Crawler $div): void
    {
        $attaqueNodes = $div->filter('.item:contains("Attaque")');

        $attaqueNodes->each(function (Crawler $node, $index) use ($carte) {
            $isAttaque2 = str_contains($node->text(), 'Attaque 2');
            $baseNode = $node->closest('.ui.grey.message');

            // Extraction du nom de l'attaque
            $nomAttaque = trim(str_replace(['Attaque :', 'Attaque 1 :', 'Attaque 2 :'], '', $node->text()));

            // Recherche des dégâts et de la description
            $degats = null;
            $description = null;

            $degatsNode = $baseNode->filter('.item:contains("Dégâts :")');
            if ($degatsNode->count() > 0) {
                preg_match('/(\d+)/', $degatsNode->text(), $matches);
                $degats = isset($matches[1]) ? (int) $matches[1] : null;
            }

            $descNode = $baseNode->filter('.item:contains("Description :")');
            if ($descNode->count() > 0) {
                $description = trim(str_replace('Description :', '', $descNode->text()));
            }

            $attaque = Attaque::updateOrCreate(
                [
                    'carte_id' => $carte->numero,
                    'nom' => $nomAttaque
                ],
                [
                    'degats' => $degats,
                    'description' => $description
                ]
            );

            // Gestion des énergies
            $energiesNode = $baseNode->filter('.item:contains("Énergies nécessaires :")');
            if ($energiesNode->count() > 0) {
                $energies = [];
                $energiesNode->filter('img')->each(function (Crawler $img) use (&$energies, $attaque) {
                    $src = $img->attr('src');
                    preg_match('/types\/([^.]+)\.png/', $src, $matches);
                    if (isset($matches[1])) {
                        $typeNom = $matches[1];
                        $type = Type::firstOrCreate(
                            ['nom' => $typeNom],
                            ['image_path' => $this->downloadImage($src, 'types', "type_{$typeNom}.png")]
                        );
                        if (!isset($energies[$typeNom])) {
                            $energies[$typeNom] = ['type' => $type, 'quantite' => 0];
                        }
                        $energies[$typeNom]['quantite']++;
                    }
                });

                foreach ($energies as $typeData) {
                    $attaque->energies()->attach($typeData['type']->id, ['quantite' => $typeData['quantite']]);
                }
            }
        });
    }

    private function handleBoosters(Carte $carte, Crawler $crawler): void
    {
        $boosterList = $crawler->filter('.ui.grey.message')->last()->filter('ul li a');
        $boosterList->each(function (Crawler $node) use ($carte) {
            $nomBooster = trim($node->text());
            $booster = Booster::firstOrCreate(
                ['nom' => $nomBooster],
                ['extension_id' => $carte->extension_id]
            );
            $carte->boosters()->syncWithoutDetaching([$booster->id]);
        });
    }

    private function handleCartesLiees(Carte $carte, Crawler $crawler): void
    {
        $cartesLiees = $crawler->filter('#liste_cartes .carte');
        $cartesLiees->each(function (Crawler $node) use ($carte) {
            $href = $node->attr('href');
            if (preg_match('/\/cartes\/(\d+)\.html$/', $href, $matches)) {
                $numeroRef = $matches[1];
                $carteLiee = Carte::where('numero', $numeroRef)
                    ->where('extension_id', $carte->extension_id)
                    ->first();

                if ($carteLiee && $carteLiee->id !== $carte->numero) {
                    $carte->cartesLiees()->syncWithoutDetaching([$carteLiee->id]);
                }
            }
        });
    }

    private function downloadImage(string $url, string $folder, string $customFilename = null): ?string
    {
        try {
            if (!Str::startsWith($url, ['http://', 'https://'])) {
                $url = 'https://www.pokekalos.fr' . $url;
            }

            $filename = $customFilename ?? basename($url);
            $storagePath = "public/{$folder}";
            $fullPath = "{$storagePath}/{$filename}";

            Log::info("Tentative de téléchargement de l'image type : {$url}");
            Log::info("Chemin de stockage : {$fullPath}");

            if (!Storage::exists($storagePath)) {
                Storage::makeDirectory($storagePath);
            }

            if (!Storage::exists($fullPath)) {
                $response = $this->client->get($url);
                $imageContent = (string) $response->getBody();
                $success = Storage::put($fullPath, $imageContent);

                if ($success) {
                    Log::info("Image type téléchargée avec succès : {$filename}");
                    return $filename;
                } else {
                    Log::error("Échec de l'enregistrement de l'image type : {$filename}");
                    return null;
                }
            }

            Log::info("Image type déjà existante : {$filename}");
            return $filename;
        } catch (\Exception $e) {
            Log::error("Erreur lors du téléchargement de l'image type {$url}: " . $e->getMessage());
            return null;
        }
    }

    private function extractTextAfter(Crawler $div, string $label): ?string
    {
        $node = $div->filter(".item:contains('{$label}')");
        if ($node->count() > 0) {
            return trim(str_replace($label, '', $node->text()));
        }
        return null;
    }
}
