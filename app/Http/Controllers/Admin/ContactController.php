<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $contacts = Contact::select(['id', 'name', 'email', 'phone', 'postcode', 'subject', 'topic', 'message', 'status', 'created_at'])->orderByDesc('created_at');

            return DataTables::of($contacts)
                ->addIndexColumn()
                ->addColumn('message', function ($row) {
                    return strlen($row->message) > 60 ? substr($row->message, 0, 60).'...' : $row->message;
                })
                ->addColumn('date', function ($row) {
                    return $row->created_at->format('d M Y, h:i A');
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';

                    return '
                        <div class="form-check form-switch" dir="ltr">
                            <input type="checkbox" class="form-check-input toggle-status"
                                id="status'.$row->id.'"
                                data-id="'.$row->id.'" '.$checked.'>
                            <label class="form-check-label" for="status'.$row->id.'"></label>
                        </div>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item view-btn" data-id="'.$row->id.'" data-url="'.route('admin.contacts.show', $row->id).'">
                                        <i class="ri-eye-fill align-bottom me-2 text-muted"></i> View
                                    </button>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item deleteBtn" data-delete-url="'.route('admin.contacts.delete', $row->id).'" data-method="DELETE" data-table="#contactTable">
                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                    </button>
                                </li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('admin.contacts.index');
    }

    public function show($id)
    {
        $contact = Contact::findOrFail($id);

        return response()->json(['success' => true, 'data' => $contact]);
    }

    public function toggleStatus(Request $request)
    {
        $contact = Contact::findOrFail($request->id);
        $contact->update(['status' => ! $contact->status]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }

    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->json(['success' => true, 'message' => 'Contact deleted successfully.']);
    }
}
