<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppMetadataResource\Pages;
use App\Filament\Resources\AppMetadataResource\RelationManagers;
use App\Models\AppMetadata;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppMetadataResource extends Resource
{
    protected static ?string $model = AppMetadata::class;
    protected static ?string $navigationLabel = 'Version App';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('version')
                    ->required()
                    ->helperText('Numéro de version (ex: 0.0.6)'),

                Forms\Components\Toggle::make('published')
                    ->label('Publier')
                    ->helperText('Activer pour rendre cette version disponible aux utilisateurs')
                    ->default(false)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version'),
                Tables\Columns\IconColumn::make('published')
                    ->boolean()
                    ->label('Publiée'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Dernière modification')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppMetadata::route('/'),
            'create' => Pages\CreateAppMetadata::route('/create'),
            'edit' => Pages\EditAppMetadata::route('/{record}/edit'),
        ];
    }
}
