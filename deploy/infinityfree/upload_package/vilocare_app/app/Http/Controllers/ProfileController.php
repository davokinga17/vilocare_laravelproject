<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'captured_profile_photo' => ['nullable', 'string'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'contact' => $validated['phone'] ?? ($validated['email'] ?? null),
        ]);

        if (filled($validated['captured_profile_photo'] ?? null)) {
            $user->profile_photo_path = $this->storeCapturedPhoto($user->id, (string) $validated['captured_profile_photo'], $user->profile_photo_path);
        } elseif ($request->hasFile('profile_photo')) {
            $user->profile_photo_path = $this->storeUploadedPhoto($user->id, $request->file('profile_photo'), $user->profile_photo_path);
        }

        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Your profile has been updated successfully.');
    }

    private function storeUploadedPhoto(int $userId, \Illuminate\Http\UploadedFile $photo, ?string $currentPath): string
    {
        $uploadDirectory = $this->ensureUploadDirectory();
        $filename = 'user-'.$userId.'-'.time().'.'.$photo->getClientOriginalExtension();
        $photo->move($uploadDirectory, $filename);
        $this->deleteOldPhoto($currentPath);

        return 'uploads/profile-photos/'.$filename;
    }

    private function storeCapturedPhoto(int $userId, string $payload, ?string $currentPath): string
    {
        if (! preg_match('/^data:(image\/(?:jpeg|jpg|png|webp));base64,(.+)$/', $payload, $matches)) {
            throw ValidationException::withMessages([
                'captured_profile_photo' => ['The captured profile photo format is invalid.'],
            ]);
        }

        $binary = base64_decode(str_replace(' ', '+', $matches[2]), true);

        if ($binary === false) {
            throw ValidationException::withMessages([
                'captured_profile_photo' => ['The captured profile photo could not be decoded.'],
            ]);
        }

        if (strlen($binary) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'captured_profile_photo' => ['The captured profile photo is too large.'],
            ]);
        }

        $imageInfo = @getimagesizefromstring($binary);

        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                'captured_profile_photo' => ['The captured profile photo is not a valid image.'],
            ]);
        }

        $extension = match ($matches[1]) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $uploadDirectory = $this->ensureUploadDirectory();
        $filename = 'user-'.$userId.'-'.time().'-capture.'.Str::lower($extension);
        file_put_contents($uploadDirectory.DIRECTORY_SEPARATOR.$filename, $binary);
        $this->deleteOldPhoto($currentPath);

        return 'uploads/profile-photos/'.$filename;
    }

    private function ensureUploadDirectory(): string
    {
        $uploadDirectory = public_path('uploads/profile-photos');

        if (! File::isDirectory($uploadDirectory)) {
            File::makeDirectory($uploadDirectory, 0755, true);
        }

        return $uploadDirectory;
    }

    private function deleteOldPhoto(?string $currentPath): void
    {
        if (! $currentPath) {
            return;
        }

        $oldPhotoPath = public_path($currentPath);

        if (File::exists($oldPhotoPath)) {
            File::delete($oldPhotoPath);
        }
    }
}
