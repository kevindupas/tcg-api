<?php

namespace App\Console\Commands;

use App\Services\PromoScraperService;
use Illuminate\Console\Command;

class ScrapePromoCommand extends Command
{
    protected $signature = 'app:scrape-promo {id=a : ID de la promo (ex: a, b, etc.)}';
    protected $description = 'Scrape une extension promo spécifique';

    public function handle(PromoScraperService $scraper)
    {
        $promoId = $this->argument('id');
        $this->info("Scraping de la Promo-" . strtoupper($promoId));

        try {
            $scraper->scrapePromo($promoId);
            $this->info('Scraping terminé avec succès !');
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
        }
    }
}
