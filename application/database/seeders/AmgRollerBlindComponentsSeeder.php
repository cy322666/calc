<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AmgRollerBlindComponentsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $items = [
            ['position' => 1,  'name' => 'Труба 32 мм с пазом (AMG)', 'note' => null],
            ['position' => 2,  'name' => 'Труба 45 мм с 3-мя пазами (AMG)', 'note' => null],
            ['position' => 3,  'name' => 'Механизм управления: цепь 32, комплект', 'note' => null],
            ['position' => 4,  'name' => 'Механизм управления: цепь 32+, комплект', 'note' => null],
            ['position' => 5,  'name' => 'Механизм управления: цепь 45, комплект', 'note' => null],
            ['position' => 6,  'name' => 'Комплект для моторизации 45', 'note' => null],
            ['position' => 7,  'name' => 'Комплект для мотора с адаптером', 'note' => null],
            ['position' => 8,  'name' => 'Механизм управления: цепь 45 для монтажного профиля', 'note' => null],
            ['position' => 9,  'name' => 'Крышка кронштейна 32', 'note' => null],
            ['position' => 10, 'name' => 'Крышка удлиненная кронштейна 32 для монтажного профиля', 'note' => null],
            ['position' => 11, 'name' => 'Крышка кронштейна 45', 'note' => null],
            ['position' => 12, 'name' => 'Крышка удлиненная кронштейна 45 для монтажного профиля', 'note' => null],
            ['position' => 13, 'name' => 'Профиль монтажный (AMG) универсальный', 'note' => null],
            ['position' => 14, 'name' => 'Саморез 2,9 × 6,5 мм', 'note' => null],
            ['position' => 15, 'name' => 'Кронштейн потолочный кассеты 32', 'note' => null],
            ['position' => 16, 'name' => 'Пластиковая полоса-фиксатор клейкая 7 мм', 'note' => null],
            ['position' => 17, 'name' => 'Полоса-фиксатор 9 мм', 'note' => null],
            ['position' => 18, 'name' => 'Лента уплотняющая 8 мм', 'note' => null],
            ['position' => 19, 'name' => 'Лента уплотняющая 7 мм', 'note' => null],
            ['position' => 20, 'name' => 'Лента уплотняющая 6 мм', 'note' => null],
            ['position' => 21, 'name' => 'Цепь управления сплошная', 'note' => 'пластик (AMG) / металлическая'],
            ['position' => 22, 'name' => 'Замок цепи управления', 'note' => 'пластиковый / металлический'],
            ['position' => 23, 'name' => 'Ограничитель цепи управления', 'note' => 'стандартный / белый RUS'],
            ['position' => 24, 'name' => 'Комплект стеновых кронштейнов, боковая фиксация', 'note' => null],
            ['position' => 25, 'name' => 'Комплект потолочных кронштейнов, боковая фиксация', 'note' => null],
            ['position' => 26, 'name' => 'Трос металлический', 'note' => null],
            ['position' => 27, 'name' => 'Натяжитель цепи (AMG)', 'note' => null],
            ['position' => 28, 'name' => 'Рейка нижняя алюминий (AMG)', 'note' => null],
            ['position' => 29, 'name' => 'Рейка нижняя алюминий под полосу, белая', 'note' => null],
            ['position' => 30, 'name' => 'Заглушка нижней рейки', 'note' => null],
            ['position' => 31, 'name' => 'Заглушка нижней рейки, боковая фиксация', 'note' => null],
            ['position' => 32, 'name' => 'Кольцо стопорное с винтом', 'note' => null],
        ];

        foreach ($items as $item) {
            $position = (int) $item['position'];

            // 1) UPDATE
            $updated = DB::table('amg_roller_blind_components')
                ->where('position', $position)
                ->update([
                    'name' => (string) $item['name'],
                    'note' => $item['note'],
                    'updated_at' => $now,
                ]);

            // 2) INSERT if not exists
            if ($updated === 0) {
                DB::table('amg_roller_blind_components')->insert([
                    'position' => $position,
                    'name' => (string) $item['name'],
                    'note' => $item['note'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
