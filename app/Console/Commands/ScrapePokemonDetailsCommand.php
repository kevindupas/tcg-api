<?php

namespace App\Console\Commands;

use App\Models\Carte;
use App\Services\PokemonDetailScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScrapePokemonDetailsCommand extends Command
{
    protected $signature = 'pokemon:scrape-details {--carte-numero=} {--extension-id=}';
    protected $description = 'Scrape les détails des cartes Pokémon';

    private PokemonDetailScraperService $detailScraper;

    public function __construct(PokemonDetailScraperService $detailScraper)
    {
        parent::__construct();
        $this->detailScraper = $detailScraper;
    }

    public function handle(): void
    {
        $this->info('Début du scraping des détails...');

        try {
            $query = Carte::query();

            // Filtrage optionnel par carte_id
            if ($carteId = $this->option('carte-numero')) {
                $query->where('numero', $carteId);
                Log::info("Filtrage par carte_id : {$carteId}");
            }

            // Filtrage optionnel par extension_id
            if ($extensionId = $this->option('extension-id')) {
                $query->where('extension_id', $extensionId);
                Log::info("Filtrage par extension_id : {$extensionId}");
            }

            $cartes = $query->get();
            Log::info("Nombre de cartes à traiter : " . $cartes);
            $total = $cartes->count();

            if ($total === 0) {
                $this->error('Aucune carte trouvée avec les critères spécifiés.');
                return;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            foreach ($cartes as $carte) {
                try {
                    DB::beginTransaction();

                    $this->line("\nTraitement de la carte : {$carte->nom} (Numero: {$carte->numero})");

                    // Construction de l'URL de la carte
                    // Construction de l'URL de la carte
                    // Construction de l'URL de la carte
                    $extension = $carte->extension;
                    $nomExtension = preg_replace('/^\[[^\]]+\]\s*/', '', $extension->nom_francais);
                    Log::info("Nom de l'extension après nettoyage des crochets : " . $nomExtension);
                    $extensionSlug = Str::of($nomExtension)
                        ->lower()
                        ->replace(['\'', '\''], '-')
                        ->slug();
                    Log::info("Slug généré : " . $extensionSlug);

                    $url = "/jeux/mobile/pocket/cartodex/extensions/{$extensionSlug}/cartes/{$carte->numero}.html";
                    Log::info("Traitement de la carte {$carte->numero} : " . url($url));

                    // Scraping des détails
                    $this->detailScraper->scrapeCarteDetails($carte, $url);

                    DB::commit();
                    $this->info("✓ Carte traitée avec succès");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("✗ Erreur lors du traitement de la carte {$carte->numero}: " . $e->getMessage());
                    Log::error("Erreur lors du scraping de la carte {$carte->numero}: " . $e->getMessage());

                    if ($this->confirm('Voulez-vous continuer avec les cartes suivantes ?')) {
                        continue;
                    } else {
                        break;
                    }
                }

                $bar->advance();

                // Petite pause pour éviter de surcharger le serveur
                usleep(500000); // 0.5 seconde de pause
            }

            $bar->finish();
            $this->info("\nScraping des détails terminé !");
        } catch (\Exception $e) {
            $this->error("Une erreur est survenue : " . $e->getMessage());
            Log::error("Erreur globale lors du scraping des détails: " . $e->getMessage());
        }
    }
}
