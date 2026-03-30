<?php

namespace App\Console\Commands;

use App\Models\Calculator\BlindComponent;
use App\Models\Calculator\BlindComponentCompatibility;
use App\Models\Calculator\BlindSystem;
use App\Models\Calculator\Fabric;
use App\Models\Calculator\FabricCollection;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class ImportAmigoCatalog extends Command
{
    protected $signature = 'amigo:import {file : Path to Excel file} {--sheet= : Optional sheet name}';

    protected $description = 'Import Amigo catalog (systems, components, compatibilities, fabrics) from Excel.';

    public function handle(): int
    {
        $file = $this->argument('file');
        $sheet = $this->option('sheet');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info('Import started...');

        $import = new class($this) implements ToCollection, WithHeadingRow {
            public int $systems = 0;
            public int $components = 0;
            public int $compatibilities = 0;
            public int $fabrics = 0;

            public function __construct(private Command $command)
            {
            }

            public function collection(Collection $rows)
            {
                foreach ($rows as $row) {
                    $systemCode = trim((string) Arr::get($row, 'system_code', ''));
                    if ($systemCode !== '') {
                        $system = BlindSystem::query()->firstOrCreate(
                            ['code' => $systemCode],
                            [
                                'name' => (string) Arr::get($row, 'system_name', $systemCode),
                                'category' => Arr::get($row, 'system_category'),
                                'has_zebra_variant' => (bool) Arr::get($row, 'system_has_zebra', false),
                                'base_cost_price' => (float) Arr::get($row, 'system_base_cost_price', 0),
                                'base_retail_price' => (float) Arr::get($row, 'system_base_retail_price', 0),
                            ]
                        );
                        $this->systems++;

                        $componentPosition = Arr::get($row, 'component_position');
                        $componentName = Arr::get($row, 'component_name');
                        if ($componentPosition !== null && $componentName) {
                            $normalizedName = preg_replace('/\s+/u', ' ', trim((string) $componentName));
                            $component = BlindComponent::query()
                                ->whereRaw('LOWER(name) = ?', [strtolower((string) $normalizedName)])
                                ->first();

                            if (! $component) {
                                $component = BlindComponent::query()->create([
                                    'name' => (string) $normalizedName,
                                    'note' => Arr::get($row, 'component_note'),
                                    'cost_price' => (float) Arr::get($row, 'component_cost_price', 0),
                                    'retail_price' => (float) Arr::get($row, 'component_retail_price', 0),
                                ]);
                            } else {
                                $component->update([
                                    'note' => $component->note ?: Arr::get($row, 'component_note'),
                                    'cost_price' => max((float) $component->cost_price, (float) Arr::get($row, 'component_cost_price', 0)),
                                    'retail_price' => max((float) $component->retail_price, (float) Arr::get($row, 'component_retail_price', 0)),
                                ]);
                            }

                            $system->components()->syncWithoutDetaching([
                                $component->id => ['position' => (int) $componentPosition],
                            ]);
                            $this->components++;
                        }

                        $compatibilityType = Arr::get($row, 'compatibility_type');
                        $compatibilityValue = Arr::get($row, 'compatibility_value');
                        if ($compatibilityType && $compatibilityValue) {
                            BlindComponentCompatibility::query()->updateOrCreate(
                                [
                                    'blind_system_id' => $system->id,
                                    'component_type' => (string) $compatibilityType,
                                    'value' => (string) $compatibilityValue,
                                ],
                                [
                                    'label' => Arr::get($row, 'compatibility_label'),
                                    'cost_price' => (float) Arr::get($row, 'compatibility_cost_price', 0),
                                    'retail_price' => (float) Arr::get($row, 'compatibility_retail_price', 0),
                                    'is_active' => (bool) Arr::get($row, 'compatibility_is_active', true),
                                ]
                            );
                            $this->compatibilities++;
                        }
                    }

                    $collectionName = trim((string) Arr::get($row, 'fabric_collection', ''));
                    $fabricName = trim((string) Arr::get($row, 'fabric_name', ''));
                    if ($collectionName !== '' && $fabricName !== '') {
                        $collectionType = (string) Arr::get($row, 'fabric_type', 'standard');
                        $collection = FabricCollection::query()->firstOrCreate(
                            ['name' => $collectionName, 'type' => $collectionType],
                            ['weight_factor' => (float) Arr::get($row, 'fabric_collection_weight', 0)]
                        );

                        Fabric::query()->updateOrCreate(
                            [
                                'fabric_collection_id' => $collection->id,
                                'name' => $fabricName,
                            ],
                            [
                                'weight_factor' => (float) Arr::get($row, 'fabric_weight_factor', 0),
                                'price_per_m2' => (float) Arr::get($row, 'fabric_price_per_m2', 0),
                                'is_active' => (bool) Arr::get($row, 'fabric_is_active', true),
                            ]
                        );
                        $this->fabrics++;
                    }
                }
            }
        };

        if ($sheet) {
            $multi = new class($import, $sheet) implements WithMultipleSheets {
                public function __construct(private $import, private string $sheet)
                {
                }

                public function sheets(): array
                {
                    return [
                        $this->sheet => $this->import,
                    ];
                }
            };

            Excel::import($multi, $file);
        } else {
            Excel::import($import, $file);
        }

        $this->info('Import complete');
        $this->line("Systems: {$import->systems}");
        $this->line("Components: {$import->components}");
        $this->line("Compatibilities: {$import->compatibilities}");
        $this->line("Fabrics: {$import->fabrics}");

        return self::SUCCESS;
    }
}
