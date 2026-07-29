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
        return $this->requireNotEmpty(
            $locations,
            $redirectRouteName,
            'You need to create a Location before you can add this. Go to Admin → Locations to create one.'
        );
    }

    /**
     * Generic guard for any "create" form whose required dropdown is
     * populated from a table that may legitimately be empty on a fresh
     * install (Services, Expense Categories, etc.) — same failure mode as
     * requireLocationExists: an empty <select> silently posts nothing for
     * a `required` field, producing a confusing generic validation error.
     */
    protected function requireNotEmpty(Collection $items, string $redirectRouteName, string $message): ?RedirectResponse
    {
        if ($items->isNotEmpty()) {
            return null;
        }

        return redirect()->route($redirectRouteName)->with('status', $message);
    }
}
