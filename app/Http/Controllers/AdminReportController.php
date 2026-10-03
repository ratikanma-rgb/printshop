<?php

namespace App\Http\Controllers;

use App\Models\Order;

class AdminReportController extends Controller
{
    public function index()
    {
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

        $cancelledOrders = Order::where(
            'status',
            'cancelled'
        )->count();

        $paidOrders = Order::where(
            'payment_status',
            'paid'
        )->count();

        $totalRevenue = Order::where(
            'payment_status',
            'paid'
        )->sum('total_price');

        $orders = Order::with([
                'user',
                'items.service',
                'payment'
            ])
            ->latest()
            ->get();

        return view('admin.reports.index', compact(
            'totalOrders',
            'pendingOrders',
            'waitingPaymentOrders',
            'processingOrders',
            'readyOrders',
            'completedOrders',
            'cancelledOrders',
            'paidOrders',
            'totalRevenue',
            'orders'
        ));
    }
}