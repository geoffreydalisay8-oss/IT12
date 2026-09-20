<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{

    public function index()
    {
        $suppliers = Supplier::latest()->get();
        return view('suppliers.index', [
            'suppliers' => $suppliers
        ]);
    }

    public function create()
    {
        return view('suppliers.create');
    }

 
    public function store(Request $request)
    {
    
        $request->validate([
            'supplier_name' => 'required|string|max:100',
            'contact_info'  => 'nullable|string|max:100',
            'location'      => 'nullable|string|max:255',
        ]);

        $supplier = new Supplier();

        $supplier->supplier_name = $request->input('supplier_name');
        $supplier->contact_info  = $request->input('contact_info');
        $supplier->location      = $request->input('location');

        $supplier->save();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier added successfully.');
    }

    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('suppliers.show', [
            'supplier' => $supplier
        ]);
    }

    
    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('suppliers.edit', [
            'supplier' => $supplier
        ]);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:100',
            'contact_info'  => 'nullable|string|max:100',
            'location'      => 'nullable|string|max:255',
        ]);

        $supplier = Supplier::findOrFail($id);

        $supplier->supplier_name = $request->input('supplier_name');
        $supplier->contact_info  = $request->input('contact_info');
        $supplier->location      = $request->input('location');

        $supplier->save();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

   
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}