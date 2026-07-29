<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->hasRole(UserRole::OWNER, UserRole::MANAGER, UserRole::ACCOUNTANT), 403);

        $query = AuditLog::with(['user', 'location'])->latest('created_at');

        if ($locationId = $user->scopedLocationId()) {
            $query->where('location_id', $locationId);
        }

        $logs = $query->paginate(25);

        return view('audit-logs.index', ['logs' => $logs]);
    }
}
