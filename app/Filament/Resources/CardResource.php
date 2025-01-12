<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CardResource\Pages;
use App\Filament\Resources\CardResource\RelationManagers;
use App\Models\Card;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CardResource extends Resource
{
    protected static ?string $model = Card::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('extension_id')
                    ->relationship('extension', 'name_fr')
                    ->required()
                    ->label('Extension'),
                Forms\Components\TextInput::make('name_fr')
                    ->required()
                    ->label('Nom Français'),
                Forms\Components\TextInput::make('name_en')
                    ->label('Nom Anglais'),
                Forms\Components\TextInput::make('number')
                    ->required()
                    ->label('Numéro'),
                Forms\Components\FileUpload::make('image')
                    ->required()
                    ->image()
                    ->directory('cards'),
                Forms\Components\Select::make('rarity_type')
                    ->relationship('rarity', 'name')
                    ->required()
                    ->label('Type de Rareté'),
                Forms\Components\TextInput::make('rarity_number')
                    ->required()
                    ->numeric()
                    ->label('Nombre de Rareté'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('number')
                    ->label('Numéro')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_fr')
                    ->label('Nom Français')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Nom Anglais')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rarity.name')
                    ->label('Rareté'),
                Tables\Columns\TextColumn::make('rarity_number')
                    ->label('Nombre de Rareté'),
            ])
            // ->filters([
            //     Tables\Filters\SelectFilter::make('booster_id')
            //         ->relationship('booster', 'name_fr')
            //         ->label('Booster'),
            //     Tables\Filters\SelectFilter::make('rarity_type')
            //         ->relationship('rarity', 'name')
            //         ->label('Rareté'),
            // ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BoostersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCards::route('/'),
            'create' => Pages\CreateCard::route('/create'),
            'edit' => Pages\EditCard::route('/{record}/edit'),
        ];
    }
}
