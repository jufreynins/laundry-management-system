<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Location;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('location')->orderBy('name')->paginate(20);

        return view('admin.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'locations' => Location::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        AuditLogService::record(AuditAction::CREATED, $user, null, ['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value]);

        return redirect()->route('admin.users.show', $user)->with('status', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('admin.users.show', ['user' => $user]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user,
            'locations' => Location::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $old = ['role' => $user->role->value, 'location_id' => $user->location_id, 'active' => $user->active];

        $user->update($request->validated());

        $roleChanged = $old['role'] !== $user->role->value;

        AuditLogService::record(
            $roleChanged ? AuditAction::ROLE_CHANGED : AuditAction::UPDATED,
            $user,
            $old,
            ['role' => $user->role->value, 'location_id' => $user->location_id, 'active' => $user->active]
        );

        return redirect()->route('admin.users.show', $user)->with('status', 'User updated successfully.');
    }
}
