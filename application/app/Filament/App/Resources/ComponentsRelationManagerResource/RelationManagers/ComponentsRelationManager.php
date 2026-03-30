<?php

namespace App\Filament\App\Resources\ComponentsRelationManagerResource\RelationManagers;

use App\Models\Calculator\BlindComponent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('note')
                ->rows(3)
                ->nullable(),
            Forms\Components\TextInput::make('cost_price')
                ->label('Себестоимость')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('retail_price')
                ->label('Розница')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->defaultSort('blind_component_system.position')
            ->columns([
                Tables\Columns\TextColumn::make('pivot.position')->label('Позиция')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('cost_price')->label('Себестоимость'),
                Tables\Columns\TextColumn::make('retail_price')->label('Розница'),
                Tables\Columns\TextColumn::make('note')->limit(50)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\Action::make('attachExisting')
                    ->label('Добавить компонент')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('component_id')
                            ->label('Компонент')
                            ->options(fn () => BlindComponent::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('position')
                            ->label('Позиция')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $this->ownerRecord->components()->syncWithoutDetaching([
                            (int) $data['component_id'] => ['position' => (int) $data['position']],
                        ]);
                    }),
                Tables\Actions\Action::make('createAndAttach')
                    ->label('Создать и добавить')
                    ->icon('heroicon-o-document-plus')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('note')
                            ->label('Примечание')
                            ->rows(3),
                        Forms\Components\TextInput::make('cost_price')
                            ->label('Себестоимость')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('retail_price')
                            ->label('Розница')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('position')
                            ->label('Позиция')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $component = BlindComponent::query()->create([
                            'name' => (string) $data['name'],
                            'note' => $data['note'] ?? null,
                            'cost_price' => (float) $data['cost_price'],
                            'retail_price' => (float) $data['retail_price'],
                        ]);

                        $this->ownerRecord->components()->attach($component->id, [
                            'position' => (int) $data['position'],
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('note')
                            ->label('Примечание')
                            ->rows(3),
                        Forms\Components\TextInput::make('cost_price')
                            ->label('Себестоимость')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('retail_price')
                            ->label('Розница')
                            ->numeric()
                            ->required(),
                    ]),
            ])
            ->bulkActions([]);
    }
}
