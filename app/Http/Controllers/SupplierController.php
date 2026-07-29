<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $user = $request->user();
        $query = Supplier::query();

        if ($locationId = $user->scopedLocationId()) {
            $query->where(function ($q) use ($locationId) {
                $q->where('location_id', $locationId)->orWhereNull('location_id');
            });
        }

        return view('suppliers.index', ['suppliers' => $query->orderBy('name')->paginate(20)]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Supplier::class);

        $scopedLocationId = $request->user()->scopedLocationId();
        $locations = $scopedLocationId === null
            ? \App\Models\Location::where('active', true)->orderBy('name')->get()
            : \App\Models\Location::where('id', $scopedLocationId)->get();

        return view('suppliers.create', ['locations' => $locations]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($scopedLocationId = $request->user()->scopedLocationId()) {
            $data['location_id'] = $scopedLocationId;
        }

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('status', 'Supplier created successfully.');
    }
}
