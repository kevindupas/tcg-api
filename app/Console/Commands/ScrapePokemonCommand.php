<?php

namespace App\Console\Commands;

use App\Services\PokemonScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapePokemonCommand extends Command
{
    protected $signature = 'app:scrape-pokemon 
                            {--extension=* : Nom de l\'extension à scraper}
                            {--all : Scraper toutes les extensions}';

    protected $description = 'Scrape les données du Pokémon TCG';

    public function handle(PokemonScraperService $scraper)
    {
        $this->info('Début du scraping...');
        $this->newLine();

        try {
            $options = [];
            if ($extension = $this->option('extension')) {
                $options['extension'] = $extension[0]; // Prendre la première extension spécifiée
            }

            $results = $scraper->scrapeAll($options);

            foreach ($results as $extension => $result) {
                $this->info("Extension : {$extension}");
                $this->info("- Cartes traitées : {$result['cartes_count']}");
                $this->newLine();
            }

            $this->info('Scraping terminé avec succès !');
        } catch (\Exception $e) {
            $this->error('Une erreur est survenue : ' . $e->getMessage());
            Log::error('Erreur lors du scraping : ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
