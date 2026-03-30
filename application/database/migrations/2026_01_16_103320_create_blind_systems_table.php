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
        Schema::create('blind_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // AMG классика, AMG кассета...
            $table->string('code')->unique();       // amg_classic, amg_cassette...
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blind_systems');
    }
};
