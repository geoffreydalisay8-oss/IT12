<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    // =========================================================
    // 1. DISPLAY ALL DEVICES
    // =========================================================
    public function index()
    {
        $devices = Device::with('customer')->latest()->get();

        return view('devices.index', [
            'devices' => $devices
        ]);
    }

    // =========================================================
    // 2. DISPLAY CREATE DEVICE FORM
    // =========================================================
    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('devices.create', [
            'customers' => $customers
        ]);
    }

    // =========================================================
    // 3. SAVE A NEW DEVICE
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'brand'          => 'required|string|max:50',
            'model'          => 'required|string|max:100',
            'serial_or_imei' => 'required|string|max:100|',
        ]);

        $device = new Device();

        // Step 3: Explicitly set each column from form input
        $device->customer_id    = $request->input('customer_id');
        $device->brand          = $request->input('brand');
        $device->model          = $request->input('model');
        $device->serial_or_imei = $request->input('serial_or_imei');

        $device->save();

        return redirect()
            ->route('devices.index')
            ->with('success', 'Device added successfully.');
    }

    // =========================================================
    // 4. DISPLAY ONE DEVICE
    // =========================================================
    public function show($id)
    {
        $device = Device::with(['customer', 'repairTickets'])->findOrFail($id);

        return view('devices.show', [
            'device' => $device
        ]);
    }

    // =========================================================
    // 5. DISPLAY EDIT DEVICE FORM
    // =========================================================
    public function edit($id)
    {
        $device = Device::findOrFail($id);
        $customers = Customer::orderBy('name')->get();

        return view('devices.edit', [
            'device'    => $device,
            'customers' => $customers
        ]);
    }

    // =========================================================
    // 6. UPDATE AN EXISTING DEVICE
    // =========================================================
    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'brand'          => 'required|string|max:50',
            'model'          => 'required|string|max:100',
            'serial_or_imei' => 'required|string|max:100|',
        ]);

        $device = Device::findOrFail($id);

        $device->customer_id    = $request->input('customer_id');
        $device->brand          = $request->input('brand');
        $device->model          = $request->input('model');
        $device->serial_or_imei = $request->input('serial_or_imei');

        $device->save();

        return redirect()
            ->route('devices.index')
            ->with('success', 'Device updated successfully.');
    }

    // =========================================================
    // 7. DELETE A DEVICE
    // =========================================================
    public function destroy($id)
    {
        $device = Device::findOrFail($id);
        $device->delete();

        return redirect()
            ->route('devices.index')
            ->with('success', 'Device deleted successfully.');
    }
}