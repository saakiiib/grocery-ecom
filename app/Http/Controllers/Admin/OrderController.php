<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $orders = Order::with(['status', 'items'])->orderByDesc('id');

            return DataTables::of($orders)
                ->addIndexColumn()
                ->addColumn('number', fn ($row) => '<a href="'.route('orders.show', $row->id).'"><strong>'.$row->number.'</strong></a>')
                ->addColumn('customer', fn ($row) => e($row->name).'<br><small class="text-muted">'.e($row->phone).'</small>')
                ->addColumn('items', fn ($row) => $row->items->sum('qty').' item'.($row->items->sum('qty') === 1 ? '' : 's'))
                ->addColumn('total', fn ($row) => '£'.number_format($row->total, 2))
                ->addColumn('payment', function ($row) {
                    $badge = $row->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark';

                    return e($row->paymentLabel()).'<br><span class="badge '.$badge.'">'.ucfirst($row->payment_status).'</span>';
                })
                ->addColumn('status', function ($row) {
                    $st = $row->status;
                    $color = $st?->color ?? '#1A2E22';
                    $name = $st?->name ?? ucfirst($row->status_slug);

                    return '<span class="badge" style="background:'.$color.';">'.$name.'</span>';
                })
                ->addColumn('date', fn ($row) => $row->created_at->format('d M Y, h:i A').'<br><small class="text-muted">For '.$row->delivery_date->format('D j M').'</small>')
                ->addColumn('action', function ($row) {
                    return '<a href="'.route('orders.show', $row->id).'" class="btn btn-soft-secondary btn-sm"><i class="ri-eye-fill align-bottom me-1 text-muted"></i> View</a>';
                })
                ->rawColumns(['number', 'customer', 'payment', 'status', 'date', 'action'])
                ->make(true);
        }

        return view('admin.orders.index');
    }

    public function show(int $id)
    {
        $order = Order::with(['items', 'histories.changer', 'status', 'user'])->findOrFail($id);
        $statuses = OrderStatus::ordered();

        return view('admin.orders.show', compact('order', 'statuses'));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|string|exists:order_statuses,slug',
            'note' => 'nullable|string|max:255',
        ]);

        $changed = $order->changeStatus($data['status'], auth()->id(), $data['note'] ?? null);

        return redirect()->route('orders.show', $order->id)->with(
            'status',
            $changed ? 'Order moved to '.$changed->toStatus()->name.'.' : 'Order is already '.$order->status->name.'.'
        );
    }
}
