<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = AuditLog::with(['user', 'location'])->latest('created_at');

        if (!$user->isAdmin()) {
            $query->where('location_id', $user->location_id);
        }

        $logs = $query->paginate(25);

        return view('audit-logs.index', ['logs' => $logs]);
    }
}
