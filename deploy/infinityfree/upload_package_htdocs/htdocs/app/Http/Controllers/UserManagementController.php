<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $actor = auth()->user();
        abort_unless($actor && $actor->canManageUsers(), 403);

        $query = User::query()->with('creator');

        if ($actor->isClinician()) {
            $query->where('created_by', $actor->id);
        }

        $users = $query
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $actor = auth()->user();
        abort_unless($actor && $actor->canManageUsers(), 403);

        return view('admin.users.create', [
            'roles' => $actor->manageableRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor && $actor->canManageUsers(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone', 'required_without:email'],
            'role' => ['required', Rule::in($actor->manageableRoles())],
        ]);

        $defaultPassword = Str::random(10);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'contact' => $validated['phone'] ?? ($validated['email'] ?? null),
            'password' => $defaultPassword,
            'must_change_password' => true,
            'created_by' => $actor->id,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User added successfully. Share the temporary password securely because it cannot be viewed again.')
            ->with('generated_password', $defaultPassword);
    }

    public function edit(User $user)
    {
        $actor = auth()->user();
        abort_unless($actor && $actor->canManageUsers() && $this->canManageTarget($actor, $user), 403);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $actor->manageableRoles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($actor && $actor->canManageUsers() && $this->canManageTarget($actor, $user), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($user->id), 'required_without:email'],
            'role' => ['required', Rule::in($actor->manageableRoles())],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'contact' => $validated['phone'] ?? ($validated['email'] ?? null),
        ]);

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        $actor = $request->user();

        abort_unless($actor && $actor->isAdministrator(), 403);

        if ($actor->is($user)) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'You cannot delete the account you are currently using.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function canManageTarget(User $actor, User $target): bool
    {
        if ($actor->isAdministrator()) {
            return true;
        }

        return $actor->isClinician()
            && in_array($target->role, $actor->manageableRoles(), true)
            && $target->created_by === $actor->id;
    }
}
