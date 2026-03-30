<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlindSystemUniSpringSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $system = \App\Models\Calculator\BlindSystem::updateOrCreate(
            ['code' => 'uni-spring'],
            [
                'name' => 'UNI с пружинным механизмом',
                'description' => 'Комплектация для рулонных жалюзи UNI с пружинным механизмом',
                'category' => 'roller',
            ]
        );

        $items = [
            ['position' => 1, 'name' => 'Короб', 'note' => null],
            ['position' => 2, 'name' => 'Дополнительный профиль высокий', 'note' => null],
            ['position' => 3, 'name' => 'Нижняя планка для пружинного механизма UNI', 'note' => null],
            ['position' => 4, 'name' => 'Направляющая для пружинного механизма UNI, белая', 'note' => null],
            ['position' => 5, 'name' => 'Механизм управления пружинный, комплект', 'note' => null],
            ['position' => 6, 'name' => 'Труба стальная 25 мм с клейкой лентой', 'note' => 'Альтернатива: труба алюминиевая 25 мм универсальная'],
            ['position' => 7, 'name' => 'Лента клейкая двусторонняя 9 мм', 'note' => null],
            ['position' => 8, 'name' => 'Лента клейкая двусторонняя 19 мм', 'note' => null],
            ['position' => 9, 'name' => 'Шуруп 3*12', 'note' => null],
            ['position' => 10, 'name' => 'Пластиковая полоса фиксатор', 'note' => null],
        ];

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
