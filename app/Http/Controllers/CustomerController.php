<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $user = $request->user();
        $query = Customer::query()->with('location');

        if ($locationId = $user->scopedLocationId()) {
            $query->where('location_id', $locationId);
        }

        if ($search = $request->string('q')->trim()->value()) {
            $query->search($search);
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('customers.index', ['customers' => $customers, 'search' => $search]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $scopedLocationId = $request->user()->scopedLocationId();
        $locations = $scopedLocationId === null
            ? \App\Models\Location::where('active', true)->orderBy('name')->get()
            : \App\Models\Location::where('id', $scopedLocationId)->get();

        if ($redirect = $this->requireLocationExists($locations, 'customers.index')) {
            return $redirect;
        }

        return view('customers.create', ['locations' => $locations]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = $request->user();
        if ($scopedLocationId = $user->scopedLocationId()) {
            $data['location_id'] = $scopedLocationId;
        }

        $duplicates = Customer::findPossibleDuplicates($data['location_id'], $data['name'], $data['phone']);

        $customer = Customer::create($data);

        AuditLogService::record(AuditAction::CREATED, $customer, null, $customer->toArray());

        $message = 'Customer created successfully.';
        if ($duplicates->isNotEmpty()) {
            $message .= ' Warning: possible duplicate customer(s) found with the same name or phone number.';
        }

        return redirect()->route('customers.show', $customer)->with('status', $message);
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $orders = $customer->orders()->latest('intake_at')->paginate(10);

        return view('customers.show', ['customer' => $customer, 'orders' => $orders]);
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $old = $customer->toArray();
        $customer->update($request->validated());

        AuditLogService::record(AuditAction::UPDATED, $customer, $old, $customer->toArray());

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated successfully.');
    }
}
