<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return view('customer.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        $orderId = $item->data['order_id'] ?? null;

        if ($orderId) {
            return redirect()->route('customer.orders.show', $orderId);
        }

        return redirect()->route('customer.notifications.index');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()
            ->route('customer.notifications.index')
            ->with('success', 'ทำเครื่องหมายว่าอ่านการแจ้งเตือนทั้งหมดแล้ว');
    }
}
