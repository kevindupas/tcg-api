<?php

namespace App\Filament\Resources\PromoCardResource\Pages;

use App\Filament\Resources\PromoCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromoCards extends ListRecords
{
    protected static string $resource = PromoCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
