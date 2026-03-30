<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlindSystemUniSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $system = \App\Models\Calculator\BlindSystem::updateOrCreate(
            ['code' => 'uni'],
            [
                'name' => 'UNI',
                'description' => 'Комплектация для рулонных жалюзи UNI',
                'category' => 'roller',
            ]
        );

        $items = [
            ['position' => 1, 'name' => 'Короб UNI', 'note' => null],
            ['position' => 2, 'name' => 'Дополнительный профиль UNI', 'note' => null],
            ['position' => 3, 'name' => 'Дополнительный профиль высокий', 'note' => null],
            ['position' => 4, 'name' => 'Труба алюминиевая 19 мм', 'note' => 'Альтернатива: труба стальная UNI/MINI с клейкой лентой'],
            ['position' => 5, 'name' => 'Планка нижняя стальная', 'note' => 'Альтернативы: планка нижняя алюминиевая (серебро); планка нижняя стальная Омега белая RUS'],
            ['position' => 6, 'name' => 'Уплотнитель нижней планки UNI', 'note' => 'Альтернативы: уплотнитель нижней планки серебро UNI; шлегель 5 x 6 мм'],
            ['position' => 7, 'name' => 'Механизм управления комплект UNI', 'note' => null],
            ['position' => 8, 'name' => 'Плитка подкладочная, пара', 'note' => null],
            ['position' => 9, 'name' => 'Плитка подкладочная высокая, пара', 'note' => null],
            ['position' => 10, 'name' => 'Направляющая плоская UNI', 'note' => null],
            ['position' => 11, 'name' => 'Направляющая тип C UNI', 'note' => null],
            ['position' => 12, 'name' => 'Цепь управления', 'note' => null],
            ['position' => 13, 'name' => 'Соединитель цепи управления', 'note' => 'Альтернатива: соединитель цепи управления белый RUS'],
            ['position' => 14, 'name' => 'Груз цепи управления', 'note' => 'Альтернатива: груз цепи управления белый декор'],
            ['position' => 15, 'name' => 'Ограничитель цепи управления', 'note' => 'Альтернатива: ограничитель цепи управления белый RUS'],
            ['position' => 16, 'name' => 'Крышка нижняя боковая UNI', 'note' => 'Альтернативы: алюминиевая серебро; Омега белая UNI RUS'],
            ['position' => 17, 'name' => 'Крышка нижняя для направляющей тип C', 'note' => null],
            ['position' => 18, 'name' => 'Кольцо подкладочное', 'note' => 'Альтернатива: кольцо подкладочное белое RUS'],
            ['position' => 19, 'name' => 'Лента клейкая для трубы 12 мм', 'note' => null],
            ['position' => 20, 'name' => 'Лента клейкая двусторонняя 9 мм белая', 'note' => null],
            ['position' => 21, 'name' => 'Лента клейкая двусторонняя 19 мм белая', 'note' => null],
            ['position' => 22, 'name' => 'Пластиковая полоса фиксатор', 'note' => null],
            ['position' => 23, 'name' => 'Шуруп 3 x 12', 'note' => null],
            ['position' => 24, 'name' => 'Соединитель цепи управления безопасный', 'note' => null],
            ['position' => 25, 'name' => 'Держатель цепи управления (КС)', 'note' => 'Альтернатива: держатель цепи управления (КС) белый RUS'],
            ['position' => 26, 'name' => 'Ось поворотная (утяжелитель)', 'note' => null],
            ['position' => 27, 'name' => 'Натяжитель для цепи', 'note' => null],
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
