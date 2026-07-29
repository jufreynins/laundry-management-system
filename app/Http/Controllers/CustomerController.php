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

        if (!$user->isAdmin()) {
            $query->where('location_id', $user->location_id);
        }

        if ($search = $request->string('q')->trim()->value()) {
            $query->search($search);
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('customers.index', ['customers' => $customers, 'search' => $search]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Customer::class);

        $locations = $request->user()->isAdmin()
            ? \App\Models\Location::where('active', true)->orderBy('name')->get()
            : \App\Models\Location::where('id', $request->user()->location_id)->get();

        return view('customers.create', ['locations' => $locations]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = $request->user();
        if (!$user->isAdmin()) {
            $data['location_id'] = $user->location_id;
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

        return view('customers.show', ['customer' => $customer]);
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
