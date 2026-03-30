<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AmgCassetteComponentsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $items = [
            ['position' => 1,  'name' => 'Труба 32 мм с пазом (AMG)', 'note' => null],
            ['position' => 2,  'name' => 'Профиль соединительный кассеты 32', 'note' => null],
            ['position' => 3,  'name' => 'Профиль лицевой кассеты 32, без паза', 'note' => null],
            ['position' => 4,  'name' => 'Профиль лицевой кассеты 32, с пазом', 'note' => null],
            ['position' => 5,  'name' => 'Труба 45 мм с 3-мя пазами (AMG)', 'note' => null],
            ['position' => 6,  'name' => 'Профиль соединительный кассеты 45', 'note' => null],
            ['position' => 7,  'name' => 'Профиль лицевой кассеты 45, без паза', 'note' => null],
            ['position' => 8,  'name' => 'Профиль лицевой кассеты 45, с пазом', 'note' => null],
            ['position' => 9,  'name' => 'Механизм управления: цепь кассеты 32', 'note' => 'левый / правый (комплект)'],
            ['position' => 10, 'name' => 'Механизм управления: цепь кассеты 45', 'note' => 'левый / правый (комплект)'],
            ['position' => 11, 'name' => 'Кронштейн стеновой кассеты 32', 'note' => null],
            ['position' => 12, 'name' => 'Кронштейн потолочный кассеты 32', 'note' => null],
            ['position' => 13, 'name' => 'Кронштейн стеновой кассеты 45', 'note' => null],
            ['position' => 14, 'name' => 'Кронштейн потолочный кассеты 45', 'note' => null],
            ['position' => 15, 'name' => 'Пластиковая полоса-фиксатор клейкая 7 мм', 'note' => null],
            ['position' => 16, 'name' => 'Цепь управления сплошная', 'note' => 'пластик (AMG) / металлическая'],
            ['position' => 17, 'name' => 'Замок цепи управления', 'note' => 'пластиковый / металлический'],
            ['position' => 18, 'name' => 'Ограничитель цепи управления', 'note' => 'стандартный / белый RUS'],
            ['position' => 19, 'name' => 'Натяжитель цепи (AMG)', 'note' => null],
            ['position' => 20, 'name' => 'Рейка нижняя алюминий (AMG)', 'note' => null],
            ['position' => 21, 'name' => 'Рейка нижняя алюминий под полосу, белая', 'note' => null],
            ['position' => 22, 'name' => 'Заглушка нижней рейки', 'note' => null],
            ['position' => 23, 'name' => 'Полоса-фиксатор 9 мм', 'note' => null],
            ['position' => 24, 'name' => 'Лента уплотняющая 8 мм', 'note' => null],
            ['position' => 25, 'name' => 'Лента уплотняющая 7 мм', 'note' => null],
            ['position' => 26, 'name' => 'Лента уплотняющая 6 мм', 'note' => null],
            ['position' => 27, 'name' => 'Лента клейкая для трубы 12 мм', 'note' => null],
            ['position' => 28, 'name' => 'Комплект для мотора с адаптером', 'note' => null],
        ];

        foreach ($items as $item) {
            $position = (int) $item['position'];

            // 1) Пытаемся обновить
            $updated = DB::table('amg_cassette_components')
                ->where('position', $position)
                ->update([
                    'name' => (string) $item['name'],
                    'note' => $item['note'],
                    'updated_at' => $now,
                ]);

            // 2) Если не обновили ни одной строки — вставляем
            if ($updated === 0) {
                DB::table('amg_cassette_components')->insert([
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
