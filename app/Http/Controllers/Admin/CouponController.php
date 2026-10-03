<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Coupon::withCount('orders')->orderByDesc('id'))
                ->addIndexColumn()
                ->addColumn('value', fn ($row) => $row->type === 'fixed' ? '£'.number_format($row->value, 2).' off' : rtrim(rtrim((string) $row->value, '0'), '.').'% off')
                ->addColumn('uses', function ($row) {
                    $used = $row->orders_count;
                    $cap = $row->max_uses !== null ? ' / '.$row->max_uses : '';

                    return '<a href="'.route('coupons.show', $row->id).'">'.$used.$cap.'</a>';
                })
                ->addColumn('expiry', fn ($row) => $row->expires_at ? $row->expires_at->format('d M Y') : '<span class="text-muted">No expiry</span>')
                ->addColumn('status', fn ($row) => '<div class="form-check form-switch"><input type="checkbox" class="form-check-input toggle-status" data-id="'.$row->id.'" '.($row->status ? 'checked' : '').'></div>')
                ->addColumn('action', fn ($row) => '<a class="btn btn-sm btn-soft-secondary" href="'.route('coupons.show', $row->id).'"><i class="ri-eye-fill"></i> Usage</a> <button class="btn btn-sm btn-soft-secondary editBtn" data-id="'.$row->id.'"><i class="ri-pencil-fill"></i> Edit</button> <button class="btn btn-sm btn-soft-danger deleteBtn" data-delete-url="'.route('coupons.delete', $row->id).'" data-method="DELETE" data-table="#couponTable"><i class="ri-delete-bin-fill"></i></button>')
                ->rawColumns(['uses', 'expiry', 'status', 'action'])
                ->make(true);
        }

        return view('admin.coupons.index');
    }

    public function show(int $id)
    {
        $coupon = Coupon::withCount('orders')->findOrFail($id);
        $orders = $coupon->orders()->with('user:id,name,email')->orderByDesc('id')->paginate(25);

        return view('admin.coupons.show', compact('coupon', 'orders'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = strtoupper(trim($data['code']));
        Coupon::create($data);

        return response()->json(['message' => 'Coupon created.']);
    }

    public function edit(int $id)
    {
        return response()->json(Coupon::findOrFail($id));
    }

    public function update(Request $request)
    {
        $coupon = Coupon::findOrFail($request->id);
        $data = $this->validated($request, $coupon->id);
        $data['code'] = strtoupper(trim($data['code']));
        $coupon->update($data);

        return response()->json(['message' => 'Coupon updated.']);
    }

    public function destroy(int $id)
    {
        Coupon::findOrFail($id)->delete();

        return response()->json(['message' => 'Coupon deleted. Past orders keep their stored code.']);
    }

    public function toggleStatus(Request $request)
    {
        $coupon = Coupon::findOrFail($request->id);
        $coupon->update(['status' => ! $coupon->status]);

        return response()->json(['message' => 'Coupon '.($coupon->status ? 'enabled' : 'disabled').'.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,'.$ignoreId,
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0.01|max:100000',
            'min_order' => 'nullable|numeric|min:0|max:100000',
            'expires_at' => 'nullable|date',
            'max_uses' => 'nullable|integer|min:1|max:1000000',
            'max_per_user' => 'required|integer|min:1|max:1000000',
            'status' => 'nullable|boolean',
        ]);
        if ($data['type'] === 'percent' && $data['value'] > 100) {
            throw ValidationException::withMessages(['value' => 'Percent coupons cannot exceed 100.']);
        }

        return $data + ['status' => $request->boolean('status', true)];
    }
}
