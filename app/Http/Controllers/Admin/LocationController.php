<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Models\Location;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Location::class);

        $locations = Location::orderBy('name')->paginate(20);

        return view('admin.locations.index', ['locations' => $locations]);
    }

    public function create(): View
    {
        $this->authorize('create', Location::class);

        return view('admin.locations.create');
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $location = Location::create($request->validated());

        AuditLogService::record(AuditAction::CREATED, $location, null, $location->toArray(), locationId: $location->id);

        return redirect()->route('admin.locations.show', $location)->with('status', 'Location created successfully.');
    }

    public function show(Location $location): View
    {
        $this->authorize('view', $location);

        return view('admin.locations.show', ['location' => $location]);
    }

    public function edit(Location $location): View
    {
        $this->authorize('update', $location);

        return view('admin.locations.edit', ['location' => $location]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $old = $location->toArray();
        $location->update($request->validated());

        AuditLogService::record(AuditAction::UPDATED, $location, $old, $location->toArray(), locationId: $location->id);

        return redirect()->route('admin.locations.show', $location)->with('status', 'Location updated successfully.');
    }
}
