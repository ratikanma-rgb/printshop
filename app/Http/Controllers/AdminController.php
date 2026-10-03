<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();

        $totalCustomers = User::where('role', 'customer')->count();

        $totalStaff = User::where('role', 'staff')->count();

        $totalServices = Service::count();

        $totalOrders = Order::count();

        $pendingOrders = Order::where('status', 'pending')->count();

        $waitingPaymentOrders = Order::where(
            'status',
            'waiting_payment'
        )->count();

        $processingOrders = Order::where(
            'status',
            'processing'
        )->count();

        $readyOrders = Order::where(
            'status',
            'ready'
        )->count();

        $completedOrders = Order::where(
            'status',
            'completed'
        )->count();

        $totalRevenue = Order::where(
            'payment_status',
            'paid'
        )->sum('total_price');

        $latestOrders = Order::with([
                'user',
                'items.service'
            ])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalCustomers',
            'totalStaff',
            'totalServices',
            'totalOrders',
            'pendingOrders',
            'waitingPaymentOrders',
            'processingOrders',
            'readyOrders',
            'completedOrders',
            'totalRevenue',
            'latestOrders'
        ));
    }
}