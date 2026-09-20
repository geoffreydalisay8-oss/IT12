<?php

namespace App\Http\Controllers;
use App\Models\Customer;
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
        // Get all sales from the database.
        //
        // with() also loads the customer and user
        // related to each sale.
        $sales = Sale::with([
            'customer',
            'user'
        ])

        // Display the newest sales first.
        ->latest()

        // Execute the query and get the results.
        ->get();


        // Open the sales index page.
        //
        // Send the sales data to the Blade view.
        return view('sales.index', [
            'sales' => $sales
        ]);
    }


    // =========================================================
    // 2. DISPLAY CREATE SALE FORM (POS Screen)
    // =========================================================
    public function create()
    {
        // Get all customers.
        //
        // orderBy() sorts the customers alphabetically
        // according to their name.
        $customers = Customer::orderBy('name')->get();


        // Get only products that currently have stock.
        //
        // stock_quantity > 0 means:
        // only products that are available for sale.
        //
        // The products are sorted alphabetically.
        $products = Product::where('stock_quantity', '>', 0)
            ->orderBy('product_name')
            ->get();


        // Open the POS/create sale page.
        //
        // Send customers and available products
        // to the Blade view.
        return view('sales.create', [
            'customers' => $customers,
            'products' => $products
        ]);
    }


    // =========================================================
    // 3. SAVE A NEW SALE
    // =========================================================
    public function store(Request $request)
    {
        // Validate the information submitted by the POS form.
        $request->validate([

            // Customer is optional.
            //
            // If a customer ID is provided,
            // that ID must exist in the customers table.
            'customer_id' =>
                'nullable|exists:customers,id',


            // Payment method is required.
            // Example:
            // Cash, GCash, Card
            'payment_method' =>
                'required|string|max:50',


            // Products must be provided.
            // It must be an array.
            // At least one product must be included.
            'products' =>
                'required|array|min:1',


            // Every product in the array must have
            // a valid product ID.
            'products.*.id' =>
                'required|exists:products,id',


            // Every product must have a quantity.
            // Quantity must be at least 1.
            'products.*.quantity' =>
                'required|integer|min:1',
        ]);


        // =====================================================
        // DATABASE TRANSACTION
        // =====================================================
        //
        // The sale, sale items, and stock changes
        // should happen together.
        //
        // If something fails inside this transaction,
        // Laravel can roll back the database changes.
        DB::transaction(function () use ($request) {


            // =================================================
            // CREATE THE SALE
            // =================================================

            // Create a new Sale object.
            $sale = new Sale();


            // Get the selected customer ID.
            $sale->customer_id =
                $request->input('customer_id');


            // Get the ID of the currently logged-in user.
            //
            // This records which staff/user processed the sale.
            $sale->user_id =
                auth()->id();


            // Store the current date and time.
            $sale->sale_date =
                now();


            // Start the total at 0.
            //
            // We will calculate the real total
            // after processing the products.
            $sale->total_amount = 0;


            // Store the selected payment method.
            $sale->payment_method =
                $request->input('payment_method');


            // New sales are automatically marked
            // as completed.
            $sale->status = 'completed';


            // Save the sale into the database.
            //
            // After save(), the sale gets its ID.
            $sale->save();


            // =================================================
            // PREPARE TO CALCULATE TOTAL
            // =================================================

            // Start the total amount at zero.
            $totalAmount = 0;


            // Get the products submitted by the POS form.
            //
            // Example:
            //
            // [
            //     ['id' => 1, 'quantity' => 2],
            //     ['id' => 5, 'quantity' => 1]
            // ]
            $productsInput =
                $request->input('products');


            // Loop through every product selected
            // in the sale.
            //
            // $index = position of the product in the array.
            // $item = product ID and quantity.
            foreach ($productsInput as $index => $item) {


                // =================================================
                // GET PRODUCT AND LOCK ITS DATABASE ROW
                // =================================================

                // Find the product by ID.
                //
                // lockForUpdate() temporarily locks the product
                // database row while this transaction is running.
                //
                // This helps prevent two transactions from
                // changing the same stock at the same time.
                $product = Product::lockForUpdate()
                    ->findOrFail($item['id']);


                // =================================================
                // CHECK AVAILABLE STOCK
                // =================================================

                // Check if the available stock is less than
                // the quantity the customer wants to buy.
                if ($product->stock_quantity < $item['quantity']) {


                    // Stop the transaction and create
                    // a validation error.
                    //
                    // Example:
                    // Available: 2
                    // Requested: 5
                    //
                    // The sale cannot continue.
                    throw ValidationException::withMessages([

                        // Point the error to the quantity
                        // field of this product.
                        "products.{$index}.quantity" => [

                            // Show the user how much stock
                            // is available.
                            "Not enough stock available for {$product->product_name}. Available: {$product->stock_quantity}, Requested: {$item['quantity']}."
                        ]
                    ]);
                }


                // =================================================
                // CALCULATE SUBTOTAL
                // =================================================

                // Calculate:
                //
                // product price × quantity
                //
                // Example:
                // ₱100 × 3 = ₱300
                $subtotal =
                    $product->sell_price * $item['quantity'];


                // =================================================
                // CREATE SALE ITEM
                // =================================================

                // Create a new SaleItem object.
                //
                // SaleItem represents one product
                // inside a sale.
                $saleItem = new SaleItem();


                // Connect the sale item to the sale.
                $saleItem->sale_id =
                    $sale->id;


                // Connect the sale item to the product.
                $saleItem->product_id =
                    $product->id;


                // Save how many units were purchased.
                $saleItem->quantity =
                    $item['quantity'];


                // Save the product price at the time of sale.
                //
                // This is important because the product's
                // current price could change later.
                $saleItem->unit_price =
                    $product->sell_price;


                // Save the subtotal.
                $saleItem->subtotal =
                    $subtotal;


                // Save the sale item to the database.
                $saleItem->save();


                // =================================================
                // REDUCE PRODUCT STOCK
                // =================================================

                // Subtract the purchased quantity
                // from the current stock.
                //
                // Example:
                // Stock = 10
                // Customer buys = 3
                // New stock = 7
                $product->stock_quantity =
                    $product->stock_quantity - $item['quantity'];


                // Save the new stock quantity.
                $product->save();


                // =================================================
                // ADD SUBTOTAL TO TOTAL
                // =================================================

                // Add this product's subtotal
                // to the total sale amount.
                //
                // Example:
                // Product 1 = ₱300
                // Product 2 = ₱200
                // Total = ₱500
                $totalAmount =
                    $totalAmount + $subtotal;
            }


            // =================================================
            // SAVE FINAL TOTAL
            // =================================================

            // After all products have been processed,
            // save the calculated total to the sale.
            $sale->total_amount =
                $totalAmount;


            // Save the updated total.
            $sale->save();
        });


        // After the transaction succeeds,
        // return to the sales list.
        return redirect()
            ->route('sales.index')

            // Display a success message.
            ->with(
                'success',
                'Sale added successfully.'
            );
    }


    // =========================================================
    // 4. DISPLAY ONE SALE
    // =========================================================
    public function show($id)
    {
        // Find one sale using its ID.
        //
        // Also load:
        // customer
        // user
        // sale items
        // products inside those sale items
        $sale = Sale::with([
            'customer',
            'user',
            'saleItems.product'
        ])
        ->findOrFail($id);


        // Open the sale details page.
        return view('sales.show', [
            'sale' => $sale
        ]);
    }


    // =========================================================
    // 5. DISPLAY EDIT SALE FORM
    // =========================================================
    public function edit($id)
    {
        // Find the sale we want to edit.
        //
        // Also load the products belonging to
        // each sale item.
        $sale = Sale::with('saleItems.product')
            ->findOrFail($id);


        // Get all customers for the customer dropdown.
        $customers = Customer::orderBy('name')->get();


        // Open the edit form.
        //
        // Send the sale and customers to the view.
        return view('sales.edit', [
            'sale' => $sale,
            'customers' => $customers
        ]);
    }


    // =========================================================
    // 6. UPDATE AN EXISTING SALE
    // =========================================================
    public function update(Request $request, $id)
    {
        // Validate the updated information.
        $request->validate([

            // Customer is optional.
            'customer_id' =>
                'nullable|exists:customers,id',


            // Payment method is required.
            'payment_method' =>
                'required|string|max:50',


            // Only these statuses are allowed.
            'status' =>
                'required|string|in:completed,cancelled,refunded',
        ]);


        // Use a database transaction because
        // updating the sale can also change product stock.
        DB::transaction(function () use ($request, $id) {


            // Find the sale and lock it during the transaction.
            //
            // This prevents another transaction from
            // modifying the same sale at the same time.
            $sale = Sale::lockForUpdate()
                ->findOrFail($id);


            // Get the new status from the form.
            $newStatus =
                $request->input('status');


            // =================================================
            // RETURN STOCK IF SALE IS CANCELLED/REFUNDED
            // =================================================

            // Check whether:
            //
            // 1. The old sale status is completed
            // AND
            // 2. The new status is cancelled or refunded
            //
            // If both are true, return the sold products
            // back into inventory.
            if (
                $sale->status === 'completed'
                &&
                (
                    $newStatus === 'cancelled'
                    ||
                    $newStatus === 'refunded'
                )
            ) {


                // Go through every product in the sale.
                foreach ($sale->saleItems as $item) {


                    // Find the product and lock its row.
                    $product = Product::lockForUpdate()
                        ->findOrFail($item->product_id);


                    // Add the sold quantity back to stock.
                    //
                    // Example:
                    // Current stock = 5
                    // Returned quantity = 2
                    // New stock = 7
                    $product->stock_quantity =
                        $product->stock_quantity + $item->quantity;


                    // Save the restored stock.
                    $product->save();
                }
            }


            // =================================================
            // UPDATE SALE INFORMATION
            // =================================================

            // Update the customer.
            $sale->customer_id =
                $request->input('customer_id');


            // Update the payment method.
            $sale->payment_method =
                $request->input('payment_method');


            // Update the status.
            $sale->status =
                $newStatus;


            // Save the changes.
            $sale->save();
        });


        // Return to the sales list.
        return redirect()
            ->route('sales.index')

            // Display a success message.
            ->with(
                'success',
                'Sale updated successfully.'
            );
    }


    // =========================================================
    // 7. DELETE A SALE
    // =========================================================
    public function destroy($id)
    {
        // Use a transaction because deleting a sale
        // can also affect product stock.
        DB::transaction(function () use ($id) {


            // Find the sale and lock it.
            $sale = Sale::lockForUpdate()
                ->findOrFail($id);


            // =================================================
            // RETURN STOCK
            // =================================================

            // If the sale was completed,
            // return the sold products to inventory
            // before deleting the sale.
            if ($sale->status === 'completed') {


                // Go through each product in the sale.
                foreach ($sale->saleItems as $item) {


                    // Find and lock the product.
                    $product = Product::lockForUpdate()
                        ->findOrFail($item->product_id);


                    // Add the sold quantity back to stock.
                    $product->stock_quantity =
                        $product->stock_quantity + $item->quantity;


                    // Save the restored stock.
                    $product->save();
                }
            }


            // =================================================
            // DELETE SALE ITEMS
            // =================================================

            // Delete all sale items belonging to this sale.
            //
            // SaleItem records must be removed because
            // they belong to the sale being deleted.
            $sale->saleItems()->delete();


            // =================================================
            // DELETE SALE
            // =================================================

            // Finally delete the sale itself.
            $sale->delete();
        });


        // After deleting the sale,
        // return to the sales list.
        return redirect()
            ->route('sales.index')

            // Display a success message.
            ->with(
                'success',
                'Sale deleted successfully.'
            );
    }
}
