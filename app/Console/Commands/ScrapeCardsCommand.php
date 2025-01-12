<?php
// app/Console/Commands/ScrapeCardsCommand.php
namespace App\Console\Commands;

use App\Jobs\ScrapeCardsJob;
use Illuminate\Console\Command;

class ScrapeCardsCommand extends Command
{
    protected $signature = 'scrape:cards {extension} {--start=1} {--end=}';
    protected $description = 'Scrape les cartes d\'une extension donnée';

    public function handle()
    {
        $extensionId = $this->argument('extension');
        $startFromId = $this->option('start');
        $endAtId = $this->option('end');

        // Exécution synchrone
        dispatch_sync(new ScrapeCardsJob($extensionId, $startFromId, $endAtId));

        $this->info("Job de scraping lancé pour l'extension {$extensionId}");
        $this->info("Début: carte #{$startFromId}");
        if ($endAtId) {
            $this->info("Fin: carte #{$endAtId}");
        } else {
            $this->info("Fin: dernière carte");
        }
    }
}
