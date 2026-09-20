<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // =========================================================
    // 1. DISPLAY ALL CUSTOMERS
    // =========================================================
    public function index()
    {
        $customers = Customer::latest()->get();

        return view('customers.index', [
            'customers' => $customers
        ]);
    }

    // =========================================================
    // 2. DISPLAY CREATE CUSTOMER FORM
    // =========================================================
    public function create()
    {
        return view('customers.create');
    }

    // =========================================================
    // 3. SAVE A NEW CUSTOMER
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $customer = new Customer();

        $customer->name = $request->input('name');
        $customer->phone = $request->input('phone');
        $customer->address = $request->input('address');

        $customer->save();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer added successfully.');
    }

    // =========================================================
    // 4. DISPLAY ONE CUSTOMER
    // =========================================================
    public function show($id)
    {
        $customer = Customer::findOrFail($id);

        return view('customers.show', [
            'customer' => $customer
        ]);
    }

    // =========================================================
    // 5. DISPLAY EDIT CUSTOMER FORM
    // =========================================================
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);

        return view('customers.edit', [
            'customer' => $customer
        ]);
    }

    // =========================================================
    // 6. UPDATE AN EXISTING CUSTOMER
    // =========================================================
    public function update(Request $request, $id)
    {
        // Step 1: Validate request input
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $customer = Customer::findOrFail($id);

        $customer->name = $request->input('name');
        $customer->phone = $request->input('phone');
        $customer->address = $request->input('address');

        $customer->save();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    // =========================================================
    // 7. DELETE A CUSTOMER
    // =========================================================
    public function destroy($id)
    {
        // Explicitly fetch record before deleting
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}