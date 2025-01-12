<?php

namespace App\Services;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeCardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $extensionId,
        protected int $startFromId = 1,
        protected ?int $endAtId = null
    ) {}

    public function handle(): void
    {
        $extension = \App\Models\Extension::findOrFail($this->extensionId);

        $response = Http::get($extension->url);
        $crawler = new Crawler($response->body());

        $crawler->filter('#liste_cartes .carte')->each(function (Crawler $carte) use ($extension) {
            // Extraire le numéro
            $rareteText = $carte->filter('.carte_rarete div')->first()->text();
            preg_match('/(\d+)\s*\/\s*\d+/', $rareteText, $matches);
            if (!isset($matches[1])) {
                logger()->error("Impossible d'extraire le numéro de la carte : " . $rareteText);
                return;
            }
            $number = (int)$matches[1];

            // Vérifier si on doit traiter cette carte
            if ($number < $this->startFromId) {
                return;
            }
            if ($this->endAtId && $number > $this->endAtId) {
                return;
            }

            // Extraire les autres informations
            $name = trim($carte->filter('.carte_nom')->text());

            // Compter les icônes de rareté
            $iconElements = $carte->filter('.carte_icone');
            $rarityType = 1; // Par défaut diamant
            if ($iconElements->count() > 0) {
                $firstIconSrc = $iconElements->first()->attr('src');
                if (str_contains($firstIconSrc, 'etoile')) {
                    $rarityType = 2;
                } elseif (str_contains($firstIconSrc, 'couronne')) {
                    $rarityType = 3;
                }
            }
            $rarityNumber = $iconElements->count();

            // Télécharger et sauvegarder l'image
            $imageUrl = $carte->filter('.carte_image img')->attr('src');
            $imageContent = file_get_contents($imageUrl);
            $uniqueFilename = Str::random(10) . '.png';
            Storage::disk('public')->put("cards/{$uniqueFilename}", $imageContent);

            // Créer la carte
            Card::create([
                'extension_id' => $extension->id,
                'name_fr' => $name,
                'number' => (string)$number,
                'image' => "cards/{$uniqueFilename}",
                'rarity_type' => $rarityType,
                'rarity_number' => $rarityNumber
            ]);

            logger()->info("Carte créée : {$name} (#{$number})");
        });
    }
}
