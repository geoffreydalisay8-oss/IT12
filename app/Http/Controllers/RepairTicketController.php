<?php

namespace App\Http\Controllers;
use App\Models\Device;
use App\Models\RepairStatusHistory;
use App\Models\RepairTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class RepairTicketController extends Controller
{
    // =========================================================
    // 1. DISPLAY ALL REPAIR TICKETS
    // =========================================================
    public function index()
    {
        // Get all repair tickets from the database.
        //
        // with() loads related information at the same time.
        //
        // device.customer
        // = Get the device of the repair ticket
        //   and the customer who owns that device.
        //
        // assignedUser
        // = Get the staff/user assigned to the repair ticket.
        $repairTickets = RepairTicket::with([
            'device.customer',
            'assignedUser',
        ])

        // Display the newest repair tickets first.
        ->latest()

        // Execute the query and get all results.
        ->get();


        // Open the repair ticket list page.
        //
        // Send the repair tickets to the Blade view.
        return view('repair_tickets.index', [
            'repairTickets' => $repairTickets
        ]);
    }


    // =========================================================
    // 2. DISPLAY CREATE FORM
    // =========================================================
    public function create()
    {
        // Get all devices.
        //
        // with('customer') also gets the customer
        // who owns each device.
        //
        // This is useful when showing devices in the
        // create repair ticket form.
        $devices = Device::with('customer')->get();


        // Get users who:
        // 1. Have the role "staff"
        // 2. Have an "active" status
        //
        // These users can be selected as the
        // staff member assigned to the repair.
        $users = User::where('role', 'staff')
            ->where('status', 'active')
            ->get();


        // Open the create repair ticket form.
        //
        // Send the devices and staff users to the view.
        return view('repair_tickets.create', [
            'devices' => $devices,
            'users'   => $users
        ]);
    }


    // =========================================================
    // 3. SAVE A NEW REPAIR TICKET
    // =========================================================
    public function store(Request $request)
    {
        // Validate the information submitted from the form.
        $request->validate([

            // A device must be selected.
            // The device ID must exist in the devices table.
            'device_id' =>
                'required|exists:devices,id',


            // A staff member can be assigned,
            // but assigning one is optional.
            //
            // If an ID is provided, it must exist
            // in the users table.
            'assigned_to' =>
                'nullable|exists:users,id',


            // The repair problem must be provided.
            // It must be text.
            'problem_description' =>
                'required|string',


            // Quotation price is optional.
            // If provided, it must be a number
            // and cannot be negative.
            'quotation_price' =>
                'nullable|numeric|min:0',


            // Final price is also optional.
            // If provided, it must be a number
            // and cannot be negative.
            'final_price' =>
                'nullable|numeric|min:0',


            // Status is required.
            //
            // Only these four values are allowed.
            'status' =>
                'required|in:pending,in_progress,completed,cancelled',


            // Date received is required
            // and must be a valid date.
            'date_received' =>
                'required|date',


            // Date completed is optional.
            //
            // If provided, it must be a valid date
            // and cannot be earlier than date_received.
            'date_completed' =>
                'nullable|date|after_or_equal:date_received',
        ]);


        // =====================================================
        // DATABASE TRANSACTION
        // =====================================================
        //
        // A transaction groups multiple database operations
        // together.
        //
        // If something fails inside this block,
        // Laravel can roll back the database changes.
        DB::transaction(function () use ($request) {


            // =================================================
            // CREATE THE REPAIR TICKET
            // =================================================

            // Create a new RepairTicket object.
            $repairTicket = new RepairTicket();


            // Get the device ID from the form.
            $repairTicket->device_id =
                $request->input('device_id');


            // Get the assigned staff/user ID.
            $repairTicket->assigned_to =
                $request->input('assigned_to');


            // Get the customer's reported problem.
            $repairTicket->problem_description =
                $request->input('problem_description');


            // Get the quotation price.
            $repairTicket->quotation_price =
                $request->input('quotation_price');


            // Get the final repair price.
            $repairTicket->final_price =
                $request->input('final_price');


            // Get the current repair status.
            $repairTicket->status =
                $request->input('status');


            // Get the date when the device was received.
            $repairTicket->date_received =
                $request->input('date_received');


            // Get the completion date.
            $repairTicket->date_completed =
                $request->input('date_completed');


            // Save the repair ticket into the database.
            //
            // After save(), the new repair ticket gets
            // its database ID.
            $repairTicket->save();


            // =================================================
            // CREATE INITIAL STATUS HISTORY
            // =================================================

            // Create a new status history record.
            //
            // This records the initial status of the
            // newly created repair ticket.
            $history = new RepairStatusHistory();


            // Connect the history record to the repair ticket.
            //
            // Example:
            // repair_ticket_id = 10
            $history->repair_ticket_id =
                $repairTicket->id;


            // Store the current status.
            //
            // Example:
            // pending
            $history->status =
                $repairTicket->status;


            // Store the ID of the logged-in user
            // who created/changed the status.
            $history->changed_by =
                auth()->id();


            // Store the current date and time.
            $history->changed_at =
                now();


            // Save the status history record.
            $history->save();
        });


        // After the transaction is successfully completed,
        // return to the repair ticket list.
        return redirect()
            ->route('repair-tickets.index')

            // Display a success message.
            ->with(
                'success',
                'Repair ticket created successfully.'
            );
    }


    // =========================================================
    // 4. DISPLAY ONE REPAIR TICKET
    // =========================================================
    public function show($id)
    {
        // Find one repair ticket using its ID.
        //
        // If the ID does not exist,
        // Laravel returns a 404 error.
        $repairTicket = RepairTicket::findOrFail($id);


        // Load related information.
        //
        // device.customer
        // = Get the device and its customer.
        //
        // assignedUser
        // = Get the staff member assigned to the repair.
        //
        // statusHistory.changedBy
        // = Get the status history and the user
        //   who changed each status.
        //
        // photos.uploadedBy
        // = Get the repair photos and the user
        //   who uploaded each photo.
        $repairTicket->load([
            'device.customer',
            'assignedUser',
            'statusHistory.changedBy',
            'photos.uploadedBy',
        ]);


        // Send the repair ticket and its related information
        // to the show Blade view.
        return view('repair_tickets.show', [
            'repairTicket' => $repairTicket
        ]);
    }


    // =========================================================
    // 5. DISPLAY EDIT FORM
    // =========================================================
    public function edit($id)
    {
        // Find the repair ticket that we want to edit.
        $repairTicket = RepairTicket::findOrFail($id);


        // Get all devices and their customers.
        //
        // These will be available in the edit form
        // for selecting a device.
        $devices = Device::with('customer')->get();


        // Get only active staff users.
        //
        // These users can be selected as
        // the assigned staff member.
        $users = User::where('role', 'staff')
            ->where('status', 'active')
            ->get();


        // Open the edit repair ticket form.
        //
        // Send the repair ticket, devices,
        // and staff users to the view.
        return view('repair_tickets.edit', [
            'repairTicket' => $repairTicket,
            'devices'      => $devices,
            'users'        => $users
        ]);
    }


    // =========================================================
    // 6. UPDATE A REPAIR TICKET
    // =========================================================
    public function update(Request $request, $id)
    {
        // Validate the updated information.
        $request->validate([

            // Device is required and must exist.
            'device_id' =>
                'required|exists:devices,id',


            // Assigned staff is optional.
            'assigned_to' =>
                'nullable|exists:users,id',


            // Problem description is required.
            'problem_description' =>
                'required|string',


            // Quotation price is optional
            // but cannot be negative.
            'quotation_price' =>
                'nullable|numeric|min:0',


            // Final price is optional
            // but cannot be negative.
            'final_price' =>
                'nullable|numeric|min:0',


            // Only these statuses are allowed.
            'status' =>
                'required|in:pending,in_progress,completed,cancelled',


            // Date received must be a valid date.
            'date_received' =>
                'required|date',


            // Completion date cannot be earlier
            // than the received date.
            'date_completed' =>
                'nullable|date|after_or_equal:date_received',
        ]);


        // Find the repair ticket that we want to update.
        $repairTicket = RepairTicket::findOrFail($id);


        // Save the OLD status before changing anything.
        //
        // Example:
        // oldStatus = "pending"
        //
        // We need this later to check whether
        // the status actually changed.
        $oldStatus = $repairTicket->status;


        // =====================================================
        // DATABASE TRANSACTION
        // =====================================================
        //
        // The ticket update and possible status-history
        // creation are treated as one database operation.
        DB::transaction(function () use (
            $request,
            $repairTicket,
            $oldStatus
        ) {


            // =================================================
            // UPDATE THE REPAIR TICKET
            // =================================================

            // Update the device.
            $repairTicket->device_id =
                $request->input('device_id');


            // Update the assigned staff member.
            $repairTicket->assigned_to =
                $request->input('assigned_to');


            // Update the problem description.
            $repairTicket->problem_description =
                $request->input('problem_description');


            // Update the quotation price.
            $repairTicket->quotation_price =
                $request->input('quotation_price');


            // Update the final price.
            $repairTicket->final_price =
                $request->input('final_price');


            // Update the status.
            $repairTicket->status =
                $request->input('status');


            // Update the date received.
            $repairTicket->date_received =
                $request->input('date_received');


            // Update the completion date.
            $repairTicket->date_completed =
                $request->input('date_completed');


            // Save all changes to the database.
            $repairTicket->save();


            // =================================================
            // CHECK IF STATUS CHANGED
            // =================================================

            // Compare the old status with the new status.
            //
            // Example:
            //
            // Old status = pending
            // New status = in_progress
            //
            // They are different, so a history record
            // should be created.
            if ($oldStatus !== $repairTicket->status) {


                // Create a new status history record.
                $history = new RepairStatusHistory();


                // Connect the history to the repair ticket.
                $history->repair_ticket_id =
                    $repairTicket->id;


                // Store the NEW status.
                $history->status =
                    $repairTicket->status;


                // Store who changed the status.
                $history->changed_by =
                    auth()->id();


                // Store when the status was changed.
                $history->changed_at =
                    now();


                // Save the status history.
                $history->save();
            }
        });


        // After successfully updating,
        // return to the repair ticket list.
        return redirect()
            ->route('repair-tickets.index')

            // Display a success message.
            ->with(
                'success',
                'Repair ticket updated successfully.'
            );
    }


    // =========================================================
    // 7. DELETE A REPAIR TICKET
    // =========================================================
    public function destroy($id)
    {
        // Find the repair ticket we want to delete.
        //
        // If it does not exist,
        // Laravel returns a 404 error.
        $repairTicket = RepairTicket::findOrFail($id);


        // Delete the repair ticket from the database.
        $repairTicket->delete();


        // After deleting,
        // return to the repair ticket list.
        return redirect()
            ->route('repair-tickets.index')

            // Display a success message.
            ->with(
                'success',
                'Repair ticket deleted successfully.'
            );
    }
}
