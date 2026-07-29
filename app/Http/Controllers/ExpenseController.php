<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Location;
use App\Models\Supplier;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $user = $request->user();
        $query = Expense::query()->with(['category', 'supplier'])->latest('expense_date');

        if ($locationId = $user->scopedLocationId()) {
            $query->where('location_id', $locationId);
        }

        return view('expenses.index', ['expenses' => $query->paginate(20)]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Expense::class);

        $scopedLocationId = $request->user()->scopedLocationId();
        $locations = $scopedLocationId === null
            ? Location::where('active', true)->orderBy('name')->get()
            : Location::where('id', $scopedLocationId)->get();

        return view('expenses.create', [
            'locations' => $locations,
            'categories' => ExpenseCategory::where('active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($scopedLocationId = $request->user()->scopedLocationId()) {
            $data['location_id'] = $scopedLocationId;
        }
        $data['recorded_by'] = $request->user()->id;

        $expense = Expense::create($data);

        AuditLogService::record(AuditAction::CREATED, $expense, null, $expense->toArray());

        return redirect()->route('expenses.index')->with('status', 'Expense recorded successfully.');
    }
}
