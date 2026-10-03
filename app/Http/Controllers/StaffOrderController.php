<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StaffOrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::with(['user', 'items.service', 'files', 'payment'])->latest()->get();

        $totalOrders = $orders->count();
        $pendingOrders = $orders->where('status', 'pending')->count();
        $waitingPaymentOrders = $orders->where('status', 'waiting_payment')->count();
        $processingOrders = $orders->where('status', 'processing')->count();
        $readyOrders = $orders->where('status', 'ready')->count();

        return view('staff.dashboard', compact(
            'orders', 'totalOrders', 'pendingOrders', 'waitingPaymentOrders', 'processingOrders', 'readyOrders'
        ));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.service', 'files', 'payment']);
        $services = Service::where('is_active', true)->orderBy('name')->get();

        return view('staff.orders.show', compact('order', 'services'));
    }

    public function review(Request $request, Order $order): RedirectResponse
    {
        if ($order->status !== 'pending') {
            return back()->withErrors(['review' => 'รายการนี้ตรวจไฟล์แล้ว ไม่สามารถยืนยันราคาอีกครั้งจากหน้านี้ได้']);
        }

        if ($order->payment_status === 'paid') {
            return back()->withErrors(['review' => 'รายการนี้ชำระเงินแล้ว ไม่สามารถแก้ราคาได้']);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'service_id' => ['required_if:action,approve', 'nullable', 'integer', 'exists:services,id'],
            'paper_size' => ['required_if:action,approve', 'nullable', 'in:A4,A3'],
            'print_color' => ['required_if:action,approve', 'nullable', 'in:black_white,color'],
            'print_side' => ['required_if:action,approve', 'nullable', 'in:single,double'],
            'pages' => ['required_if:action,approve', 'nullable', 'integer', 'min:1', 'max:100000'],
            'quantity' => ['required_if:action,approve', 'nullable', 'integer', 'min:1', 'max:100000'],
            'unit_price' => ['required_if:action,approve', 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['action'] === 'reject') {
            if (blank($validated['review_note'] ?? null)) {
                throw ValidationException::withMessages([
                    'review_note' => 'กรุณาระบุเหตุผลที่ไม่อนุมัติ เพื่อให้ลูกค้าแก้ไขหรือส่งใหม่',
                ]);
            }

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
                'review_note' => $validated['review_note'],
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);
            $order->loadMissing('user');
            $order->user?->notify(new OrderStatusChanged($order));

            return back()->with('success', 'บันทึกว่าไม่อนุมัติรายการแล้ว ลูกค้าจะเห็นเหตุผลในรายละเอียดงาน');
        }

        $service = Service::whereKey($validated['service_id'])->where('is_active', true)->first();
        if (! $service) {
            return back()->withErrors(['service_id' => 'บริการนี้ถูกปิดใช้งาน กรุณาเลือกบริการอื่น']);
        }

        $item = $order->items()->first();
        if (! $item) {
            return back()->withErrors(['review' => 'ไม่พบรายละเอียดงาน ไม่สามารถอนุมัติได้']);
        }
        if ($order->files()->count() < 1) {
            return back()->withErrors(['review' => 'ไม่พบไฟล์งาน กรุณาให้ลูกค้าส่งไฟล์ใหม่']);
        }

        $pages = (int) $validated['pages'];
        $quantity = (int) $validated['quantity'];
        $unitPrice = round((float) $validated['unit_price'], 2);
        $isPerPage = $service->unit === 'หน้า' || strtolower((string) $service->unit) === 'page';
        $subtotal = round($isPerPage ? $unitPrice * $pages * $quantity : $unitPrice * $quantity, 2);

        if ($subtotal <= 0) {
            return back()->withErrors(['unit_price' => 'ยอดรวมต้องมากกว่า 0 บาท']);
        }

        try {
            DB::transaction(function () use ($order, $item, $service, $validated, $pages, $quantity, $unitPrice, $subtotal) {
                // ล้างหลักฐานชำระเงินเก่าจาก flow เดิม (ถ้ามี) เพื่อไม่ให้ยอดเก่าถูกนำมาใช้
                $oldPayment = $order->payment;
                if ($oldPayment && $oldPayment->status !== 'paid') {
                    if ($oldPayment->slip_path && Storage::disk('public')->exists($oldPayment->slip_path)) {
                        Storage::disk('public')->delete($oldPayment->slip_path);
                    }
                    $oldPayment->delete();
                }

                $item->update([
                    'service_id' => $service->id,
                    'paper_size' => $validated['paper_size'],
                    'print_color' => $validated['print_color'],
                    'print_side' => $validated['print_side'],
                    'pages' => $pages,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $order->update([
                    'total_price' => $subtotal,
                    'status' => 'waiting_payment',
                    'payment_status' => 'unpaid',
                    'review_note' => $validated['review_note'] ?? null,
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                ]);
            });
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['review' => 'บันทึกข้อมูลไม่ได้ กรุณาลองใหม่อีกครั้ง']);
        }

        $order->refresh()->loadMissing('user');
        $order->user?->notify(new OrderStatusChanged($order));

        return back()->with('success', 'ยืนยันราคาเรียบร้อยแล้ว ลูกค้าสามารถชำระเงินได้');
    }

    public function print(Order $order)
    {
        $order->load(['user', 'items.service', 'files', 'payment']);

        abort_unless($order->payment_status === 'paid', 403, 'ยังไม่ได้ยืนยันการชำระเงิน');
        abort_unless(in_array($order->status, ['processing', 'ready', 'completed'], true), 403, 'งานยังไม่อยู่ในขั้นตอนที่สามารถพิมพ์ได้');

        // ถ้ามีไฟล์เดียวและเป็น PDF ให้เปิดไฟล์ในกรอบพิมพ์และเรียก Print dialog ทันที
        // เพื่อไม่ให้พนักงานต้องผ่านหน้ากลางแล้วกดเปิดไฟล์ต้นฉบับอีกครั้ง
        if ($order->files->count() === 1) {
            $file = $order->files->first();
            $extension = strtolower($file->extension ?: pathinfo($file->original_name, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                $pdfUrl = asset('storage/'.$file->file_path);

                return view('staff.orders.print-pdf-auto', compact('order', 'file', 'pdfUrl'));
            }
        }

        return view('staff.orders.print', compact('order'));
    }

    public function paymentSlip(Order $order): View
    {
        $order->load(['user', 'payment']);
        if (! $order->payment || ! $order->payment->slip_path) {
            abort(404, 'ไม่พบหลักฐานการชำระเงิน');
        }
        return view('staff.orders.payment-slip', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:processing,ready,completed,cancelled'],
        ]);
        $newStatus = $validated['status'];

        // ป้องกันการข้ามขั้นตอนด้วยการยิง URL/Request ตรง
        // ต้องยืนยันการชำระเงินก่อนจึงจะเปลี่ยนสถานะในขั้นตอนดำเนินงานได้
        if ($order->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'status' => 'ยังดำเนินงานไม่ได้ กรุณารอให้ลูกค้าชำระเงินและยืนยันการชำระเงินก่อน',
            ]);
        }

        if ($newStatus === $order->status) {
            return back()->with('success', 'สถานะงานไม่มีการเปลี่ยนแปลง');
        }

        $allowedTransitions = [
            'processing' => ['ready', 'cancelled'],
            'ready' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (! in_array($newStatus, $allowedTransitions[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'ไม่สามารถเปลี่ยนสถานะจากสถานะปัจจุบันไปยังสถานะที่เลือกได้',
            ]);
        }

        if ($newStatus === 'ready' && $order->payment_status !== 'paid') {
            throw ValidationException::withMessages(['status' => 'ยังไม่ได้ยืนยันการชำระเงิน']);
        }

        $order->update(['status' => $newStatus]);
        $order->refresh()->loadMissing('user');
        $order->user?->notify(new OrderStatusChanged($order));

        return back()->with('success', 'อัปเดตสถานะงานเรียบร้อยแล้ว');
    }
}
