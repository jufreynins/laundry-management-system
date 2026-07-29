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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('location');

        if ($locationId = $request->user()->scopedLocationId()) {
            $query->where('location_id', $locationId);
        }

        $users = $query->orderBy('name')->paginate(20);

        return view('admin.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $scopedLocationId = auth()->user()->scopedLocationId();
        $locations = $scopedLocationId === null
            ? Location::where('active', true)->orderBy('name')->get()
            : Location::where('id', $scopedLocationId)->get();

        return view('admin.users.create', ['locations' => $locations]);
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

        $scopedLocationId = auth()->user()->scopedLocationId();
        $locations = $scopedLocationId === null
            ? Location::where('active', true)->orderBy('name')->get()
            : Location::where('id', $scopedLocationId)->get();

        return view('admin.users.edit', ['user' => $user, 'locations' => $locations]);
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
