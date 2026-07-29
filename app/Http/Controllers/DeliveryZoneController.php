<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryZoneRequest;
use App\Models\DeliveryZone;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryZoneController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DeliveryZone::class);

        $user = $request->user();
        $query = DeliveryZone::query()->with('location');

        if (!$user->isAdmin()) {
            $query->where('location_id', $user->location_id);
        }

        return view('delivery-zones.index', ['zones' => $query->orderBy('name')->get()]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', DeliveryZone::class);

        $user = $request->user();
        $locations = $user->isAdmin()
            ? Location::where('active', true)->orderBy('name')->get()
            : Location::where('id', $user->location_id)->get();

        return view('delivery-zones.create', ['locations' => $locations]);
    }

    public function store(StoreDeliveryZoneRequest $request): RedirectResponse
    {
        DeliveryZone::create($request->validated());

        return redirect()->route('delivery-zones.index')->with('status', 'Delivery zone created successfully.');
    }
}
