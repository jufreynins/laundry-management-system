<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Guard for "create" forms that need at least one Location to exist
     * (customers, orders, suppliers, inventory, expenses, delivery zones).
     * Without this, the create form silently submits an empty location_id
     * and the user sees a confusing generic validation error instead of
     * being told what's actually missing.
     */
    protected function requireLocationExists(Collection $locations, string $redirectRouteName): ?RedirectResponse
    {
        if ($locations->isNotEmpty()) {
            return null;
        }

        return redirect()->route($redirectRouteName)
            ->with('status', 'You need to create a Location before you can add this. Go to Admin → Locations to create one.');
    }
}
