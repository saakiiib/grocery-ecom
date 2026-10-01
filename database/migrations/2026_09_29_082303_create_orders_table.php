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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('postcode', 20);
            $table->text('notes')->nullable();
            $table->date('delivery_date');
            $table->foreignId('delivery_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('delivery_slot_label')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('payment_method', 20)->default('cod');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('payment_reference')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('order_statuses')->nullOnDelete();
            $table->string('status_slug')->default('new');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
