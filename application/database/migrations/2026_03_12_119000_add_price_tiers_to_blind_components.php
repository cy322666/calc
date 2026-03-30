<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blind_components')) {
            return;
        }

        $missingColumns = [];
        foreach (['price_opt', 'price_opt1', 'price_opt2', 'price_opt3', 'price_opt4', 'price_vip'] as $column) {
            if (!Schema::hasColumn('blind_components', $column)) {
                $missingColumns[] = $column;
            }
        }

        if (empty($missingColumns)) {
            return;
        }

        Schema::table('blind_components', function (Blueprint $table) use ($missingColumns) {
            foreach ($missingColumns as $column) {
                $table->decimal($column, 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('blind_components')) {
            return;
        }

        $existingColumns = [];
        foreach (['price_opt', 'price_opt1', 'price_opt2', 'price_opt3', 'price_opt4', 'price_vip'] as $column) {
            if (Schema::hasColumn('blind_components', $column)) {
                $existingColumns[] = $column;
            }
        }

        if (empty($existingColumns)) {
            return;
        }

        Schema::table('blind_components', function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }
};
