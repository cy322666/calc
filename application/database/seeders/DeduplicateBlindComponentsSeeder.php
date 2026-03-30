<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeduplicateBlindComponentsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $components = DB::table('blind_components')
                ->select(['id', 'name', 'note', 'cost_price', 'retail_price'])
                ->orderBy('id')
                ->get();

            $keepByName = [];
            $duplicates = [];

            foreach ($components as $component) {
                $key = $this->normalizeName($component->name);

                if (! isset($keepByName[$key])) {
                    $keepByName[$key] = $component;
                    continue;
                }

                $duplicates[] = [
                    'keep' => $keepByName[$key],
                    'dup' => $component,
                ];
            }

            foreach ($duplicates as $pair) {
                $keep = $pair['keep'];
                $dup = $pair['dup'];

                $this->mergePivotRows((int) $keep->id, (int) $dup->id);

                DB::table('blind_components')
                    ->where('id', (int) $keep->id)
                    ->update([
                        'note' => $keep->note ?: $dup->note,
                        'cost_price' => max((float) $keep->cost_price, (float) $dup->cost_price),
                        'retail_price' => max((float) $keep->retail_price, (float) $dup->retail_price),
                    ]);

                DB::table('blind_components')
                    ->where('id', (int) $dup->id)
                    ->delete();
            }
        });
    }

    private function mergePivotRows(int $keepId, int $dupId): void
    {
        $pivotRows = DB::table('blind_component_system')
            ->where('blind_component_id', $dupId)
            ->get();

        foreach ($pivotRows as $row) {
            $existing = DB::table('blind_component_system')
                ->where('blind_system_id', (int) $row->blind_system_id)
                ->where('blind_component_id', $keepId)
                ->first();

            if ($existing) {
                DB::table('blind_component_system')
                    ->where('id', (int) $existing->id)
                    ->update([
                        'position' => min((int) $existing->position, (int) $row->position),
                    ]);

                DB::table('blind_component_system')
                    ->where('id', (int) $row->id)
                    ->delete();

                continue;
            }

            DB::table('blind_component_system')
                ->where('id', (int) $row->id)
                ->update([
                    'blind_component_id' => $keepId,
                ]);
        }
    }

    private function normalizeName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));

        // Unify dimension separators: "3 x 12", "3*12", "3х12", "3×12" -> "3x12"
        $normalized = preg_replace('/(\d)\s*[*xх×]\s*(\d)/u', '$1x$2', $normalized);

        // Normalize remaining separator variants to latin "x".
        $normalized = str_replace(['*', 'х', '×'], 'x', $normalized);

        // Collapse whitespace.
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return trim($normalized ?? $name);
    }
}
