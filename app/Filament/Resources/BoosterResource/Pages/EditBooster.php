<?php

namespace App\Filament\Resources\BoosterResource\Pages;

use App\Filament\Resources\BoosterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooster extends EditRecord
{
    protected static string $resource = BoosterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
