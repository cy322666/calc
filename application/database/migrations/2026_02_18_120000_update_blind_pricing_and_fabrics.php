<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blind_systems', function (Blueprint $table) {
            $table->string('category')->nullable()->index();
            $table->boolean('has_zebra_variant')->default(false);
            $table->decimal('base_cost_price', 12, 2)->default(0);
            $table->decimal('base_retail_price', 12, 2)->default(0);
        });

        Schema::table('blind_components', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('retail_price', 12, 2)->default(0);
        });

        Schema::create('blind_component_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blind_system_id')
                ->constrained('blind_systems')
                ->cascadeOnDelete();
            $table->string('component_type');
            $table->string('value');
            $table->string('label')->nullable();
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('retail_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['blind_system_id', 'component_type', 'value']);
            $table->index(['component_type', 'value']);
        });

        Schema::create('fabric_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('standard'); // standard | zebra
            $table->decimal('weight_factor', 8, 4)->default(0);
            $table->timestamps();

            $table->index(['type']);
        });

        Schema::create('fabrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabric_collection_id')
                ->constrained('fabric_collections')
                ->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight_factor', 8, 4)->default(0); // kg/m2
            $table->decimal('price_per_m2', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active']);
            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabrics');
        Schema::dropIfExists('fabric_collections');
        Schema::dropIfExists('blind_component_compatibilities');

        Schema::table('blind_components', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'retail_price']);
        });

        Schema::table('blind_systems', function (Blueprint $table) {
            $table->dropColumn(['category', 'has_zebra_variant', 'base_cost_price', 'base_retail_price']);
        });
    }
};
