<?php

namespace App\Support;

use App\Mail\OrderPlaced;
use App\Models\CompanyDetails;
use App\Models\DeliverySlot;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\ProductVariant;
use App\Models\RepeatSchedule;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Weekly standing orders. Due schedules (next_run_at <= today) rebuild
 * from current shelf prices; unavailable lines are skipped, and a schedule
 * with nothing available simply rolls to next week. New orders start as
 * unpaid `new` — COD is settled on delivery, online methods via the
 * existing pay buttons in the account.
 */
class RepeatRunner
{
    /** @return array{created: int, skipped: int} */
    public static function runDue(): array
    {
        $created = 0;
        $skipped = 0;
        $schedules = RepeatSchedule::where('is_active', true)
            ->whereDate('next_run_at', '<=', today())
            ->get();

        foreach ($schedules as $schedule) {
            $result = static::runOne($schedule);
            if ($result) {
                $created++;
            } else {
                $skipped++;
            }
            $schedule->update(['last_run_at' => now(), 'next_run_at' => today()->addWeek()]);
        }

        return compact('created', 'skipped');
    }

    public static function runOne(RepeatSchedule $schedule): ?Order
    {
        $items = is_array($schedule->items) ? $schedule->items : [];
        $lines = [];
        $subtotal = 0.0;
        foreach ($items as $row) {
            $variant = ProductVariant::with('product')->find($row['variant_id'] ?? null);
            if (! $variant || ! $variant->product || ! $variant->product->status || ! $variant->status || ! $variant->in_stock) {
                continue;
            }
            $price = (float) $variant->sellingPrice();
            $hit = FlashSale::priceFor($variant->id, $variant->product_id);
            if ($hit && $hit['price'] < $price) {
                $price = $hit['price'];
            }
            $qty = min(max((int) ($row['qty'] ?? 1), 1), 99);
            $lines[] = [
                'variant_id' => $variant->id, 'product_id' => $variant->product_id,
                'name' => $variant->product->name, 'sku' => $variant->sku,
                'pack' => $variant->combinationLabel(), 'price' => $price,
                'qty' => $qty, 'line_total' => round($price * $qty, 2),
            ];
            $subtotal += round($price * $qty, 2);
        }
        $min = Setting::money('delivery_min_order', 15.00);
        if ($lines === [] || $subtotal < $min) {
            return null;
        }

        $slot = DeliverySlot::ordered()->first();
        $dates = DeliverySlot::bookableDates();
        if (! $slot || $dates === []) {
            return null;
        }
        $fee = $subtotal >= Setting::money('delivery_free_over', 50.00) ? 0.0 : (float) $slot->fee;
        $total = round($subtotal + $fee, 2);
        $vatPercent = (float) (CompanyDetails::cached()->vat_percent ?? 0);
        $status = OrderStatus::where('slug', 'new')->where('is_active', true)->first();
        if (! $status) {
            return null;
        }

        return DB::transaction(function () use ($schedule, $lines, $subtotal, $fee, $total, $vatPercent, $slot, $dates, $status) {
            $order = Order::create([
                'number' => 'PENDING',
                'user_id' => $schedule->user_id,
                'name' => $schedule->name,
                'phone' => $schedule->phone,
                'email' => $schedule->email,
                'address' => $schedule->address,
                'city' => $schedule->city,
                'postcode' => $schedule->postcode,
                'billing_name' => $schedule->name,
                'billing_phone' => $schedule->phone,
                'billing_address' => $schedule->address,
                'billing_city' => $schedule->city,
                'billing_postcode' => $schedule->postcode,
                'substitution_preference' => 'substitute',
                'delivery_date' => array_key_first($dates),
                'delivery_slot_id' => $slot->id,
                'delivery_slot_label' => $slot->label(),
                'subtotal' => round($subtotal, 2),
                'delivery_fee' => round($fee, 2),
                'total' => $total,
                'vat_percent' => $vatPercent,
                'vat_amount' => $vatPercent > 0 ? round($total * $vatPercent / (100 + $vatPercent), 2) : 0.0,
                'payment_method' => $schedule->payment_method,
                'payment_status' => 'unpaid',
                'status_id' => $status->id,
                'status_slug' => $status->slug,
            ]);
            $order->number = 'EGF-'.(10000 + $order->id);
            $order->save();
            foreach ($lines as $line) {
                $order->items()->create([
                    'product_variant_id' => $line['variant_id'],
                    'product_id' => $line['product_id'],
                    'product_name' => $line['name'],
                    'variant_sku' => $line['sku'],
                    'pack_label' => $line['pack'],
                    'unit_price' => $line['price'],
                    'qty' => $line['qty'],
                    'line_total' => $line['line_total'],
                ]);
            }
            $order->histories()->create([
                'from_slug' => null, 'to_slug' => $status->slug,
                'changed_by' => $schedule->user_id, 'note' => 'Weekly repeat order.',
            ]);
            try {
                Mail::to($order->receiptEmail())->send(new OrderPlaced($order));
            } catch (\Throwable $e) {
                // Repeat stands even if the mailer is down.
            }

            return $order;
        });
    }
}
