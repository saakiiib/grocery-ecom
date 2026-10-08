<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RepeatSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RepeatController extends Controller
{
    public function index()
    {
        $repeats = RepeatSchedule::with('user:id,name')->orderByDesc('id')->paginate(25);

        return view('admin.repeats.index', compact('repeats'));
    }

    public function toggleStatus(Request $request): RedirectResponse
    {
        $schedule = RepeatSchedule::findOrFail($request->input('id'));
        $schedule->update(['is_active' => ! $schedule->is_active]);

        return redirect()->route('repeats.index')->with('status', 'Repeat updated.');
    }

    public function destroy($id): RedirectResponse
    {
        RepeatSchedule::findOrFail($id)->delete();

        return redirect()->route('repeats.index')->with('status', 'Repeat deleted.');
    }
}
