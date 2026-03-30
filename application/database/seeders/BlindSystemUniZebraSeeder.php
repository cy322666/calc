<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlindSystemUniZebraSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $system = \App\Models\Calculator\BlindSystem::updateOrCreate(
            ['code' => 'uni-zebra'],
            [
                'name' => 'UNI ЗЕБРА',
                'description' => 'Комплектация для рулонных жалюзи UNI ЗЕБРА',
                'category' => 'roller',
            ]
        );

        $items = [
            ['position' => 1, 'name' => 'Короб UNI', 'note' => null],
            ['position' => 2, 'name' => 'Профиль дополнительный для UNI ЗЕБРА', 'note' => null],
            ['position' => 3, 'name' => 'Механизм управления комплект UNI', 'note' => null],
            ['position' => 4, 'name' => 'Труба алюминиевая 19 мм', 'note' => 'Альтернатива: труба стальная UNI/MINI с клейкой лентой'],
            ['position' => 5, 'name' => 'Отвес нижний для UNI ЗЕБРА 10 мм', 'note' => null],
            ['position' => 6, 'name' => 'Плитка подкладочная, пара UNI', 'note' => null],
            ['position' => 7, 'name' => 'Направляющая плоская UNI', 'note' => null],
            ['position' => 8, 'name' => 'Цепь петля UNI/MINI', 'note' => null],
            ['position' => 9, 'name' => 'Плитка подкладочная высокая пара UNI', 'note' => null],
            ['position' => 10, 'name' => 'Груз цепи управления', 'note' => 'Альтернатива: груз цепи управления белый декор'],
            ['position' => 11, 'name' => 'Крышка нижняя для направляющей тип C', 'note' => null],
            ['position' => 12, 'name' => 'Кольцо подкладочное', 'note' => 'Альтернатива: кольцо подкладочное белое RUS'],
            ['position' => 13, 'name' => 'Лента клейкая для трубы 12 мм', 'note' => null],
            ['position' => 14, 'name' => 'Лента клейкая двусторонняя 9 мм', 'note' => null],
            ['position' => 15, 'name' => 'Пластиковая полоса фиксатор', 'note' => null],
            ['position' => 16, 'name' => 'Шуруп 3 x 12', 'note' => null],
            ['position' => 17, 'name' => 'Натяжитель для цепи', 'note' => null],
            ['position' => 18, 'name' => 'Держатель цепи управления (ГКС)', 'note' => 'Альтернатива: держатель цепи управления (ГКС) белый RUS'],
            ['position' => 19, 'name' => 'Дополнительный профиль высокий универсальный UNI', 'note' => null],
            ['position' => 20, 'name' => 'Направляющая тип C UNI', 'note' => null],
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
