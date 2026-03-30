<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\FabricResource\Pages;
use App\Models\Calculator\Fabric;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class FabricResource extends Resource
{
    protected static ?string $model = Fabric::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Ткани';
    protected static ?string $modelLabel = 'Ткань';
    protected static ?string $pluralModelLabel = 'Ткани';
    protected static ?string $navigationGroup = 'Калькулятор';
    protected static ?string $navigationParentItem = 'Компоненты';

    public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\Select::make('fabric_collection_id')
                ->relationship('collection', 'name')
                ->required(),
            \Filament\Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            \Filament\Forms\Components\TextInput::make('weight_factor')
                ->label('Вес (kg/m2)')
                ->numeric()
                ->default(0),
            \Filament\Forms\Components\TextInput::make('price_per_m2')
                ->label('Цена за м2')
                ->numeric()
                ->default(0),
            \Filament\Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('collection.name')->label('Коллекция'),
                \Filament\Tables\Columns\TextColumn::make('collection.type')->label('Тип'),
                \Filament\Tables\Columns\TextColumn::make('weight_factor')->label('Вес'),
                \Filament\Tables\Columns\TextColumn::make('price_per_m2')->label('Цена/м2'),
                \Filament\Tables\Columns\IconColumn::make('is_active')->boolean()->label('Активна'),
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
            'index' => Pages\ListFabrics::route('/'),
            'create' => Pages\CreateFabric::route('/create'),
            'edit' => Pages\EditFabric::route('/{record}/edit'),
        ];
    }
}
