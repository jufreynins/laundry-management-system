<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessSettingsController extends Controller
{
    private const KEYS = [
        'sales_tax_enabled',
        'tax_rate',
        'minimum_order_amount',
        'business_hours_open',
        'business_hours_close',
        'terms_notice',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\BusinessSettings::class);

        $scopedLocationId = $request->user()->scopedLocationId();
        $locations = $scopedLocationId === null
            ? Location::where('active', true)->orderBy('name')->get()
            : Location::where('id', $scopedLocationId)->get();

        $requestedLocationId = $request->integer('location_id') ?: null;
        $location = $requestedLocationId
            ? $locations->firstWhere('id', $requestedLocationId)
            : $locations->first();

        abort_if($location === null, 403);

        $this->authorize('view', new \App\Models\BusinessSettings(['location_id' => $location->id]));

        $settings = [];
        foreach (self::KEYS as $key) {
            $settings[$key] = $location->setting($key);
        }

        return view('admin.settings.index', [
            'location' => $location,
            'locations' => $locations,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $this->authorize('update', new \App\Models\BusinessSettings(['location_id' => $location->id]));

        $data = $request->validate([
            'sales_tax_enabled' => ['nullable', 'boolean'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'business_hours_open' => ['nullable', 'date_format:H:i'],
            'business_hours_close' => ['nullable', 'date_format:H:i'],
            'terms_notice' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach (self::KEYS as $key) {
            $value = $data[$key] ?? null;
            if ($key === 'sales_tax_enabled') {
                $value = $request->boolean('sales_tax_enabled') ? '1' : '0';
            }
            if ($value !== null) {
                $location->setSetting($key, (string) $value);
            }
        }

        AuditLogService::record(
            AuditAction::UPDATED,
            $location,
            null,
            $data,
            'Business settings updated',
            $location->id
        );

        return redirect()->route('admin.settings.index', ['location_id' => $location->id])
            ->with('status', 'Settings updated successfully.');
    }
}
