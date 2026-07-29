<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordInventoryTransactionRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\Supplier;
use App\Services\InventoryException;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', InventoryItem::class);

        $user = $request->user();
        $query = InventoryItem::query()->with('supplier');

        if ($locationId = $user->scopedLocationId()) {
            $query->where('location_id', $locationId);
        }

        $items = $query->orderBy('name')->paginate(20);

        return view('inventory.index', ['items' => $items]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', InventoryItem::class);

        $scopedLocationId = $request->user()->scopedLocationId();
        $locations = $scopedLocationId === null
            ? Location::where('active', true)->orderBy('name')->get()
            : Location::where('id', $scopedLocationId)->get();

        if ($redirect = $this->requireLocationExists($locations, 'inventory.index')) {
            return $redirect;
        }

        return view('inventory.create', [
            'locations' => $locations,
            'suppliers' => Supplier::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($scopedLocationId = $request->user()->scopedLocationId()) {
            $data['location_id'] = $scopedLocationId;
        }

        $item = InventoryItem::create($data);

        return redirect()->route('inventory.show', $item)->with('status', 'Inventory item created successfully.');
    }

    public function show(InventoryItem $item): View
    {
        $this->authorize('view', $item);

        return view('inventory.show', ['item' => $item->load(['transactions.recordedBy', 'supplier'])]);
    }

    public function recordTransaction(RecordInventoryTransactionRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        try {
            $this->inventoryService->recordTransaction(
                $inventoryItem,
                \App\Enums\InventoryTransactionType::from($request->validated('type')),
                number_format((float) $request->validated('quantity'), 2, '.', ''),
                $request->validated('reason'),
                $request->user(),
            );
        } catch (InventoryException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return redirect()->route('inventory.show', $inventoryItem)->with('status', 'Inventory transaction recorded.');
    }
}
