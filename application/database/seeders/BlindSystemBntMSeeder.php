<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlindSystemBntMSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $system = \App\Models\Calculator\BlindSystem::updateOrCreate(
            ['code' => 'bnt-m'],
            [
                'name' => 'КЛАССИКА BNT M',
                'description' => 'Комплектация КЛАССИКА BNT M',
                'category' => 'roller',
            ]
        );

        $items = [
            ['position' => 1, 'name' => 'Труба 29 мм R/OOF', 'note' => null],
            ['position' => 2, 'name' => 'Труба 43 мм с двумя пазами ML', 'note' => null],
            ['position' => 3, 'name' => 'Профиль монтажный M', 'note' => null],
            ['position' => 4, 'name' => 'Рейка нижняя M', 'note' => null],
            ['position' => 5, 'name' => 'Рейка нижняя L', 'note' => null],
            ['position' => 6, 'name' => 'Пластиковая полоса-фиксатор 9x0,5 мм', 'note' => null],
            ['position' => 7, 'name' => 'Пластиковая полоса-фиксатор 10x1,2 мм', 'note' => null],
            ['position' => 8, 'name' => 'Механизм управления цепочный 29 мм M', 'note' => null],
            ['position' => 9, 'name' => 'Механизм цепочный для детской безопасности', 'note' => null],
            ['position' => 10, 'name' => 'Механизм управления 29 мм, комплект', 'note' => 'Варианты: матовый хром, хром, шампань'],
            ['position' => 11, 'name' => 'Заглушка в трубу 29 мм M', 'note' => null],
            ['position' => 12, 'name' => 'Заглушка в трубу 29 мм подпружиненная', 'note' => null],
            ['position' => 13, 'name' => 'Заглушка в трубу 29 мм регулируемая M, белая', 'note' => null],
            ['position' => 14, 'name' => 'Цепь управления сплошная пластиковая, СК стандарт', 'note' => 'Варианты: цепь управления сплошная металлическая; цепь управления 4.5x6 мм; цепь управления сплошная прозрачная'],
            ['position' => 15, 'name' => 'Адаптер 29-43 мм M', 'note' => null],
            ['position' => 16, 'name' => 'Крышка нижней рейки M, пара', 'note' => null],
            ['position' => 17, 'name' => 'Крышка нижней рейки M для боковой фиксации, пара, белая', 'note' => null],
            ['position' => 18, 'name' => 'Крышка нижней рейки L, пара', 'note' => null],
            ['position' => 19, 'name' => 'Кронштейн потолочный универсальный M, металл', 'note' => null],
            ['position' => 20, 'name' => 'Трос металлический', 'note' => null],
            ['position' => 21, 'name' => 'Кронштейн нижний для троса M, белый', 'note' => null],
            ['position' => 22, 'name' => 'Кольцо стопорное 6x1,3x5 мм', 'note' => null],
            ['position' => 23, 'name' => 'Втулка для троса 1,2 мм', 'note' => null],
            ['position' => 24, 'name' => 'Пружина 0,7x44', 'note' => null],
            ['position' => 25, 'name' => 'Держатель троса прозрачный, стеновой', 'note' => null],
            ['position' => 26, 'name' => 'Держатель троса прозрачный, потолочный', 'note' => null],
            ['position' => 27, 'name' => 'Кронштейн 36 мм M, металл', 'note' => 'Вариант: кронштейн 36 мм M, 90 гр. металл'],
            ['position' => 28, 'name' => 'Кронштейн 41 мм M, металл', 'note' => null],
            ['position' => 29, 'name' => 'Кронштейн 41 мм M, 90 гр. металл', 'note' => null],
            ['position' => 30, 'name' => 'Крышка кронштейна плоская 55x36 мм M, белая', 'note' => null],
            ['position' => 31, 'name' => 'Крышка кронштейна широкая 55x36 мм M, белая', 'note' => null],
            ['position' => 32, 'name' => 'Крышка кронштейна плоская 55x41 мм M', 'note' => null],
            ['position' => 33, 'name' => 'Крышка кронштейна широкая 55x41 мм M', 'note' => null],
            ['position' => 34, 'name' => 'Крышка кронштейна плоская 55x46 мм M', 'note' => null],
            ['position' => 35, 'name' => 'Крышка кронштейна широкая 55x46 мм M', 'note' => null],
            ['position' => 36, 'name' => 'Саморез 2,9x6,5 DIN 7981 остроконечный', 'note' => null],
            ['position' => 37, 'name' => 'Груз цепи декоративный', 'note' => null],
            ['position' => 38, 'name' => 'Ограничитель цепи управления 4,5x6 мм прозрачный', 'note' => null],
            ['position' => 39, 'name' => 'Замок цепи управления пластиковый, односоставный', 'note' => 'Варианты: замок цепи управления металлический; замок цепи управления пластиковый, прозрачный; замок цепи управления 4,5 мм'],
            ['position' => 40, 'name' => 'Лента клейкая д/трубы 17 мм', 'note' => null],
            ['position' => 41, 'name' => 'Кронштейн 36 мм M, 90 гр. металл', 'note' => null],
            ['position' => 42, 'name' => 'Пружина 43 мм', 'note' => null],
            ['position' => 43, 'name' => 'Кронштейн 60 мм M, металл', 'note' => null],
            ['position' => 44, 'name' => 'Крышка кронштейна плоская 55x60 мм M', 'note' => null],
            ['position' => 45, 'name' => 'Крышка кронштейна широкая 55x60 мм M', 'note' => null],
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
