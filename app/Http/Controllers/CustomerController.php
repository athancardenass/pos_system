<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::query()->create($this->validated($request));

        AuditLogger::record('create', 'customer', $customer->customer_id, 'Created customer '.$customer->fullName());

        return redirect()->route('customers.index')->with('status', 'Customer created.');
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));

        AuditLogger::record('update', 'customer', $customer->customer_id, 'Updated customer '.$customer->fullName());

        return redirect()->route('customers.index')->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->saleTransactions()->exists()) {
            return back()->with('error', 'Cannot delete a customer with sales history. Set them inactive instead.');
        }

        $id = $customer->customer_id;
        $name = $customer->fullName();
        $customer->delete();

        AuditLogger::record('delete', 'customer', $id, 'Deleted customer '.$name);

        return redirect()->route('customers.index')->with('status', 'Customer deleted.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'customer_status' => 'required|in:active,inactive',
        ]);
    }
}
