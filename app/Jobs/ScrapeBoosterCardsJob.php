<?php
// app/Jobs/ScrapeBoosterCardsJob.php
namespace App\Jobs;

use App\Models\Booster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeBoosterCardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $extensionId,
        protected int $startFromId = 1,
        protected ?int $endAtId = null
    ) {}

    public function handle(): void
    {
        $boosters = Booster::where('extension_id', $this->extensionId)->get();

        foreach ($boosters as $booster) {
            Log::info("Traitement du booster : " . $booster->name_fr);

            $response = Http::get($booster->url);
            $crawler = new Crawler($response->body());

            $crawler->filter('#liste_cartes .carte')->each(function (Crawler $carte) use ($booster) {
                try {
                    // Extraire le numéro
                    $rareteText = $carte->filter('.carte_rarete div')->first()->text();
                    preg_match('/(\d+)\s*\/\s*\d+/', $rareteText, $matches);
                    if (!isset($matches[1])) {
                        Log::error("Impossible d'extraire le numéro de la carte : " . $rareteText);
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

                    Log::info("Association de la carte {$number} avec le booster " . $booster->name_fr);

                    // Trouver la carte correspondante
                    $card = \App\Models\Card::where('extension_id', $this->extensionId)
                        ->where('number', (string)$number)
                        ->first();

                    if ($card) {
                        // Ajouter la relation si elle n'existe pas déjà
                        if (!$booster->cards()->where('card_id', $card->id)->exists()) {
                            $booster->cards()->attach($card->id);
                            Log::info("Carte {$card->name_fr} (#{$number}) associée au booster {$booster->name_fr}");
                        }
                    } else {
                        Log::warning("Carte #{$number} non trouvée pour l'extension {$this->extensionId}");
                    }
                } catch (\Exception $e) {
                    Log::error("Erreur lors du traitement d'une carte : " . $e->getMessage());
                }
            });
        }
    }
}
