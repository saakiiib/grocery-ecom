<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OptionGroup;
use App\Models\OptionValue;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OptionGroupController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = OptionGroup::withCount('values')
                ->select(['id', 'name', 'slug', 'type', 'status', 'sort_order'])
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type', function ($row) {
                    return $row->type === 'dropdown' ? 'Dropdown' : 'Buttons';
                })
                ->addColumn('values_count', function ($row) {
                    return $row->values_count
                        ? '<a href="'.route('option-groups.manage', $row->id).'">'.$row->values_count.' values</a>'
                        : '<span class="text-muted">0</span>';
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';

                    return '<div class="form-check form-switch" dir="ltr">
                                <input type="checkbox" class="form-check-input toggle-status"
                                      id="customSwitchStatus'.$row->id.'" data-id="'.$row->id.'" '.$checked.'>
                                <label class="form-check-label" for="customSwitchStatus'.$row->id.'"></label>
                            </div>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="'.route('option-groups.manage', $row->id).'">
                                        <i class="ri-list-check align-bottom me-2 text-muted"></i> '.'Manage Values'.'
                                    </a>
                                </li>
                                <li>
                                    <button class="dropdown-item" id="EditBtn" rid="'.$row->id.'">
                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> '.'Edit'.'
                                    </button>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item deleteBtn"
                                        data-delete-url="'.route('option-groups.delete', $row->id).'"
                                        data-method="DELETE"
                                        data-table="#optionGroupTable">
                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> '.'Delete'.'
                                    </button>
                                </li>
                            </ul>
                        </div>
                    ';
                })
                ->rawColumns(['values_count', 'status', 'action'])
                ->make(true);
        }

        return view('admin.option-groups.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:option_groups,name',
            'type' => 'required|in:buttons,dropdown',
        ], [
            'name.required' => 'Option group name is required',
            'name.unique' => 'This option group already exists',
            'type.required' => 'Display type is required',
            'type.in' => 'Display type must be buttons or dropdown',
        ]);

        $data = new OptionGroup;
        $data->name = $request->name;
        $data->slug = $this->uniqueSlug($request->name, OptionGroup::class);
        $data->type = $request->type;
        $data->sort_order = (OptionGroup::max('sort_order') ?? -1) + 1;

        if ($data->save()) {
            return response()->json([
                'message' => 'Option group created successfully',
                'group' => $data,
            ], 200);
        }

        return response()->json([
            'message' => 'Error creating option group',
        ], 500);
    }

    public function edit($id)
    {
        $info = OptionGroup::findOrFail($id);

        return response()->json($info);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:option_groups,name,'.$request->codeid,
            'type' => 'required|in:buttons,dropdown',
        ], [
            'name.required' => 'Option group name is required',
            'name.unique' => 'This option group already exists',
            'type.required' => 'Display type is required',
            'type.in' => 'Display type must be buttons or dropdown',
        ]);

        $data = OptionGroup::findOrFail($request->codeid);
        $data->name = $request->name;
        // Slug always follows the latest name.
        $data->slug = $this->uniqueSlug($request->name, OptionGroup::class, $data->id);
        $data->type = $request->type;

        if ($data->save()) {
            return response()->json([
                'message' => 'Option group updated successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Error updating option group',
        ], 500);
    }

    public function delete($id)
    {
        $data = OptionGroup::find($id);

        if (! $data) {
            return response()->json([
                'message' => 'Option group not found',
            ], 404);
        }

        if ($data->categories()->exists() || $data->products()->exists()) {
            return response()->json([
                'message' => 'This group is used in a category template or product override — remove it there first',
            ], 422);
        }

        if (OptionValue::where('option_group_id', $id)->whereHas('variants')->exists()) {
            return response()->json([
                'message' => 'Some values of this group are used by product variants — remove them from the variants first',
            ], 422);
        }

        // Unused values cascade away with the group.
        if ($data->delete()) {
            return response()->json([
                'message' => 'Option group deleted successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Error deleting option group',
        ], 500);
    }

    public function toggleStatus(Request $request)
    {
        $group = OptionGroup::find($request->group_id);

        if (! $group) {
            return response()->json([
                'message' => 'Option group not found',
            ], 404);
        }

        $group->status = $request->status;

        if ($group->save()) {
            return response()->json([
                'message' => 'Status updated successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Error updating status',
        ], 500);
    }

    public function sortList()
    {
        $groups = OptionGroup::withCount('values')
            ->select(['id', 'name', 'type', 'sort_order'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($groups);
    }

    public function sortUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ]);

        foreach ($request->ids as $index => $id) {
            OptionGroup::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Sort order updated successfully']);
    }

    public function manage($id)
    {
        $group = OptionGroup::with(['values' => fn ($q) => $q->withCount('variants')->orderBy('sort_order')->orderBy('id')])->findOrFail($id);

        return view('admin.option-groups.manage', compact('group'));
    }

    public function storeValue(Request $request, $groupId)
    {
        $group = OptionGroup::findOrFail($groupId);

        $request->validate([
            'label' => 'required',
        ], [
            'label.required' => 'Value label is required',
        ]);

        $slug = $this->uniqueValueSlug($request->label, $groupId);

        if (OptionValue::where('option_group_id', $groupId)->where('label', $request->label)->exists()) {
            return response()->json([
                'message' => 'This value already exists in '.$group->name,
            ], 422);
        }

        $value = new OptionValue;
        $value->option_group_id = $groupId;
        $value->label = $request->label;
        $value->slug = $slug;
        $value->sort_order = (OptionValue::where('option_group_id', $groupId)->max('sort_order') ?? -1) + 1;

        if ($value->save()) {
            return response()->json([
                'message' => 'Value added successfully',
                'value' => $value,
            ], 200);
        }

        return response()->json([
            'message' => 'Error adding value',
        ], 500);
    }

    public function updateValue(Request $request, $id)
    {
        $value = OptionValue::findOrFail($id);

        $request->validate([
            'label' => [
                'required',
                Rule::unique('option_values', 'label')
                    ->where('option_group_id', $value->option_group_id)
                    ->ignore($value->id),
            ],
        ], [
            'label.required' => 'Value label is required',
            'label.unique' => 'This value already exists in this group',
        ]);

        $value->label = $request->label;
        $value->slug = $this->uniqueValueSlug($request->label, $value->option_group_id, $value->id);

        if ($value->save()) {
            return response()->json([
                'message' => 'Value updated successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Error updating value',
        ], 500);
    }

    public function deleteValue($id)
    {
        $value = OptionValue::find($id);

        if (! $value) {
            return response()->json([
                'message' => 'Value not found',
            ], 404);
        }

        if ($value->variants()->exists()) {
            return response()->json([
                'message' => 'This value is used by product variants — remove it from the variants first',
            ], 422);
        }

        if ($value->delete()) {
            return response()->json([
                'message' => 'Value deleted successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Error deleting value',
        ], 500);
    }

    public function toggleValueStatus(Request $request)
    {
        $value = OptionValue::find($request->value_id);

        if (! $value) {
            return response()->json([
                'message' => 'Value not found',
            ], 404);
        }

        $value->status = $request->status;

        if ($value->save()) {
            return response()->json([
                'message' => 'Status updated successfully',
            ], 200);
        }

        return response()->json([
            'message' => 'Error updating status',
        ], 500);
    }

    public function sortValuesUpdate(Request $request, $groupId)
    {
        $request->validate([
            'ids' => 'required|array',
        ]);

        foreach ($request->ids as $index => $id) {
            OptionValue::where('option_group_id', $groupId)->where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Sort order updated successfully']);
    }

    /**
     * Unique value slug scoped to one group (mirrors base uniqueSlug, but
     * option_values are unique per group, not globally).
     */
    protected function uniqueValueSlug(string $label, int $groupId, ?int $ignoreId = null): string
    {
        $base = Str::slug($label);
        $slug = $base;
        $i = 1;

        while (OptionValue::where('option_group_id', $groupId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
