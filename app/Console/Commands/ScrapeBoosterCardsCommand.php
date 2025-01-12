<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeBoosterCardsJob;
use Illuminate\Console\Command;

class ScrapeBoosterCardsCommand extends Command
{
    protected $signature = 'scrape:booster-cards {extension} {--start=1} {--end=}';
    protected $description = 'Associe les cartes aux boosters pour une extension donnée';

    public function handle()
    {
        $extensionId = $this->argument('extension');
        $startFromId = $this->option('start');
        $endAtId = $this->option('end');

        // Exécution synchrone
        dispatch_sync(new ScrapeBoosterCardsJob($extensionId, $startFromId, $endAtId));

        $this->info("Association cartes-boosters terminée pour l'extension {$extensionId}");
        $this->info("Début: carte #{$startFromId}");
        if ($endAtId) {
            $this->info("Fin: carte #{$endAtId}");
        } else {
            $this->info("Fin: dernière carte");
        }
    }
}
