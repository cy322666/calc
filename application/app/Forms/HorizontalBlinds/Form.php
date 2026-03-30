<?php

namespace App\Forms\HorizontalBlinds;

use App\Models\Calculator\BlindSystem;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

abstract class Form
{
    public static string $prefix = 'hor_';

    public static string $typeName = 'type_horizontal_blinds';

    static array $conditions = [

        'al' => [
            'hor_width',
            'hor_height',
            'hor_size',
            'hor_side',
            'hor_color',
            'hor_fixator'
        ],
        'wood' => [
            'hor_width',
            'hor_height',
            'hor_type',
            'hor_size',
            'hor_side',
            'hor_color',
            'hor_fixator'
        ],
        'param' => [
            'hor_width',
            'hor_height',
            'hor_type',
            'hor_size',
            'hor_side',
            'hor_color',
            'hor_fixator'
        ],
    ];

    public static function all(Get $get): array
    {
        return $get('type_horizontal_blinds') != '' ? [

            TextInput::make('hor_width')
                ->label('Ширина по габаритам изделия')
                ->numeric()
                ->visible(fn(Get $get) => in_array('hor_width', static::$conditions[$get('type_horizontal_blinds')])),

            TextInput::make('hor_height')
                ->label('Высота по габаритам изделия')
                ->numeric()
                ->visible(fn(Get $get) => in_array('hor_height', static::$conditions[$get('type_horizontal_blinds')])),


            Select::make('hor_side')
                ->label('Сторона управления')
                ->options([
                    'from_management' => 'От управления',
                    'to_management' => 'К управлению',
                    'from_center' => 'От центра',
                    'to_center' => 'К центру',
                ])
                ->visible(fn(Get $get) => in_array('hor_side', static::$conditions[$get('type_horizontal_blinds')])),

            Select::make('hor_textile_color')
                ->label('Цвет ткани')
                ->options([
                    'white' => 'Белый',
                    'black' => 'Черный',
                    'red'   => 'Красный',
                ])
                ->visible(fn(Get $get) => in_array('hor_textile_color', static::$conditions[$get('type_horizontal_blinds')])),

            Select::make('hor_textile_name')
                ->label('Название ткани')
                ->options([
                    'white' => 'Белый',
                    'black' => 'Черный',
                    'red'   => 'Красный',
                ])
                ->visible(fn(Get $get) => in_array('hor_textile_name', static::$conditions[$get('type_horizontal_blinds')])),

            Radio::make('hor_ceiling_bracket')
                ->label('Потолочный кронштейн')
                ->options([
                    'normal' => 'Обычный',
                    'armstrong' => 'Армстронг',
                ])
                ->visible(fn(Get $get) => in_array('hor_ceiling_bracket', static::$conditions[$get('type_horizontal_blinds')])),

            Radio::make('hor_wall_bracket')
                ->label('Стеновой кронштейн')
                ->options([
                    'no'  => 'Не нужен',
                    'yes' => 'Нужен',
                ])
                ->reactive()
                ->visible(fn(Get $get) => in_array('hor_wall_bracket', static::$conditions[$get('type_horizontal_blinds')])),

            Radio::make('hor_stove')
                ->label('Грувер')
                ->options([
                    'no'  => 'Не нужен',
                    'yes' => 'Нужен',
                ])
                ->reactive()
                ->visible(fn(Get $get) => in_array('hor_stove', static::$conditions[$get('type_horizontal_blinds')])),
        ] : [];
    }

    public static function getForm()
    {
        return [
            Select::make('type_horizontal_blinds')
                ->label('Тип')
                ->options([
                    'al' => 'Алюминиевые',
                    'wood' => 'Деревянные',
                    'param' => 'Параметры',
                ])
//                ->searchable()
                ->reactive(),

            \Filament\Forms\Components\Grid::make(1)
                ->schema(function ($get) {

                    if ($get('type_horizontal_blinds')) {

                       if ($get('type_horizontal_blinds') == 'al') {

                           return [

                           ];
                       }

                        if ($get('type_horizontal_blinds') == 'wood') {

                            return [
                                Select::make('sub_type')
                                    ->label('Подтип')
                                    ->options([
                                        'bambyk' => 'Бамбук',
                                        'wood' => 'Дерево',
                                    ])
                                ->reactive(),

                                Select::make('bambyk_size')
                                    ->label('Диаметр')
                                    ->options([
                                        '25' => '25 mm',
                                        '50' => '50 mm',
                                    ])
                                    ->visible(fn (Get $get) => $get('sub_type') == 'bambyk')
                                    ->reactive(),

                                Select::make('wood_size')
                                    ->label('Диаметр')
                                    ->options([
                                        '25' => '25 mm',
                                        '50' => '50 mm',
                                    ])
                                    ->visible(fn (Get $get) => $get('sub_type') == 'wood')
                                    ->reactive(),
                            ];
                        }


                        if ($get('type_horizontal_blinds') == 'param') {

                            return [
                                Select::make('control_side')
                                    ->label('Сторона управления')
                                    ->options([
                                        'left' => 'Лево',
                                        'right' => 'Право',
                                    ])
                                    ->reactive(),

                                Select::make('bottom_clamp')
                                    ->label('Нижний фикстатор')
                                    ->options([
                                        'yes' => 'Нужен',
                                        'no' => 'Не нужен',
                                    ])
                                    ->reactive(),

                                Select::make('color')
                                    ->label('Цвет')
                                    ->options([
                                        'red' => 'Красный',
                                        'black' => 'Черный',
                                        'green' => 'Зеленый',
                                    ])
                                    ->reactive(),
                            ];
                        }

                    } else
                        return [];
                })
//            Select::make('type_2_rolled_curtains')
//                ->label('Подтип')
//                ->options(function(Get $get) {
//
//                    if ($get('type_rolled_curtains')) {
//
//                        $parent = Category::query()->find($get('type_rolled_curtains'));
//
//                        return Category::query()
//                            ->where('parent_id', $parent->id)//TODO
//                            ->where('is_visible', true)
//                            ->pluck('name', 'slug');
//                    }
//                })
//                ->reactive()
//                ->visible(fn(Get $get) => $get('type_rolled_curtains') !== ''),
        ];
    }
}
