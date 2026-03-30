<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BlindComponentResource\Pages;
use App\Models\Calculator\BlindComponent;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class BlindComponentResource extends Resource
{
    protected static ?string $model = BlindComponent::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Компоненты';
    protected static ?string $modelLabel = 'Компонент';
    protected static ?string $pluralModelLabel = 'Компоненты';
    protected static ?string $navigationGroup = 'Калькулятор';

    public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255),
            \Filament\Forms\Components\Textarea::make('note')
                ->label('Примечание')
                ->rows(3),
            \Filament\Forms\Components\Section::make('Цены')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('cost_price')
                        ->label('Себестоимость')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('retail_price')
                        ->label('Розница')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('price_opt')
                        ->label('ОПТ')
                        ->numeric()
                        ->default(0),
                    \Filament\Forms\Components\TextInput::make('price_opt1')
                        ->label('ОПТ 1')
                        ->numeric()
                        ->default(0),
                    \Filament\Forms\Components\TextInput::make('price_opt2')
                        ->label('ОПТ 2')
                        ->numeric()
                        ->default(0),
                    \Filament\Forms\Components\TextInput::make('price_opt3')
                        ->label('ОПТ 3')
                        ->numeric()
                        ->default(0),
                    \Filament\Forms\Components\TextInput::make('price_opt4')
                        ->label('ОПТ 4')
                        ->numeric()
                        ->default(0),
                    \Filament\Forms\Components\TextInput::make('price_vip')
                        ->label('ВИП')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('cost_price')
                    ->label('Себестоимость')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('retail_price')
                    ->label('Розница')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('price_opt')
                    ->label('ОПТ')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('price_opt1')
                    ->label('ОПТ 1')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('price_opt2')
                    ->label('ОПТ 2')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('price_opt3')
                    ->label('ОПТ 3')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('price_opt4')
                    ->label('ОПТ 4')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('price_vip')
                    ->label('ВИП')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('systems_count')
                    ->counts('systems')
                    ->label('Используется в системах'),
                \Filament\Tables\Columns\TextColumn::make('note')
                    ->label('Примечание')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()->label('Редактировать'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlindComponents::route('/'),
            'create' => Pages\CreateBlindComponent::route('/create'),
            'edit' => Pages\EditBlindComponent::route('/{record}/edit'),
        ];
    }
}
