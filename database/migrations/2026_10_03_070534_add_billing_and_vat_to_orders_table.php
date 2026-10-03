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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('billing_name', 100)->nullable()->after('postcode');
            $table->string('billing_phone', 30)->nullable()->after('billing_name');
            $table->text('billing_address')->nullable()->after('billing_phone');
            $table->string('billing_city', 100)->nullable()->after('billing_address');
            $table->string('billing_postcode', 20)->nullable()->after('billing_city');
            $table->decimal('vat_percent', 5, 2)->default(0)->after('coupon_discount');
            $table->decimal('vat_amount', 10, 2)->default(0)->after('vat_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'billing_name', 'billing_phone', 'billing_address',
                'billing_city', 'billing_postcode', 'vat_percent', 'vat_amount',
            ]);
        });
    }
};
