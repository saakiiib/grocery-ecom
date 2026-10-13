<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draft_orders', function (Blueprint $table) {
            $table->id();
            $table->string('draft_number', 40)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('payload');
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draft_orders');
    }
};
