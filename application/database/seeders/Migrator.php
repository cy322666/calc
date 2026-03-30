<?php

namespace Database\Seeders;

use App\Models\Calculator\BlindComponent;
use App\Models\Calculator\BlindSystem;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migrator extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1) Системы (типы штор)
        $classic = BlindSystem::updateOrCreate(
            ['code' => 'amg_classic'],
            [
                'name' => 'AMG классика',
                'description' => null,
                'category' => 'roller',
            ]
        );

        $cassette = BlindSystem::updateOrCreate(
            ['code' => 'amg_cassette'],
            [
                'name' => 'AMG кассета',
                'description' => null,
                'category' => 'roller',
            ]
        );

        // 2) Перенос из старых таблиц
        $this->migrateTable(
            legacyTable: 'amg_roller_blind_components',
            systemId: $classic->id,
            now: $now
        );

        $this->migrateTable(
            legacyTable: 'amg_cassette_components',
            systemId: $cassette->id,
            now: $now
        );
    }

    private function migrateTable(string $legacyTable, int $systemId, Carbon $now): void
    {
        // Если старой таблицы нет — ничего не делаем
        if (! Schema::hasTable($legacyTable)) {
            $this->command?->warn("Skip: legacy table '{$legacyTable}' not found.");
            return;
        }

        $rows = DB::table($legacyTable)
            ->select(['position', 'name', 'note'])
            ->orderBy('position')
            ->get();

        if ($rows->isEmpty()) {
            $this->command?->warn("Skip: legacy table '{$legacyTable}' is empty.");
            return;
        }

        $count = 0;
        foreach ($rows as $row) {
            $component = BlindComponent::firstOrCreate(
                [
                    'name' => (string) $row->name,
                    'note' => $row->note !== null ? (string) $row->note : null,
                    'cost_price' => 0,
                    'retail_price' => 0,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            DB::table('blind_component_system')->updateOrInsert(
                [
                    'blind_system_id' => $systemId,
                    'position' => (int) $row->position,
                ],
                [
                    'blind_component_id' => $component->id,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $count++;
        }

        $this->command?->info("Migrated {$count} rows from '{$legacyTable}' into blind_component_system (system_id={$systemId}).");
    }

}
