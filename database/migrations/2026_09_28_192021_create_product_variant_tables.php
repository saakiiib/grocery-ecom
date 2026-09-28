<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->enum('type', ['buttons', 'dropdown'])->default('dropdown');
            $table->boolean('status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained('option_groups')->cascadeOnDelete();
            $table->string('label');
            $table->string('slug');
            $table->boolean('status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['option_group_id', 'slug']);
        });

        Schema::create('category_option_group', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('option_group_id')->constrained('option_groups')->restrictOnDelete();
            $table->integer('sort_order')->default(0);

            $table->primary(['category_id', 'option_group_id']);
        });

        Schema::create('product_option_group', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('option_group_id')->constrained('option_groups')->restrictOnDelete();
            $table->integer('sort_order')->default(0);

            $table->primary(['product_id', 'option_group_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique()->nullable();
            $table->decimal('mrp', 12, 2);
            $table->decimal('offer_price', 12, 2)->nullable();
            $table->string('image')->nullable();
            $table->boolean('in_stock')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('option_value_id')->constrained('option_values')->restrictOnDelete();

            $table->primary(['variant_id', 'option_value_id']);
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('label');
            $table->text('value');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_option_group');
        Schema::dropIfExists('category_option_group');
        Schema::dropIfExists('option_values');
        Schema::dropIfExists('option_groups');
    }
};
