<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlindSystemAmgSpring32Seeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1) Создаём/находим тип системы
        $system = \App\Models\Calculator\BlindSystem::updateOrCreate(
            ['code' => 'amg_spring_32'],
            [
                'name' => 'AMG пружина 32 мм',
                'description' => null,
                'category' => 'roller',
            ]
        );

        // 2) Комплектующие системы
        $items = [
            ['position' => 1,  'name' => 'Труба 32 мм с пазом (AMG)', 'note' => null],
            ['position' => 2,  'name' => 'Механизм управления с мягкой пружиной 32 (комплект)', 'note' => null],
            ['position' => 3,  'name' => 'Автостоп пружинного механизма 32 короткий', 'note' => null],
            ['position' => 4,  'name' => 'Автостоп пружинного механизма 32 длинный', 'note' => null],
            ['position' => 5,  'name' => 'Крышка кронштейна 32', 'note' => null],
            ['position' => 6,  'name' => 'Крышка удлиненная кронштейна 32 для монтажного профиля', 'note' => null],
            ['position' => 7,  'name' => 'Профиль монтажный (AMG), универсальный', 'note' => null],
            ['position' => 8,  'name' => 'Саморез 2,9 x 6,5 мм', 'note' => null],
            ['position' => 9,  'name' => 'Кронштейн потолочный кассеты 32', 'note' => null],
            ['position' => 10, 'name' => 'Комплект потолочных кронштейнов, боковая фиксация', 'note' => null],
            ['position' => 11, 'name' => 'Комплект стеновых кронштейнов, боковая фиксация', 'note' => null],
            ['position' => 12, 'name' => 'Трос металлический', 'note' => null],
            ['position' => 13, 'name' => 'Кольцо стопорное с винтом', 'note' => null],
            ['position' => 14, 'name' => 'Рейка нижняя алюминий (AMG)', 'note' => null],
            ['position' => 15, 'name' => 'Рейка нижняя алюминий под полосу, белая', 'note' => null],
            ['position' => 16, 'name' => 'Заглушка нижней рейки', 'note' => null],
            ['position' => 17, 'name' => 'Заглушка нижней рейки, боковая фиксация', 'note' => null],
            ['position' => 18, 'name' => 'Ручка управления нижней рейки', 'note' => null],
            ['position' => 19, 'name' => 'Пластиковая полоса-фиксатор клейкая 7 мм', 'note' => null],
            ['position' => 20, 'name' => 'Полоса-фиксатор 9 мм', 'note' => null],
            ['position' => 21, 'name' => 'Лента уплотняющая 8 мм', 'note' => null],
            ['position' => 22, 'name' => 'Лента уплотняющая 7 мм', 'note' => null],
            ['position' => 23, 'name' => 'Лента уплотняющая 6 мм', 'note' => null],
        ];

        // 3) PG-safe upsert без upsert: update -> insert
        foreach ($items as $item) {
            $position = (int) $item['position'];

            DB::table('blind_components')->updateOrInsert(
                [
                    'name' => (string) $item['name'],
                    'note' => $item['note'],
                    'cost_price' => 0,
                    'retail_price' => 0,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $component = DB::table('blind_components')
                ->where('name', (string) $item['name'])
                ->where('note', $item['note'])
                ->where('cost_price', 0)
                ->where('retail_price', 0)
                ->first();

            if ($component) {
                DB::table('blind_component_system')->updateOrInsert(
                    [
                        'blind_system_id' => $system->id,
                        'position' => $position,
                    ],
                    [
                        'blind_component_id' => $component->id,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
}
