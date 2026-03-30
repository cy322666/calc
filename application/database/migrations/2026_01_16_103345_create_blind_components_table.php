<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blind_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('blind_system_id')
                ->constrained('blind_systems')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('position'); // 1..N внутри системы
            $table->string('name');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['blind_system_id', 'position']);
            $table->index(['blind_system_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blind_components');
    }
};
