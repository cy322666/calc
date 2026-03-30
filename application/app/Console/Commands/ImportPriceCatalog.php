<?php

namespace App\Console\Commands;

use App\Models\Calculator\BlindComponent;
use App\Models\Calculator\BlindComponentVariant;
use App\Models\Calculator\BlindSystem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportPriceCatalog extends Command
{
    protected $signature = 'prices:import 
        {file : Path to xlsx file}
        {--sheet= : Sheet title to import (optional)}
        {--system= : System code for linking components (optional)}
        {--dry-run : Parse only, without writes}';

    protected $description = 'Import component variants and prices from XLSX catalog into DB.';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $sheetFilter = (string) ($this->option('sheet') ?? '');
        $systemCode = (string) ($this->option('system') ?? '');
        $dryRun = (bool) $this->option('dry-run');

        if (!is_file($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $system = null;
        if ($systemCode !== '') {
            $system = BlindSystem::query()->where('code', $systemCode)->first();
            if (!$system) {
                $this->error("System not found by code: {$systemCode}");
                return self::FAILURE;
            }
        }

        $spreadsheet = IOFactory::load($file);
        $sheets = $spreadsheet->getAllSheets();

        $createdComponents = 0;
        $updatedComponents = 0;
        $createdVariants = 0;
        $updatedVariants = 0;
        $linkedToSystem = 0;
        $parsedRows = 0;

        DB::beginTransaction();
        try {
            foreach ($sheets as $sheet) {
                if ($sheetFilter !== '' && $sheet->getTitle() !== $sheetFilter) {
                    continue;
                }

                [$rows, $stats] = $this->extractRows($sheet);
                $parsedRows += $stats['parsed'];

                foreach ($rows as $row) {
                    $baseName = $this->extractBaseName($row['name']);
                    if ($baseName === '') {
                        continue;
                    }

                    $attrs = $this->extractAttributes($row['name']);
                    $component = BlindComponent::query()
                        ->firstOrCreate(
                            ['name' => $baseName],
                            [
                                'note' => null,
                                'cost_price' => 0,
                                'retail_price' => 0,
                                'price_opt' => 0,
                                'price_opt1' => 0,
                                'price_opt2' => 0,
                                'price_opt3' => 0,
                                'price_opt4' => 0,
                                'price_vip' => 0,
                            ]
                        );

                    if ($component->wasRecentlyCreated) {
                        $createdComponents++;
                    }

                    $variant = BlindComponentVariant::query()
                        ->where('blind_component_id', $component->id)
                        ->where('name', $row['name'])
                        ->first();

                    $payload = [
                        'color' => $attrs['color'],
                        'side' => $attrs['side'],
                        'material' => $attrs['material'],
                        'is_default' => $attrs['is_default'],
                        'price_opt' => $row['opt'],
                        'price_opt1' => $row['opt1'],
                        'price_opt2' => $row['opt2'],
                        'price_opt3' => $row['opt3'],
                        'price_opt4' => $row['opt4'],
                        'price_vip' => $row['vip'],
                    ];

                    if ($variant) {
                        $variant->update($payload);
                        $updatedVariants++;
                    } else {
                        BlindComponentVariant::query()->create([
                            'blind_component_id' => $component->id,
                            'name' => $row['name'],
                            ...$payload,
                        ]);
                        $createdVariants++;
                    }

                    if ($system) {
                        $exists = DB::table('blind_component_system')
                            ->where('blind_system_id', $system->id)
                            ->where('blind_component_id', $component->id)
                            ->exists();

                        if (!$exists) {
                            $position = (int) DB::table('blind_component_system')
                                    ->where('blind_system_id', $system->id)
                                    ->max('position') + 1;

                            DB::table('blind_component_system')->insert([
                                'blind_system_id' => $system->id,
                                'blind_component_id' => $component->id,
                                'position' => $position,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $linkedToSystem++;
                        }
                    }

                    if (!$component->wasRecentlyCreated) {
                        $updatedComponents++;
                    }
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry-run enabled: changes were rolled back.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Import completed.');
        $this->line("Parsed rows: {$parsedRows}");
        $this->line("Components created: {$createdComponents}");
        $this->line("Components touched: {$updatedComponents}");
        $this->line("Variants created: {$createdVariants}");
        $this->line("Variants updated: {$updatedVariants}");
        if ($system) {
            $this->line("Linked to system {$system->code}: {$linkedToSystem}");
        }

        return self::SUCCESS;
    }

    private function extractRows(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestDataRow();
        $rows = [];
        $parsed = 0;

        for ($row = 1; $row <= $highestRow; $row++) {
            $name = $this->normalize((string) $sheet->getCell('C' . $row)->getFormattedValue());
            if ($name === '') {
                continue;
            }

            if ($this->isHeaderRow($name)) {
                continue;
            }

            $opt = $this->toFloat($sheet->getCell('E' . $row)->getCalculatedValue());
            $opt1 = $this->toFloat($sheet->getCell('F' . $row)->getCalculatedValue());
            $opt2 = $this->toFloat($sheet->getCell('G' . $row)->getCalculatedValue());
            $opt3 = $this->toFloat($sheet->getCell('H' . $row)->getCalculatedValue());
            $opt4 = $this->toFloat($sheet->getCell('I' . $row)->getCalculatedValue());
            $vip = $this->toFloat($sheet->getCell('J' . $row)->getCalculatedValue());

            // Keep only catalog rows with at least one price.
            if ($opt <= 0 && $opt1 <= 0 && $opt2 <= 0 && $opt3 <= 0 && $opt4 <= 0 && $vip <= 0) {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'opt' => $opt,
                'opt1' => $opt1,
                'opt2' => $opt2,
                'opt3' => $opt3,
                'opt4' => $opt4,
                'vip' => $vip,
            ];
            $parsed++;
        }

        return [$rows, ['parsed' => $parsed]];
    }

    private function extractBaseName(string $name): string
    {
        $parts = explode(',', $name);
        $base = $this->normalize($parts[0] ?? $name);
        return trim($base);
    }

    private function extractAttributes(string $name): array
    {
        $lower = mb_strtolower($name);

        $side = null;
        if (str_contains($lower, 'лев')) {
            $side = 'left';
        } elseif (str_contains($lower, 'прав')) {
            $side = 'right';
        }

        $material = null;
        if (str_contains($lower, 'металл')) {
            $material = 'metal';
        } elseif (str_contains($lower, 'пластик')) {
            $material = 'plastic';
        }

        $color = null;
        if (str_contains($lower, 'бел')) {
            $color = 'white';
        } elseif (str_contains($lower, 'антрац')) {
            $color = 'anthracite';
        } elseif (str_contains($lower, 'сер')) {
            $color = 'gray';
        } elseif (str_contains($lower, 'черн')) {
            $color = 'black';
        }

        return [
            'side' => $side,
            'material' => $material,
            'color' => $color,
            'is_default' => $side === null && $material === null && $color === null,
        ];
    }

    private function isHeaderRow(string $name): bool
    {
        $upper = mb_strtoupper($name);
        return str_contains($upper, 'ПРАЙС')
            || str_contains($upper, 'НАИМЕНОВАНИЕ')
            || preg_match('/^\d+\s*ММ$/u', $upper) === 1;
    }

    private function normalize(string $value): string
    {
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        return trim($value);
    }

    private function toFloat(mixed $value): float
    {
        $limit = 9999999.99;

        if (is_numeric($value)) {
            $num = (float) $value;
            return abs($num) <= $limit ? $num : 0.0;
        }

        $s = $this->normalize((string) $value);
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/[^0-9.\-]/', '', $s) ?? '';
        if (!is_numeric($s)) {
            return 0.0;
        }

        $num = (float) $s;
        return abs($num) <= $limit ? $num : 0.0;
    }
}
