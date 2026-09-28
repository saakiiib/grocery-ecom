<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $q = Faq::with('category:id,name')->orderBy('sort_order');
            if ($request->filled('faq_category_id')) {
                $q->where('faq_category_id', $request->faq_category_id);
            }

            return DataTables::of($q->get())
                ->addIndexColumn()
                ->addColumn('category', fn ($r) => $r->category?->name ?? '-')
                ->addColumn('question', fn ($r) => strlen($r->question) > 80 ? substr($r->question, 0, 80).'...' : $r->question)
                ->addColumn('status', fn ($r) => '<div class="form-check form-switch"><input type="checkbox" class="form-check-input toggle-status" data-id="'.$r->id.'" '.($r->status ? 'checked' : '').'></div>')
                ->addColumn('action', fn ($r) => '<button class="btn btn-sm btn-soft-secondary editBtn" data-id="'.$r->id.'"><i class="ri-pencil-fill"></i> Edit</button> <button class="btn btn-sm btn-soft-danger deleteBtn" data-delete-url="'.route('faqs.delete', $r->id).'" data-method="DELETE" data-table="#faqTable"><i class="ri-delete-bin-fill"></i></button>')
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $categories = FaqCategory::where('status', 1)->orderBy('sort_order')->get(['id', 'name']);

        return view('admin.faqs.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'faq_category_id' => 'nullable|exists:faq_categories,id',
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);
        Faq::create([
            ...$request->only(['faq_category_id', 'question', 'answer', 'badge']),
            'sort_order' => (int) (Faq::max('sort_order') ?? 0) + 1,
        ]);

        return response()->json(['message' => 'FAQ added']);
    }

    public function edit($id)
    {
        return response()->json(Faq::findOrFail($id));
    }

    public function update(Request $request)
    {
        $request->validate([
            'faq_category_id' => 'nullable|exists:faq_categories,id',
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);
        Faq::findOrFail($request->id)->update($request->only(['faq_category_id', 'question', 'answer', 'badge']));

        return response()->json(['message' => 'FAQ updated']);
    }

    public function destroy($id)
    {
        Faq::findOrFail($id)->delete();

        return response()->json(['message' => 'FAQ deleted']);
    }

    public function toggleStatus(Request $request)
    {
        $f = Faq::findOrFail($request->id);
        $f->update(['status' => ! $f->status]);

        return response()->json(['message' => 'Status updated']);
    }
}
