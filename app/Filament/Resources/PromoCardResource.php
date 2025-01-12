<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCardResource\Pages;
use App\Filament\Resources\PromoCardResource\RelationManagers;
use App\Models\PromoCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromoCardResource extends Resource
{
    protected static ?string $model = PromoCard::class;

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
                    ->label('Type de Rareté'),
                Forms\Components\TextInput::make('rarity_number')
                    ->numeric()
                    ->label('Nombre de Rareté'),
                Forms\Components\TextInput::make('obtention')
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
                Tables\Columns\TextColumn::make('obtention')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPromoCards::route('/'),
            'create' => Pages\CreatePromoCard::route('/create'),
            'edit' => Pages\EditPromoCard::route('/{record}/edit'),
        ];
    }
}
