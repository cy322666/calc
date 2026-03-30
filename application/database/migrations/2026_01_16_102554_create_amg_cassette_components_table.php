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
        Schema::create('amg_cassette_components', function (Blueprint $table) {
            $table->id();

            // Позиция из каталога (1–28)
            $table->unsignedSmallInteger('position')->unique();

            // Название позиции
            $table->string('name');

            // Дополнительные варианты / примечания
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amg_cassette_components');
    }
};
