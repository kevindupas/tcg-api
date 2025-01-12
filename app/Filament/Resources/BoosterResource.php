<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoosterResource\Pages;
use App\Models\Booster;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BoosterResource extends Resource
{
    protected static ?string $model = Booster::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('extension_id')
                ->relationship('extension', 'name_fr')
                ->required()
                ->label('Extension'),
            Forms\Components\FileUpload::make('image')
                ->required()
                ->image()
                ->directory('boosters'),
            Forms\Components\TextInput::make('name_fr')
                ->required()
                ->label('Nom Français'),
            Forms\Components\TextInput::make('name_en')
                ->required()
                ->label('Nom Anglais'),
            Forms\Components\TextInput::make('url')
                ->required()
                ->url(),
            Forms\Components\Toggle::make('promo')
                ->label('Booster Promo'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('extension.name_fr')
                    ->label('Extension')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_fr')
                    ->label('Nom Français')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Nom Anglais'),
                Tables\Columns\IconColumn::make('promo')
                    ->boolean()
                    ->label('Promo'),
            ])
            ->filters([
                Tables\Filters\Filter::make('promo')
                    ->query(fn($query) => $query->where('promo', true))
                    ->label('Boosters Promo'),
                Tables\Filters\SelectFilter::make('extension_id')
                    ->relationship('extension', 'name_fr')
                    ->label('Extension'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoosters::route('/'),
            'create' => Pages\CreateBooster::route('/create'),
            'edit' => Pages\EditBooster::route('/{record}/edit'),
        ];
    }
}
