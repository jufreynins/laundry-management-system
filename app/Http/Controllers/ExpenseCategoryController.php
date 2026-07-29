<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole(UserRole::OWNER, UserRole::MANAGER), 403);

        return view('expense-categories.index', ['categories' => ExpenseCategory::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRole::OWNER), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
        ]);

        ExpenseCategory::create($data);

        return redirect()->route('expense-categories.index')->with('status', 'Expense category created.');
    }
}
