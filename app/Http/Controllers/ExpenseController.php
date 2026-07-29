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

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $scopedLocationId = $request->user()->scopedLocationId();
        $locations = $scopedLocationId === null
            ? Location::where('active', true)->orderBy('name')->get()
            : Location::where('id', $scopedLocationId)->get();

        if ($redirect = $this->requireLocationExists($locations, 'expenses.index')) {
            return $redirect;
        }

        $categories = ExpenseCategory::where('active', true)->orderBy('name')->get();

        if ($redirect = $this->requireNotEmpty($categories, 'expense-categories.index', 'You need at least one Expense Category before you can record an expense. Ask an Owner to add one.')) {
            return $redirect;
        }

        // Only offer suppliers this expense's location could actually be
        // saved with (own location, or shared/no-location suppliers) —
        // otherwise the dropdown offers choices that fail validation.
        $supplierQuery = Supplier::where('active', true);
        if ($scopedLocationId !== null) {
            $supplierQuery->where(fn ($q) => $q->where('location_id', $scopedLocationId)->orWhereNull('location_id'));
        }

        return view('expenses.create', [
            'locations' => $locations,
            'categories' => $categories,
            'suppliers' => $supplierQuery->orderBy('name')->get(),
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
