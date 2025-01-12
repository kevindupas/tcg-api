<?php
// app/Filament/Resources/ExtensionResource.php
namespace App\Filament\Resources;

use App\Filament\Resources\ExtensionResource\Pages;
use App\Models\Extension;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;


class ExtensionResource extends Resource
{
    protected static ?string $model = Extension::class;
    protected static ?string $navigationIcon = 'heroicon-o-numbered-list';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('image')
                ->required()
                ->image()
                ->directory('extensions'),
            Forms\Components\TextInput::make('name_fr')
                ->required()
                ->label('Nom Français'),
            Forms\Components\TextInput::make('name_en')
                ->required()
                ->label('Nom Anglais'),
            Forms\Components\TextInput::make('card_number')
                ->required()
                ->numeric()
                ->label('Nombre de cartes'),
            Forms\Components\DatePicker::make('release_date')
                ->required()
                ->label('Date de sortie'),
            Forms\Components\Toggle::make('promo')
                ->label('Extension Promo'),
            Forms\Components\TextInput::make('url')
                ->required()
                ->url(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('name_fr')
                    ->label('Nom Français')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Nom Anglais'),
                Tables\Columns\TextColumn::make('card_number')
                    ->label('Nombre de cartes'),
                Tables\Columns\TextColumn::make('release_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('promo')
                    ->boolean()
                    ->label('Promo'),
            ])
            ->filters([
                Tables\Filters\Filter::make('promo')
                    ->query(fn($query) => $query->where('promo', true))
                    ->label('Extensions Promo'),
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
            'index' => Pages\ListExtensions::route('/'),
            'create' => Pages\CreateExtension::route('/create'),
            'edit' => Pages\EditExtension::route('/{record}/edit'),
        ];
    }
}
