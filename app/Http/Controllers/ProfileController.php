<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($user->id)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'contact' => $validated['phone'] ?? ($validated['email'] ?? null),
        ]);

        if ($request->hasFile('profile_photo')) {
            $uploadDirectory = public_path('uploads/profile-photos');

            if (! File::isDirectory($uploadDirectory)) {
                File::makeDirectory($uploadDirectory, 0755, true);
            }

            $photo = $request->file('profile_photo');
            $filename = 'user-'.$user->id.'-'.time().'.'.$photo->getClientOriginalExtension();
            $photo->move($uploadDirectory, $filename);

            if ($user->profile_photo_path) {
                $oldPhotoPath = public_path($user->profile_photo_path);

                if (File::exists($oldPhotoPath)) {
                    File::delete($oldPhotoPath);
                }
            }

            $user->profile_photo_path = 'uploads/profile-photos/'.$filename;
        }

        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Your profile has been updated successfully.');
    }
}
