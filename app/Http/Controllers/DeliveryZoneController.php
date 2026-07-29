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

        if ($locationId = $user->scopedLocationId()) {
            $query->where('location_id', $locationId);
        }

        return view('delivery-zones.index', ['zones' => $query->orderBy('name')->get()]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', DeliveryZone::class);

        $scopedLocationId = $request->user()->scopedLocationId();
        $locations = $scopedLocationId === null
            ? Location::where('active', true)->orderBy('name')->get()
            : Location::where('id', $scopedLocationId)->get();

        if ($redirect = $this->requireLocationExists($locations, 'delivery-zones.index')) {
            return $redirect;
        }

        return view('delivery-zones.create', ['locations' => $locations]);
    }

    public function store(StoreDeliveryZoneRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($scopedLocationId = $request->user()->scopedLocationId()) {
            $data['location_id'] = $scopedLocationId;
        }

        DeliveryZone::create($data);

        return redirect()->route('delivery-zones.index')->with('status', 'Delivery zone created successfully.');
    }
}
