<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('group')->default('config');
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->decimal('price_delta', 12, 2)->nullable();
            $table->string('swatch_color', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
