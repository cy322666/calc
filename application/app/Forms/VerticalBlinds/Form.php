<?php

namespace App\Forms\VerticalBlinds;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

abstract class Form
{
    public static string $prefix = 'ver_';

    public static string $typeName = 'type_vertical_blinds';

    static array $conditions = [
        //Потолочный кронштейн
        //Стеновой кронштейн
        //Грувер
        'tkanevye' => [
            'ver_width',
            'ver_height',
            'ver_side',
            'ver_ceiling_bracket',
            'ver_wall_bracket',
            'ver_textile_color',
            'ver_textile_name',
            'ver_stove',
        ],
        'plastikovye' => [
            'ver_width',
            'ver_height',
            'ver_side',
            'ver_ceiling_bracket',
            'ver_wall_bracket',
            'ver_textile_color',
            'ver_textile_name',
            'ver_stove',
        ],
    ];

    public static function all(Get $get): array
    {
        return $get('type_vertical_blinds') != '' ? [

            TextInput::make('ver_width')
                ->label('Ширина по габаритам изделия')
                ->numeric()
                ->visible(fn(Get $get) => in_array('ver_width', static::$conditions[$get('type_vertical_blinds')])),

            TextInput::make('ver_height')
                ->label('Высота по габаритам изделия')
                ->numeric()
                ->visible(fn(Get $get) => in_array('ver_height', static::$conditions[$get('type_vertical_blinds')])),


            Radio::make('ver_side')
                ->label('Сторона управления')
                ->options([
                    'from_management' => 'От управления',
                    'to_management' => 'К управлению',
                    'from_center' => 'От центра',
                    'to_center' => 'К центру',
                ])
                ->visible(fn(Get $get) => in_array('ver_side', static::$conditions[$get('type_vertical_blinds')])),

            Select::make('ver_textile_color')
                ->label('Цвет ткани')
                ->options([
                    'white' => 'Белый',
                    'black' => 'Черный',
                    'red'   => 'Красный',
                ])
                ->visible(fn(Get $get) => in_array('ver_textile_color', static::$conditions[$get('type_vertical_blinds')])),

            Select::make('ver_textile_name')
                ->label('Название ткани')
                ->options([
                    'white' => 'Белый',
                    'black' => 'Черный',
                    'red'   => 'Красный',
                ])
                ->visible(fn(Get $get) => in_array('ver_textile_name', static::$conditions[$get('type_vertical_blinds')])),

            Radio::make('ver_ceiling_bracket')
                ->label('Потолочный кронштейн')
                ->options([
                    'normal' => 'Обычный',
                    'armstrong' => 'Армстронг',
                ])
                ->visible(fn(Get $get) => in_array('ver_ceiling_bracket', static::$conditions[$get('type_vertical_blinds')])),

            Radio::make('ver_wall_bracket')
                ->label('Стеновой кронштейн')
                ->options([
                    'no'  => 'Не нужен',
                    'yes' => 'Нужен',
                ])
                ->reactive()
                ->visible(fn(Get $get) => in_array('ver_wall_bracket', static::$conditions[$get('type_vertical_blinds')])),

            Radio::make('ver_stove')
                ->label('Грувер')
                ->options([
                    'no'  => 'Не нужен',
                    'yes' => 'Нужен',
                ])
                ->reactive()
                ->visible(fn(Get $get) => in_array('ver_stove', static::$conditions[$get('type_vertical_blinds')])),
        ] : [];
    }
}
