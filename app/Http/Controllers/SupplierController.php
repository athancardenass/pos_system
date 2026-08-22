<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        return view('suppliers.index', [
            'suppliers' => Supplier::query()->orderBy('supplier_name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $supplier = Supplier::query()->create($data);

        AuditLogger::record('create', 'supplier', $supplier->supplier_id, 'Created supplier '.$supplier->supplier_name);

        return redirect()->route('suppliers.index')->with('status', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        AuditLogger::record('update', 'supplier', $supplier->supplier_id, 'Updated supplier '.$supplier->supplier_name);

        return redirect()->route('suppliers.index')->with('status', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->products()->exists() || $supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Cannot delete a supplier that is linked to products or purchase orders.');
        }

        $id = $supplier->supplier_id;
        $name = $supplier->supplier_name;
        $supplier->delete();

        AuditLogger::record('delete', 'supplier', $id, 'Deleted supplier '.$name);

        return redirect()->route('suppliers.index')->with('status', 'Supplier deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_name' => 'required|string|max:100',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
        ]);
    }
}
