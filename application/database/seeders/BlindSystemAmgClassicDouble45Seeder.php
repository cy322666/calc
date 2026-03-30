<?php

namespace Database\Seeders;

use App\Models\BlindSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BlindSystemAmgClassicDouble45Seeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        /** @var \App\Models\BlindSystem $system */
        $system = \App\Models\Calculator\BlindSystem::updateOrCreate(
            ['code' => 'amg_classic_double_45'],
            [
                'name' => 'AMG классика Double 45 мм',
                'description' => null,
                'category' => 'roller',
            ]
        );

        $items = [
            ['position' => 1,  'name' => 'Труба 45 мм с 3-мя пазами (AMG)', 'note' => null],
            ['position' => 2,  'name' => 'Крышка удлиненная кронштейна 45 для монтажного профиля', 'note' => null],
            ['position' => 3,  'name' => 'Механизм управления: цепь 45, комплект', 'note' => null],
            ['position' => 4,  'name' => 'Механизм управления: цепь 45 для монтажного профиля (комплект)', 'note' => null],
            ['position' => 5,  'name' => 'Крышка кронштейна 45', 'note' => null],
            ['position' => 6,  'name' => 'Пластина подкладочная для MONO/DOUBLE AMG', 'note' => null],
            ['position' => 7,  'name' => 'Кронштейн промежуточный 45', 'note' => null],
            ['position' => 8,  'name' => 'Комплект для мотора с адаптером', 'note' => null],
            ['position' => 9,  'name' => 'Цепь управления сплошная', 'note' => 'пластик (AMG) / металлическая'],
            ['position' => 10, 'name' => 'Замок цепи управления', 'note' => 'пластиковый / металлический'],
            ['position' => 11, 'name' => 'Ограничитель цепи управления', 'note' => 'стандартный / белый RUS'],
            ['position' => 12, 'name' => 'Натяжитель цепи (AMG)', 'note' => null],
            ['position' => 13, 'name' => 'Пластиковая полоса-фиксатор клейкая 7 мм', 'note' => null],
            ['position' => 14, 'name' => 'Рейка нижняя алюминий (AMG)', 'note' => null],
            ['position' => 15, 'name' => 'Рейка нижняя алюминий под полосу, белая', 'note' => null],
            ['position' => 16, 'name' => 'Заглушка нижней рейки', 'note' => null],
            ['position' => 17, 'name' => 'Полоса-фиксатор 9 мм', 'note' => null],
            ['position' => 18, 'name' => 'Лента уплотняющая 8 мм', 'note' => null],
            ['position' => 19, 'name' => 'Лента уплотняющая 7 мм', 'note' => null],
            ['position' => 20, 'name' => 'Лента уплотняющая 6 мм', 'note' => null],
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
