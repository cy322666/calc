<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blind_component_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blind_component_id')
                ->constrained('blind_components')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('side')->nullable();
            $table->string('material')->nullable();
            $table->boolean('is_default')->default(false);

            $table->decimal('price_opt', 12, 2)->default(0);
            $table->decimal('price_opt1', 12, 2)->default(0);
            $table->decimal('price_opt2', 12, 2)->default(0);
            $table->decimal('price_opt3', 12, 2)->default(0);
            $table->decimal('price_opt4', 12, 2)->default(0);
            $table->decimal('price_vip', 12, 2)->default(0);

            $table->timestamps();

            $table->unique(['blind_component_id', 'name']);
            $table->index(['color']);
            $table->index(['side']);
            $table->index(['material']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blind_component_variants');
    }
};

