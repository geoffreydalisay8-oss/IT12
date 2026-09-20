<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Device;
use App\Models\Product;
use App\Models\RepairTicket;
use App\Models\Sale;

class DashboardController extends Controller
{
    // Display the main dashboard.
    public function index()
    {
        // Count how many customers exist.
        $customerCount = Customer::count();

        // Count how many registered devices exist.
        $deviceCount = Device::count();

        // Count how many products exist.
        $productCount = Product::count();

        // Count repair tickets that are still pending.
        $pendingRepairs = RepairTicket::where(
            'status',
            'pending'
        )->count();

        // Count completed sales.
        $completedSales = Sale::where(
            'status',
            'completed'
        )->count();

        // Send the summary information to the dashboard view.
        // Explicit array mapping instead of compact()
        return view('dashboard', [
            'customerCount'  => $customerCount,
            'deviceCount'    => $deviceCount,
            'productCount'   => $productCount,
            'pendingRepairs' => $pendingRepairs,
            'completedSales' => $completedSales,
        ]);
    }
}