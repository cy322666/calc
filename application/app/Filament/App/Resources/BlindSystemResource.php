<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BlindSystemResource\Pages;
use App\Filament\App\Resources\BlindSystemResource\RelationManagers;
use App\Filament\App\Resources\ComponentsRelationManagerResource\RelationManagers\ComponentsRelationManager;
use App\Models\BlindSystem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BlindSystemResource extends Resource
{
    protected static ?string $model = \App\Models\Calculator\BlindSystem::class;
    protected static ?string $navigationLabel = 'Системы';
    protected static ?string $modelLabel = 'Система';
    protected static ?string $pluralModelLabel = 'Системы';
    protected static ?string $navigationGroup = 'Калькулятор';

//    protected static $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('name')->required()->maxLength(255),
            \Filament\Forms\Components\TextInput::make('code')->required()->maxLength(255)->unique(ignoreRecord: true),
            \Filament\Forms\Components\Select::make('category')
                ->options([
                    'roller' => 'Рулонные шторы',
                    'vertical' => 'Вертикальные жалюзи',
                    'horizontal' => 'Горизонтальные жалюзи',
                ])
                ->nullable(),
            \Filament\Forms\Components\Toggle::make('has_zebra_variant')
                ->label('Есть Зебра-вариант')
                ->default(false),
//            \Filament\Forms\Components\TextInput::make('base_cost_price')
//                ->label('Себестоимость (база)')
//                ->numeric()
//                ->default(0),
//            \Filament\Forms\Components\TextInput::make('base_retail_price')
//                ->label('Розница (база)')
//                ->numeric()
//                ->default(0),
            \Filament\Forms\Components\Textarea::make('description')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('code')->copyable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('category')->label('Категория')->sortable(),
                \Filament\Tables\Columns\IconColumn::make('has_zebra_variant')
                    ->label('Зебра')
                    ->boolean(),
//                \Filament\Tables\Columns\TextColumn::make('base_cost_price')->label('Себестоимость'),
//                \Filament\Tables\Columns\TextColumn::make('base_retail_price')->label('Розница'),
                \Filament\Tables\Columns\TextColumn::make('components_count')->counts('components')->label('Комплектующих'),
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
            ComponentsRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlindSystems::route('/'),
            'create' => Pages\CreateBlindSystem::route('/create'),
            'edit' => Pages\EditBlindSystem::route('/{record}/edit'),
        ];
    }
}
