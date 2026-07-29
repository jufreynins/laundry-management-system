<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\PricingType;
use App\Enums\ServiceCategory;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::orderBy('category')->orderBy('name')->paginate(20);

        return view('services.index', ['services' => $services]);
    }

    public function create(): View
    {
        $this->authorize('create', Service::class);

        return view('services.create', [
            'categories' => ServiceCategory::cases(),
            'pricingTypes' => PricingType::cases(),
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $service = Service::create($request->validated());

        AuditLogService::record(AuditAction::CREATED, $service, null, $service->toArray());

        return redirect()->route('services.show', $service)->with('status', 'Service created successfully.');
    }

    public function show(Service $service): View
    {
        $this->authorize('view', $service);

        return view('services.show', ['service' => $service->load('servicePrices.location')]);
    }

    public function edit(Service $service): View
    {
        $this->authorize('update', $service);

        return view('services.edit', [
            'service' => $service,
            'categories' => ServiceCategory::cases(),
            'pricingTypes' => PricingType::cases(),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $old = $service->toArray();
        $service->update($request->validated());

        AuditLogService::record(AuditAction::UPDATED, $service, $old, $service->toArray());

        return redirect()->route('services.show', $service)->with('status', 'Service updated successfully.');
    }
}
