<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;

class OrderPayload
{
    public static function make(Order $order): array
    {
        $order->loadMissing(['items', 'histories', 'status']);

        return array_merge($order->toArray(), [
            'status_name' => $order->status?->name ?? $order->status_slug,
            'payment_label' => $order->paymentLabel(),
            'payment_status_label' => $order->paymentStatusLabel(),
            'substitution_label' => $order->substitutionLabel(),
            'is_paid' => $order->isPaid(),
            'can_cancel' => ! $order->isPaid() && in_array($order->status_slug, ['new', 'confirmed'], true),
            'needs_payment' => ! $order->isPaid()
                && $order->payment_method !== 'cod'
                && ! in_array($order->status_slug, ['cancelled', 'delivered'], true)
                && (float) $order->refunded_amount <= 0,
        ]);
    }
}
