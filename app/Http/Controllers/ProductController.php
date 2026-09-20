<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class ProductController extends Controller
{
    // =========================================================
    // 1. DISPLAY ALL PRODUCTS
    // =========================================================
    public function index()
    {
        // Get all products from the products table.
        //
        // with() also gets the related category and supplier.
        // This is useful because each product belongs to a category
        // and a supplier.
        $products = Product::with([
            'category',
            'supplier',
        ])

        // Display the newest products first.
        // latest() normally uses the created_at column.
        ->latest()

        // Execute the database query and get the products.
        ->get();


        // Send the products to the Blade view:
        // resources/views/products/index.blade.php
        //
        // 'products' is the name that the Blade file
        // will use to access the product data.
        return view('products.index', [
            'products' => $products
        ]);
    }


    // =========================================================
    // 2. DISPLAY CREATE PRODUCT FORM
    // =========================================================
    public function create()
    {
        // Get all categories from the categories table.
        //
        // orderBy() sorts the categories alphabetically
        // using category_name.
        $categories = Category::orderBy('category_name')->get();


        // Get all suppliers from the suppliers table.
        //
        // The suppliers are also sorted alphabetically
        // using supplier_name.
        $suppliers = Supplier::orderBy('supplier_name')->get();


        // Open the create product form.
        //
        // Send the categories and suppliers to the Blade view.
        // The form can use them for dropdown/select fields.
        return view('products.create', [
            'categories' => $categories,
            'suppliers' => $suppliers,
        ]);
    }


    // =========================================================
    // 3. SAVE A NEW PRODUCT
    // =========================================================
    public function store(Request $request)
    {
        // Validate the information submitted by the user.
        //
        // Laravel checks these rules BEFORE saving the product.
        $request->validate([

            // Product name is required.
            // It must be text and cannot exceed 150 characters.
            'product_name' => 'required|string|max:150',

            // SKU is required.
            // It must be text.
            // It cannot exceed 50 characters.
            // It must be unique in the products table.
            'sku' => 'required|string|max:50|unique:products,sku',

            // category_id is required.
            // The category ID must exist in the categories table.
            'category_id' => 'required|exists:categories,id',

            // supplier_id is required.
            // The supplier ID must exist in the suppliers table.
            'supplier_id' => 'required|exists:suppliers,id',

            // Cost price is required.
            // It must be a number and cannot be negative.
            'cost_price' => 'required|numeric|min:0',

            // Selling price is required.
            // It must be a number and cannot be negative.
            'sell_price' => 'required|numeric|min:0',

            // Stock quantity is required.
            // It must be a whole number and cannot be negative.
            'stock_quantity' => 'required|integer|min:0',
        ]);


        // Create a new Product object.
        //
        // At this point, we are preparing a new
        // product database record.
        $product = new Product();


        // Get the product name from the submitted form
        // and place it into the Product object.
        $product->product_name = $request->input('product_name');


        // Get the SKU from the form.
        $product->sku = $request->input('sku');


        // Get the selected category ID.
        //
        // This connects the product to a category.
        $product->category_id = $request->input('category_id');


        // Get the selected supplier ID.
        //
        // This connects the product to a supplier.
        $product->supplier_id = $request->input('supplier_id');


        // Get the product's cost price.
        $product->cost_price = $request->input('cost_price');


        // Get the product's selling price.
        $product->sell_price = $request->input('sell_price');


        // Get the starting/current stock quantity.
        $product->stock_quantity = $request->input('stock_quantity');


        // Save the Product object into the products table.
        $product->save();


        // After successfully saving:
        // redirect the user to the product list page.
        return redirect()
            ->route('products.index')

            // Display a success message.
            ->with('success', 'Product added successfully.');
    }


    // =========================================================
    // 4. DISPLAY ONE PRODUCT
    // =========================================================
    public function show($id)
    {
        // Find one product using its ID.
        //
        // Example:
        // /products/5
        //
        // Laravel searches for product ID 5.
        //
        // If the product does not exist, Laravel returns 404.
        $product = Product::findOrFail($id);


        // Load the category and supplier related to this product.
        //
        // This allows us to access things such as:
        // $product->category->category_name
        // $product->supplier->supplier_name
        $product->load('category', 'supplier');


        // Send the product to the show Blade view.
        return view('products.show', [
            'product' => $product
        ]);
    }


    // =========================================================
    // 5. DISPLAY EDIT PRODUCT FORM
    // =========================================================
    public function edit($id)
    {
        // Find the product that we want to edit.
        //
        // If the product does not exist, Laravel returns 404.
        $product = Product::findOrFail($id);


        // Get all categories.
        //
        // These will be used in the category dropdown
        // on the edit form.
        $categories = Category::orderBy('category_name')->get();


        // Get all suppliers.
        //
        // These will be used in the supplier dropdown
        // on the edit form.
        $suppliers = Supplier::orderBy('supplier_name')->get();


        // Open the edit form.
        //
        // Send the product, categories, and suppliers
        // to the Blade view.
        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
            'suppliers' => $suppliers,
        ]);
    }


    // =========================================================
    // 6. UPDATE AN EXISTING PRODUCT
    // =========================================================
    public function update(Request $request, $id)
    {
        // Find the existing product using its ID.
        $product = Product::findOrFail($id);


        // Validate the new information.
        $request->validate([

            // Product name is required.
            // It must be text and maximum 150 characters.
            'product_name' => 'required|string|max:150',


            // SKU must be required, text, and maximum 50 characters.
            //
            // Rule::unique() checks that the SKU is not already
            // being used by another product.
            //
            // ignore($product->id) means:
            // "Ignore this product's current SKU."
            //
            // This is important because when editing a product,
            // it is okay for it to keep its existing SKU.
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],


            // The selected category must exist.
            'category_id' => 'required|exists:categories,id',


            // The selected supplier must exist.
            'supplier_id' => 'required|exists:suppliers,id',


            // Cost price must be a number and cannot be negative.
            'cost_price' => 'required|numeric|min:0',


            // Selling price must be a number and cannot be negative.
            'sell_price' => 'required|numeric|min:0',


            // Stock quantity must be a whole number and cannot be negative.
            'stock_quantity' => 'required|integer|min:0',
        ]);


        // Update the product name.
        $product->product_name = $request->input('product_name');


        // Update the SKU.
        $product->sku = $request->input('sku');


        // Update the category.
        $product->category_id = $request->input('category_id');


        // Update the supplier.
        $product->supplier_id = $request->input('supplier_id');


        // Update the cost price.
        $product->cost_price = $request->input('cost_price');


        // Update the selling price.
        $product->sell_price = $request->input('sell_price');


        // Update the stock quantity.
        $product->stock_quantity = $request->input('stock_quantity');


        // Save all the changes to the database.
        $product->save();


        // After updating, return to the product list.
        return redirect()
            ->route('products.index')

            // Display a success message.
            ->with('success', 'Product updated successfully.');
    }


    // =========================================================
    // 7. DELETE A PRODUCT
    // =========================================================
    public function destroy($id)
    {
        // Find the product that we want to delete.
        //
        // If the product does not exist, Laravel returns 404.
        $product = Product::findOrFail($id);


        // Delete the product from the products table.
        $product->delete();


        // After deleting the product,
        // return to the product list.
        return redirect()
            ->route('products.index')

            // Display a success message.
            ->with('success', 'Product deleted successfully.');
    }
}
