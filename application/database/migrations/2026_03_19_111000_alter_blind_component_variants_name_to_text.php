<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blind_component_variants')) {
            return;
        }

        DB::statement('ALTER TABLE blind_component_variants ALTER COLUMN name TYPE TEXT');
    }

    public function down(): void
    {
        if (!Schema::hasTable('blind_component_variants')) {
            return;
        }

        DB::statement('ALTER TABLE blind_component_variants ALTER COLUMN name TYPE VARCHAR(255)');
    }
};

