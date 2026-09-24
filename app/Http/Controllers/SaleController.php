<?php

namespace App\Http\Controllers;

// Only import the models needed for processing sales and inventory
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    // =========================================================
    // 1. DISPLAY ALL SALES
    // =========================================================
    public function index()
    {
        // Query the sales table directly without eager loading (with())
        // Order by the newest record first
        $sales = Sale::latest()->get();

        // Send the retrieved sales collection to the view
        return view('sales.index', [
            'sales' => $sales
        ]);
    }


    // =========================================================
    // 2. DISPLAY CREATE SALE FORM (POS Screen)
    // =========================================================
    public function create()
    {
        // Fetch only products that are currently in stock (quantity > 0)
        // Sort them alphabetically by product name
        $products = Product::where('stock_quantity', '>', 0)
            ->orderBy('product_name')
            ->get();

        // Send available products to the POS Blade view
        return view('sales.create', [
            'products' => $products
        ]);
    }


    // =========================================================
    // 3. SAVE A NEW SALE
    // =========================================================
    public function store(Request $request)
    {
        // Step 1: Validate input fields submitted from the form
        $request->validate([
            'payment_method'      => 'required|string|max:50', // e.g., Cash, GCash, Card
            'products'            => 'required|array|min:1',  // At least 1 item must be selected
            'products.*.id'       => 'required|exists:products,id', // Must be a valid product ID
            'products.*.quantity' => 'required|integer|min:1',      // Quantity must be 1 or higher
        ]);

        // Step 2: Use a Database Transaction
        // Ensures that creating the sale, adding items, and updating stock
        // either ALL succeed together or ALL roll back if an error occurs.
        DB::transaction(function () use ($request) {

            // Create and initialize a new Sale header record
            $sale = new Sale();
            $sale->user_id        = auth()->id(); // ID of logged-in cashier/staff
            $sale->sale_date      = now();        // Current timestamp
            $sale->total_amount   = 0;            // Set placeholder total (calculated below)
            $sale->payment_method = $request->input('payment_method');
            $sale->status         = 'completed';  // Mark new sales as completed
            $sale->save();                        // Save first to generate $sale->id

            $totalAmount   = 0;
            $productsInput = $request->input('products');

            // Step 3: Loop through each item in the cart array
            foreach ($productsInput as $index => $item) {

                // Retrieve product directly by ID
                $product = Product::findOrFail($item['id']);

                // Verify stock availability
                if ($product->stock_quantity < $item['quantity']) {
                    // Throw a validation exception to abort transaction and return to form with message
                    throw ValidationException::withMessages([
                        "products.{$index}.quantity" => [
                            "Not enough stock for {$product->product_name}. Available: {$product->stock_quantity}, Requested: {$item['quantity']}."
                        ]
                    ]);
                }

                // Calculate total for this specific line item
                $subtotal = $product->sell_price * $item['quantity'];

                // Create individual SaleItem record
                $saleItem             = new SaleItem();
                $saleItem->sale_id    = $sale->id;
                $saleItem->product_id = $product->id;
                $saleItem->quantity   = $item['quantity'];
                $saleItem->unit_price = $product->sell_price; // Save unit price at time of purchase
                $saleItem->subtotal   = $subtotal;
                $saleItem->save();

                // Deduct purchased quantity from product inventory
                $product->stock_quantity -= $item['quantity'];
                $product->save();

                // Accumulate grand total
                $totalAmount += $subtotal;
            }

            // Update the Sale header with the calculated final total
            $sale->total_amount = $totalAmount;
            $sale->save();
        });

        // Redirect back to sales list with success notification
        return redirect()
            ->route('sales.index')
            ->with('success', 'Sale added successfully.');
    }


    // =========================================================
    // 4. DISPLAY ONE SALE
    // =========================================================
    public function show($id)
    {
        // Find the sale directly by primary key without relationship lookups
        $sale = Sale::findOrFail($id);

        return view('sales.show', [
            'sale' => $sale
        ]);
    }


    // =========================================================
    // 5. DISPLAY EDIT SALE FORM
    // =========================================================
    public function edit($id)
    {
        // Find the target sale record directly
        $sale = Sale::findOrFail($id);

        return view('sales.edit', [
            'sale' => $sale
        ]);
    }


    // =========================================================
    // 6. UPDATE AN EXISTING SALE
    // =========================================================
    public function update(Request $request, $id)
    {
        // Validate incoming request parameters
        $request->validate([
            'payment_method' => 'required|string|max:50',
            'status'         => 'required|string|in:completed,cancelled,refunded',
        ]);

        DB::transaction(function () use ($request, $id) {

            $sale      = Sale::findOrFail($id);
            $newStatus = $request->input('status');

            // Stock restoration logic:
            // If status changes from 'completed' to 'cancelled' or 'refunded',
            // return all purchased items back to the product stock.
            if (
                $sale->status === 'completed'
                && ($newStatus === 'cancelled' || $newStatus === 'refunded')
            ) {
                foreach ($sale->saleItems as $item) {
                    $product = Product::findOrFail($item->product_id);
                    $product->stock_quantity += $item->quantity; // Restock inventory
                    $product->save();
                }
            }

            // Update header fields and save changes
            $sale->payment_method = $request->input('payment_method');
            $sale->status         = $newStatus;
            $sale->save();
        });

        return redirect()
            ->route('sales.index')
            ->with('success', 'Sale updated successfully.');
    }


    // =========================================================
    // 7. DELETE A SALE
    // =========================================================
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {

            $sale = Sale::findOrFail($id);

            // If deleting a completed sale, restore items to stock first
            if ($sale->status === 'completed') {
                foreach ($sale->saleItems as $item) {
                    $product = Product::findOrFail($item->product_id);
                    $product->stock_quantity += $item->quantity;
                    $product->save();
                }
            }

            // Delete associated line items first, then delete the parent sale record
            $sale->saleItems()->delete();
            $sale->delete();
        });

        return redirect()
            ->route('sales.index')
            ->with('success', 'Sale deleted successfully.');
    }
}
