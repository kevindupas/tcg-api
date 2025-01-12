<?php

namespace App\Filament\Resources\CardResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BoostersRelationManager extends RelationManager
{
    protected static string $relationship = 'boosters';
    protected static ?string $recordTitleAttribute = 'name_fr';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name_fr')
                ->required()
                ->disabled()
                ->label('Nom du Booster'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_fr')
                    ->label('Nom du Booster')
                    ->searchable(),
                Tables\Columns\TextColumn::make('extension.name_fr')
                    ->label('Extension'),
                // Tables\Columns\ToggleColumn::make('is_attached')
                //     ->label('Lié')
                //     ->onColor('success')
                //     ->offColor('danger'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn(Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
