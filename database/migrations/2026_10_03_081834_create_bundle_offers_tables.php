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
        Schema::create('bundle_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('required_qty')->default(3);
            $table->decimal('bundle_price', 12, 2);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('bundle_offer_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bundle_offer_id', 'category_id']);
        });

        Schema::create('bundle_offer_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bundle_offer_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_offer_variants');
        Schema::dropIfExists('bundle_offer_categories');
        Schema::dropIfExists('bundle_offers');
    }
};
