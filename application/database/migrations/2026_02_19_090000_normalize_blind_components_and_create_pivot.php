<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blind_components')) {
            return;
        }

        Schema::rename('blind_components', 'blind_components_legacy');

        Schema::create('blind_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('note')->nullable();
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('retail_price', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['name']);
        });

        Schema::create('blind_component_system', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blind_system_id')
                ->constrained('blind_systems')
                ->cascadeOnDelete();
            $table->foreignId('blind_component_id')
                ->constrained('blind_components')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['blind_system_id', 'blind_component_id']);
            $table->unique(['blind_system_id', 'position']);
        });

        DB::transaction(function () {
            $legacyRows = DB::table('blind_components_legacy')
                ->select([
                    'id',
                    'blind_system_id',
                    'position',
                    'name',
                    'note',
                    'cost_price',
                    'retail_price',
                    'created_at',
                    'updated_at',
                ])
                ->orderBy('id')
                ->get();

            $componentMap = [];

            foreach ($legacyRows as $row) {
                $signature = implode('|', [
                    strtolower(trim((string) $row->name)),
                    trim((string) ($row->note ?? '')),
                    number_format((float) ($row->cost_price ?? 0), 2, '.', ''),
                    number_format((float) ($row->retail_price ?? 0), 2, '.', ''),
                ]);

                if (!isset($componentMap[$signature])) {
                    $componentId = DB::table('blind_components')->insertGetId([
                        'name' => (string) $row->name,
                        'note' => $row->note,
                        'cost_price' => (float) ($row->cost_price ?? 0),
                        'retail_price' => (float) ($row->retail_price ?? 0),
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);

                    $componentMap[$signature] = $componentId;
                }

                DB::table('blind_component_system')->insert([
                    'blind_system_id' => (int) $row->blind_system_id,
                    'blind_component_id' => $componentMap[$signature],
                    'position' => (int) $row->position,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        });

        Schema::drop('blind_components_legacy');
    }

    public function down(): void
    {
        if (!Schema::hasTable('blind_components') || !Schema::hasTable('blind_component_system')) {
            return;
        }

        Schema::rename('blind_components', 'blind_components_normalized');

        Schema::create('blind_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blind_system_id')
                ->constrained('blind_systems')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('name');
            $table->text('note')->nullable();
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('retail_price', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['blind_system_id', 'position']);
            $table->index(['blind_system_id', 'name']);
        });

        DB::transaction(function () {
            $rows = DB::table('blind_component_system as pivot')
                ->join('blind_components_normalized as component', 'component.id', '=', 'pivot.blind_component_id')
                ->select([
                    'pivot.blind_system_id',
                    'pivot.position',
                    'component.name',
                    'component.note',
                    'component.cost_price',
                    'component.retail_price',
                    'pivot.created_at',
                    'pivot.updated_at',
                ])
                ->orderBy('pivot.blind_system_id')
                ->orderBy('pivot.position')
                ->get();

            foreach ($rows as $row) {
                DB::table('blind_components')->insert([
                    'blind_system_id' => (int) $row->blind_system_id,
                    'position' => (int) $row->position,
                    'name' => (string) $row->name,
                    'note' => $row->note,
                    'cost_price' => (float) ($row->cost_price ?? 0),
                    'retail_price' => (float) ($row->retail_price ?? 0),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        });

        Schema::dropIfExists('blind_component_system');
        Schema::dropIfExists('blind_components_normalized');
    }
};
