<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;

class AdminProfileController extends Controller
{
    public function index()
    {
        $admin = Auth::user();

        return view('admin.profile.index', compact('admin'));
    }

    public function update(Request $request)
    {
        $admin = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$admin->id,
            'password' => 'nullable|string|min:6|confirmed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'Name is required',
            'name.string' => 'Name must be a text',
            'name.max' => 'Name can be at most 255 characters',

            'email.email' => 'Enter a valid email',
            'email.unique' => 'This email is already in use',

            'password.string' => 'Password must be text',
            'password.min' => 'Password must be at least 6 characters',
            'password.confirmed' => 'Password confirmation does not match',

            'image.image' => 'The file must be an image',
            'image.mimes' => 'Image must be JPG, PNG, JPEG, or GIF',
            'image.max' => 'Image size can be maximum 2MB',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('image')) {
            // Only delete old image if it's not the placeholder
            if ($admin->image && $admin->image !== 'placeholder.webp' && file_exists(public_path($admin->image))) {
                @unlink(public_path($admin->image));
            }

            $randomName = mt_rand(10000000, 99999999).'.webp';
            $destinationPath = public_path('uploads/admins/');

            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            Image::make($request->file('image'))
                ->resize(800, null, fn ($c) => $c->aspectRatio())
                ->encode('webp', 50)
                ->save($destinationPath.$randomName)
                ->destroy();

            $data['image'] = '/uploads/admins/'.$randomName;
        } elseif ($request->boolean('remove_image')) {
            // Only delete old image if it's not the placeholder
            if ($admin->image && $admin->image !== 'placeholder.webp' && file_exists(public_path($admin->image))) {
                @unlink(public_path($admin->image));
            }

            $data['image'] = null;
        }

        $admin->update($data);

        return response()->json(['message' => 'Profile updated successfully']);
    }
}
