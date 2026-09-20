<?php

namespace App\Http\Controllers;
use App\Models\DevicePhoto;
use App\Models\RepairTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class DevicePhotoController extends Controller
{
    // =========================================================
    // 1. DISPLAY ALL PHOTOS FOR A REPAIR TICKET
    // =========================================================
    public function index($repairTicketId)
    {
        // Find the repair ticket using the ID.
        // If the ticket does not exist, Laravel automatically returns 404.
        $repairTicket = RepairTicket::findOrFail($repairTicketId);

        // Get all photos that belong to this repair ticket.
        // where() filters the photos using repair_ticket_id.
        // latest() sorts the newest photos first.
        // get() retrieves the results from the database.
        $photos = DevicePhoto::where('repair_ticket_id', $repairTicketId)
            ->latest()
            ->get();

        // Send the repair ticket and photos to the index Blade view.
        return view('device_photos.index', [
            'repairTicket' => $repairTicket,
            'photos'       => $photos
        ]);
    }


    // =========================================================
    // 2. DISPLAY UPLOAD PHOTO FORM
    // =========================================================
    public function create($repairTicketId)
    {
        // Find the repair ticket first.
        // This makes sure the repair ticket exists before
        // showing the upload form.
        $repairTicket = RepairTicket::findOrFail($repairTicketId);

        // Open the upload form.
        // We also send the repair ticket to the Blade view
        // so the form knows which ticket the photo belongs to.
        return view('device_photos.create', [
            'repairTicket' => $repairTicket
        ]);
    }


    // =========================================================
    // 3. SAVE A NEW DEVICE PHOTO
    // =========================================================
    public function store(Request $request, $repairTicketId)
    {
        // STEP 1:
        // Find the repair ticket.
        // If the ID does not exist, Laravel returns a 404 error.
        $repairTicket = RepairTicket::findOrFail($repairTicketId);


        // STEP 2:
        // Validate the information submitted by the user.
        $request->validate([
            
            // The photo is required.
            // image = must be an image.
            // mimes = only these file types are allowed.
            // max:5120 = maximum file size is 5120 KB (5 MB).
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',

            // photo_type is required and can only have
            // one of these three values.
            'photo_type' => 'required|in:before_repair,during_repair,after_repair',
        ]);


        // Store the uploaded image inside:
        // storage/app/public/repair-photos
        //
        // Laravel returns the path of the uploaded file.
        // Example:
        // repair-photos/abc123.jpg
        $path = $request->file('photo')->store('repair-photos', 'public');


        // Create a new DevicePhoto object.
        // At this point, we are preparing a new database record.
        $devicePhoto = new DevicePhoto();


        // Connect the photo to the repair ticket.
        // Example:
        // repair_ticket_id = 5
        $devicePhoto->repair_ticket_id = $repairTicket->id;


        // Save the location/path of the uploaded image.
        $devicePhoto->photo_path = $path;


        // Save whether the photo is:
        // before_repair, during_repair, or after_repair.
        $devicePhoto->photo_type = $request->input('photo_type');


        // Save the ID of the currently logged-in user.
        // auth()->id() gets the user's ID.
        $devicePhoto->uploaded_by = auth()->id();


        // Save the current date and time.
        $devicePhoto->uploaded_at = now();


        // Save all the information into the database.
        $devicePhoto->save();


        // After successfully saving the photo,
        // redirect the user back to the photo list.
        return redirect()
            ->route('repair-tickets.photos.index', $repairTicket->id)

            // Display a success message.
            ->with('success', 'Repair photo uploaded successfully.');
    }


    // =========================================================
    // 4. DISPLAY ONE PHOTO
    // =========================================================
    public function show($repairTicketId, $photoId)
    {
        // Find the repair ticket.
        $repairTicket = RepairTicket::findOrFail($repairTicketId);

        // Find the specific photo using its ID.
        $devicePhoto = DevicePhoto::findOrFail($photoId);

        // Send both objects to the show Blade view.
        return view('device_photos.show', [
            'repairTicket' => $repairTicket,
            'devicePhoto'  => $devicePhoto
        ]);
    }


    // =========================================================
    // 5. DISPLAY EDIT PHOTO FORM
    // =========================================================
    public function edit($repairTicketId, $photoId)
    {
        // Find the repair ticket.
        $repairTicket = RepairTicket::findOrFail($repairTicketId);

        // Find the photo that we want to edit.
        $devicePhoto = DevicePhoto::findOrFail($photoId);

        // Open the edit form.
        // Send the repair ticket and photo to the Blade view.
        return view('device_photos.edit', [
            'repairTicket' => $repairTicket,
            'devicePhoto'  => $devicePhoto
        ]);
    }


    // =========================================================
    // 6. UPDATE AN EXISTING PHOTO TYPE
    // =========================================================
    public function update(Request $request, $repairTicketId, $photoId)
    {
        // Validate the new photo type.
        // It must be one of the allowed values.
        $request->validate([
            'photo_type' => 'required|in:before_repair,during_repair,after_repair',
        ]);


        // Find the repair ticket.
        $repairTicket = RepairTicket::findOrFail($repairTicketId);

        // Find the photo that we want to update.
        $devicePhoto = DevicePhoto::findOrFail($photoId);


        // Change the photo type using the value
        // submitted by the user.
        $devicePhoto->photo_type = $request->input('photo_type');


        // Save the updated information to the database.
        $devicePhoto->save();


        // Return to the photo list after updating.
        return redirect()
            ->route('repair-tickets.photos.index', $repairTicket->id)

            // Display a success message.
            ->with('success', 'Repair photo updated successfully.');
    }


    // =========================================================
    // 7. DELETE A DEVICE PHOTO
    // =========================================================
    public function destroy($repairTicketId, $photoId)
    {
        // Find the repair ticket.
        $repairTicket = RepairTicket::findOrFail($repairTicketId);

        // Find the photo that needs to be deleted.
        $devicePhoto = DevicePhoto::findOrFail($photoId);


        // Check if the photo has a file path.
        if ($devicePhoto->photo_path) {

            // Delete the actual image file from storage.
            //
            // IMPORTANT:
            // This deletes the physical image file.
            Storage::disk('public')->delete($devicePhoto->photo_path);
        }


        // Delete the photo's database record.
        $devicePhoto->delete();


        // Return to the photo list.
        return redirect()
            ->route('repair-tickets.photos.index', $repairTicket->id)

            // Display a success message.
            ->with('success', 'Repair photo deleted successfully.');
    }
}
