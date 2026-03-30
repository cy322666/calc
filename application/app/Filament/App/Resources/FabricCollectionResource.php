<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\FabricCollectionResource\Pages;
use App\Models\Calculator\FabricCollection;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class FabricCollectionResource extends Resource
{
    protected static ?string $model = FabricCollection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Коллекции тканей';
    protected static ?string $modelLabel = 'Коллекция тканей';
    protected static ?string $pluralModelLabel = 'Коллекции тканей';
    protected static ?string $navigationGroup = 'Калькулятор';
    protected static ?string $navigationParentItem = 'Компоненты';

    public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            \Filament\Forms\Components\Select::make('type')
                ->options([
                    'standard' => 'Standard',
                    'zebra' => 'Zebra',
                ])
                ->default('standard')
                ->required(),
            \Filament\Forms\Components\TextInput::make('weight_factor')
                ->label('Вес (kg/m2)')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('type')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('weight_factor')->label('Вес'),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFabricCollections::route('/'),
            'create' => Pages\CreateFabricCollection::route('/create'),
            'edit' => Pages\EditFabricCollection::route('/{record}/edit'),
        ];
    }
}
