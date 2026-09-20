<?php
namespace App\Http\Controllers;
use App\Models\Receipt;
use Illuminate\Http\Request;


class ReceiptController extends Controller
{
    // =========================================================
    // 1. DISPLAY ALL RECEIPTS
    // =========================================================
    public function index()
    {
        // Get all receipts from the database.
        //
        // with() also loads the related sale and repair ticket.
        // This allows us to access their information
        // when displaying the receipts.
        $receipts = Receipt::with([
            'sale',
            'repairTicket',
        ])

        // Display the newest receipts first.
        // latest() normally uses the created_at column.
        ->latest()

        // Execute the query and get the results.
        ->get();


        // Open the receipts index Blade view.
        //
        // Send the $receipts variable to the view.
        //
        // The Blade file can then use:
        // $receipts
        return view('receipts.index', [
            'receipts' => $receipts
        ]);
    }


    // =========================================================
    // 2. DISPLAY CREATE RECEIPT FORM
    // =========================================================
    public function create()
    {
        // Display the form for creating a new receipt.
        //
        // No database query is needed here because
        // we are only opening the form.
        return view('receipts.create');
    }


    // =========================================================
    // 3. SAVE A NEW RECEIPT
    // =========================================================
    public function store(Request $request)
    {
        // Validate the information submitted from the form.
        $request->validate([

            // Receipt number is required.
            // It must be text.
            // Maximum of 100 characters.
            // It must be unique in the receipts table.
            'receipt_number' =>
                'required|string|max:100|unique:receipts,receipt_number',


            // sale_id is optional.
            // If provided, the ID must exist in the sales table.
            'sale_id' =>
                'nullable|exists:sales,id',


            // repair_ticket_id is optional.
            // If provided, the ID must exist in the repair_tickets table.
            'repair_ticket_id' =>
                'nullable|exists:repair_tickets,id',


            // Total amount is required.
            // It must be a number.
            // It cannot be negative.
            'total_amount' =>
                'required|numeric|min:0',
        ]);


        // Create a new Receipt object.
        //
        // We are preparing a new receipt record
        // before saving it to the database.
        $receipt = new Receipt();


        // Get the receipt number from the form
        // and put it into the Receipt object.
        $receipt->receipt_number =
            $request->input('receipt_number');


        // Get the sale ID from the form.
        //
        // This connects the receipt to a sale.
        $receipt->sale_id =
            $request->input('sale_id');


        // Get the repair ticket ID from the form.
        //
        // This connects the receipt to a repair ticket.
        $receipt->repair_ticket_id =
            $request->input('repair_ticket_id');


        // Get the total amount from the form.
        $receipt->total_amount =
            $request->input('total_amount');


        // Save the receipt into the receipts table.
        $receipt->save();


        // After successfully saving the receipt,
        // send the user back to the receipt list.
        return redirect()
            ->route('receipts.index')

            // Show a success message.
            ->with('success', 'Receipt created successfully.');
    }


    // =========================================================
    // 4. DISPLAY ONE RECEIPT
    // =========================================================
    public function show($id)
    {
        // Find one receipt using its ID.
        //
        // Example:
        // /receipts/5
        //
        // If receipt ID 5 does not exist,
        // Laravel returns a 404 error.
        $receipt = Receipt::findOrFail($id);


        // Load related information for this receipt.
        //
        // sale.customer
        // = Get the customer connected to the sale.
        //
        // sale.user
        // = Get the user/staff who handled the sale.
        //
        // sale.saleItems.product
        // = Get the items in the sale and the products
        // associated with those items.
        //
        // repairTicket.device.customer
        // = Get the repair ticket's device and
        // the customer who owns that device.
        $receipt->load([
            'sale.customer',
            'sale.user',
            'sale.saleItems.product',
            'repairTicket.device.customer',
        ]);


        // Send the receipt and its related information
        // to the show Blade view.
        return view('receipts.show', [
            'receipt' => $receipt
        ]);
    }


    // =========================================================
    // 5. DISPLAY EDIT RECEIPT FORM
    // =========================================================
    public function edit($id)
    {
        // Find the receipt that we want to edit.
        //
        // If the receipt does not exist,
        // Laravel returns a 404 error.
        $receipt = Receipt::findOrFail($id);


        // Open the edit form.
        //
        // Send the existing receipt information
        // to the Blade view.
        return view('receipts.edit', [
            'receipt' => $receipt
        ]);
    }


    // =========================================================
    // 6. UPDATE AN EXISTING RECEIPT
    // =========================================================
    public function update(Request $request, $id)
    {
        // Validate the new information.
        $request->validate([

            // Receipt number is required.
            // It must be text.
            // Maximum 100 characters.
            //
            // unique:receipts,receipt_number,' . $id
            //
            // means the receipt number must be unique,
            // EXCEPT for the current receipt being edited.
            //
            // Example:
            // Receipt #5 currently has R-001.
            // When editing receipt #5, R-001 is still allowed.
            'receipt_number' =>
                'required|string|max:100|unique:receipts,receipt_number,' . $id,


            // sale_id is optional.
            // If provided, it must exist in the sales table.
            'sale_id' =>
                'nullable|exists:sales,id',


            // repair_ticket_id is optional.
            // If provided, it must exist in the repair_tickets table.
            'repair_ticket_id' =>
                'nullable|exists:repair_tickets,id',


            // Total amount is required.
            // It must be a number and cannot be negative.
            'total_amount' =>
                'required|numeric|min:0',
        ]);


        // Find the receipt that we want to update.
        $receipt = Receipt::findOrFail($id);


        // Update the receipt number.
        $receipt->receipt_number =
            $request->input('receipt_number');


        // Update the sale ID.
        $receipt->sale_id =
            $request->input('sale_id');


        // Update the repair ticket ID.
        $receipt->repair_ticket_id =
            $request->input('repair_ticket_id');


        // Update the total amount.
        $receipt->total_amount =
            $request->input('total_amount');


        // Save the changes to the database.
        $receipt->save();


        // After updating, go back to the receipt list.
        return redirect()
            ->route('receipts.index')

            // Display a success message.
            ->with('success', 'Receipt updated successfully.');
    }


    // =========================================================
    // 7. DELETE A RECEIPT
    // =========================================================
    public function destroy($id)
    {
        // Find the receipt that we want to delete.
        //
        // If the receipt does not exist,
        // Laravel returns a 404 error.
        $receipt = Receipt::findOrFail($id);


        // Delete the receipt from the receipts table.
        $receipt->delete();


        // After deleting, go back to the receipt list.
        return redirect()
            ->route('receipts.index')

            // Display a success message.
            ->with('success', 'Receipt deleted successfully.');
    }
}
