<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            Migrator::class,
            BlindSystemAmgClassicDouble45Seeder::class,
            BlindSystemAmgDayNight45Seeder::class,
            BlindSystemAmgSpring32Seeder::class,
            BlindSystemAmgClassicMono45Seeder::class,
            BlindSystemUniSeeder::class,
            BlindSystemUniZebraSeeder::class,
            BlindSystemUniSpringSeeder::class,
            BlindSystemBntMSeeder::class,
            DeduplicateBlindComponentsSeeder::class,
        ]);
    }
}
