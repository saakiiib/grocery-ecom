<?php

namespace App\Console\Commands;

use App\Http\Controllers\CheckoutController;
use App\Models\Order;
use Illuminate\Console\Command;

class PruneUnpaidOrders extends Command
{
    protected $signature = 'app:prune-unpaid-orders {--minutes=60 : Cancel new/unpaid online orders older than this}';

    protected $description = 'Cancel abandoned new/unpaid online orders so coupons and loyalty points are released.';

    public function handle(): int
    {
        $minutes = max(5, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);

        $orders = Order::where('status_slug', 'new')
            ->where('payment_status', 'unpaid')
            ->whereIn('payment_method', ['stripe', 'paypal'])
            ->where('created_at', '<', $cutoff)
            ->get();

        $cancelled = 0;
        foreach ($orders as $order) {
            try {
                if ($order->payment_method === 'stripe' && $order->payment_reference && str_starts_with($order->payment_reference, 'pi_')) {
                    try {
                        CheckoutController::stripeCancelIntent($order->payment_reference);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
                $order->changeStatus('cancelled', null, 'Payment abandoned — auto-cancelled after '.$minutes.' minutes.');
                $cancelled++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Abandoned orders cancelled: {$cancelled}.");

        return self::SUCCESS;
    }
}
