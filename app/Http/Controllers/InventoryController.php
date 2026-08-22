<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $items = Inventory::query()
            ->with('product')
            ->orderBy('inventory_id')
            ->paginate(15);

        return view('inventory.index', compact('items'));
    }

    public function edit(Inventory $inventory): View
    {
        $inventory->load('product');

        return view('inventory.edit', compact('inventory'));
    }

    public function update(Request $request, Inventory $inventory): RedirectResponse
    {
        $data = $request->validate([
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $inventory->update([
            'stock_quantity' => $data['stock_quantity'],
            'last_restocked' => now(),
        ]);

        AuditLogger::record(
            'update',
            'inventory',
            $inventory->inventory_id,
            'Set stock for '.$inventory->product?->product_name.' to '.$data['stock_quantity'],
        );

        return redirect()->route('inventory.index')->with('status', 'Stock updated.');
    }

    public function storeMissing(): RedirectResponse
    {
        $created = 0;

        Product::query()
            ->whereDoesntHave('inventory')
            ->each(function (Product $product) use (&$created): void {
                Inventory::query()->create([
                    'product_id' => $product->product_id,
                    'stock_quantity' => 0,
                ]);
                $created++;
            });

        return redirect()->route('inventory.index')->with(
            'status',
            $created ? "Created {$created} missing inventory row(s)." : 'Every product already has an inventory row.',
        );
    }
}
