<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('model_code')->unique();
            $table->string('tagline')->nullable();
            $table->longText('description')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('lead_time')->nullable();
            $table->string('warranty')->nullable();
            $table->decimal('base_price', 12, 2)->nullable();
            $table->string('hero_image')->nullable();
            $table->string('video_url')->nullable();
            $table->string('model_3d')->nullable();
            $table->boolean('show_3d')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('meta_image')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
