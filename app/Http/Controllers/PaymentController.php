<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        if ($order->status !== 'waiting_payment') {
            return back()->withErrors([
                'payment' => 'ยังชำระเงินไม่ได้ กรุณารอร้านตรวจไฟล์และยืนยันราคาก่อน',
            ]);
        }
        if ($order->payment_status === 'paid') {
            return back()->withErrors(['payment' => 'รายการนี้ชำระเงินแล้ว']);
        }
        if ((float) $order->total_price <= 0) {
            return back()->withErrors(['payment' => 'ยอดชำระไม่ถูกต้อง กรุณาติดต่อร้าน']);
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'in:bank_transfer,pay_at_store'],
            'slip' => ['nullable', 'required_if:payment_method,bank_transfer', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ], [
            'payment_method.required' => 'กรุณาเลือกวิธีชำระเงิน',
            'slip.required_if' => 'กรุณาแนบหลักฐานการชำระเงิน',
            'slip.image' => 'หลักฐานการชำระเงินต้องเป็นรูปภาพ',
            'slip.mimes' => 'รองรับไฟล์ JPG, JPEG และ PNG เท่านั้น',
        ]);

        $oldPayment = $order->payment;
        if ($oldPayment?->status === 'paid') {
            return back()->withErrors(['payment' => 'รายการนี้ชำระเงินแล้ว']);
        }

        $oldSlipPath = $oldPayment?->slip_path;
        $newSlipPath = null;

        try {
            if ($validated['payment_method'] === 'bank_transfer') {
                $slip = $request->file('slip');
                if (! $slip || ! $slip->isValid()) {
                    return back()->withErrors(['slip' => 'ไฟล์สลิปไม่สมบูรณ์ กรุณาเลือกไฟล์ใหม่']);
                }
                $extension = strtolower($slip->getClientOriginalExtension());
                $newSlipPath = $slip->storeAs('payment-slips/' . $order->id, Str::uuid() . '.' . $extension, 'public');
                if (! $newSlipPath) {
                    throw new \RuntimeException('บันทึกสลิปไม่สำเร็จ');
                }
            }

            DB::beginTransaction();
            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'amount' => $order->total_price,
                    'payment_method' => $validated['payment_method'],
                    'slip_path' => $newSlipPath,
                    'reference_no' => null,
                    'status' => 'pending',
                    'paid_at' => null,
                ]
            );
            $order->update(['payment_status' => 'pending']);
            DB::commit();

            if ($oldSlipPath && $oldSlipPath !== $newSlipPath && Storage::disk('public')->exists($oldSlipPath)) {
                Storage::disk('public')->delete($oldSlipPath);
            }

            return back()->with('success', $validated['payment_method'] === 'pay_at_store'
                ? 'เลือกชำระที่หน้าร้านแล้ว กรุณาชำระยอดที่ร้านยืนยันไว้'
                : 'ส่งหลักฐานการชำระเงินแล้ว กรุณารอร้านตรวจ');
        } catch (Throwable $e) {
            DB::rollBack();
            if ($newSlipPath && Storage::disk('public')->exists($newSlipPath)) {
                Storage::disk('public')->delete($newSlipPath);
            }
            report($e);
            return back()->withErrors(['payment' => 'ไม่สามารถบันทึกการชำระเงินได้ กรุณาลองใหม่อีกครั้ง']);
        }
    }

    public function confirm(Order $order): RedirectResponse
    {
        $order->load(['payment', 'user']);
        $payment = $order->payment;

        if ($order->status !== 'waiting_payment') {
            return back()->withErrors(['payment' => 'สถานะงานไม่อยู่ในขั้นตอนรอชำระเงิน']);
        }
        if (! $payment) {
            return back()->withErrors(['payment' => 'ไม่พบข้อมูลการชำระเงินของรายการนี้']);
        }
        if ($payment->status === 'paid' || $order->payment_status === 'paid') {
            return back()->with('success', 'รายการนี้ได้รับการยืนยันการชำระเงินแล้ว');
        }
        if (abs((float) $payment->amount - (float) $order->total_price) > 0.009) {
            return back()->withErrors(['payment' => 'ยอดในรายการชำระเงินไม่ตรงกับยอดที่ตรวจล่าสุด กรุณาให้ลูกค้าส่งการชำระเงินใหม่']);
        }
        if ($payment->payment_method === 'bank_transfer' && ! $payment->slip_path) {
            return back()->withErrors(['payment' => 'ไม่พบสลิปสำหรับการโอนเงิน']);
        }

        try {
            DB::transaction(function () use ($payment, $order) {
                $payment->update(['status' => 'paid', 'paid_at' => now()]);
                $order->update(['payment_status' => 'paid', 'status' => 'processing']);
            });

            $order->refresh();
            $order->user?->notify(new OrderStatusChanged($order));
            return back()->with('success', 'ยืนยันการชำระเงินเรียบร้อยแล้ว งานเข้าสู่ขั้นตอนดำเนินการ');
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['payment' => 'ไม่สามารถยืนยันการชำระเงินได้ กรุณาลองใหม่อีกครั้ง']);
        }
    }

    public function reject(Order $order): RedirectResponse
    {
        $order->load('payment');
        $payment = $order->payment;

        if ($order->status !== 'waiting_payment') {
            return back()->withErrors(['payment' => 'ไม่สามารถเปลี่ยนผลการชำระเงินในสถานะงานปัจจุบันได้']);
        }
        if (! $payment) {
            return back()->withErrors(['payment' => 'ไม่พบข้อมูลการชำระเงินของรายการนี้']);
        }
        if ($payment->payment_method === 'pay_at_store') {
            return back()->withErrors(['payment' => 'รายการชำระที่หน้าร้านไม่มีหลักฐานแบบสลิปให้ตรวจ']);
        }
        if ($payment->status === 'paid') {
            return back()->withErrors(['payment' => 'รายการนี้ยืนยันการชำระเงินแล้ว']);
        }

        $payment->update(['status' => 'rejected', 'paid_at' => null]);
        $order->update(['payment_status' => 'unpaid']);

        return back()->with('success', 'บันทึกว่าหลักฐานการชำระเงินไม่ผ่านแล้ว ลูกค้าสามารถส่งใหม่ได้');
    }
}
