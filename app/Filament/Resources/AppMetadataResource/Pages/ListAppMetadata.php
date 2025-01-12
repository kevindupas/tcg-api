<?php

namespace App\Filament\Resources\AppMetadataResource\Pages;

use App\Filament\Resources\AppMetadataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppMetadata extends ListRecords
{
    protected static string $resource = AppMetadataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
