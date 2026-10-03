<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->with(['items.service', 'payment'])
            ->latest()
            ->get();

        return view('customer.orders.index', compact('orders'));
    }

    public function payments(): View
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->where('status', 'waiting_payment')
            ->where('payment_status', '!=', 'paid')
            ->with(['items.service', 'payment'])
            ->latest()
            ->get();

        return view('customer.payments.index', compact('orders'));
    }

    public function create(): View
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('customer.orders.create', compact('services'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'paper_size' => ['required', 'in:A4,A3'],
            'print_color' => ['required', 'in:black_white,color'],
            'print_side' => ['required', 'in:single,double'],
            'pages' => ['required', 'integer', 'min:1', 'max:100000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:40960'],
        ], [
            'service_id.required' => 'กรุณาเลือกบริการ',
            'paper_size.required' => 'กรุณาเลือกขนาดกระดาษ',
            'print_color.required' => 'กรุณาเลือกรูปแบบสี',
            'print_side.required' => 'กรุณาเลือกรูปแบบการพิมพ์',
            'pages.required' => 'กรุณาระบุจำนวนหน้า',
            'quantity.required' => 'กรุณาระบุจำนวนชุด',
            'files.required' => 'กรุณาแนบไฟล์งาน',
            'files.min' => 'กรุณาแนบไฟล์งานอย่างน้อย 1 ไฟล์',
            'files.max' => 'สามารถแนบไฟล์งานได้สูงสุด 10 ไฟล์',
        ]);

        $service = Service::query()
            ->whereKey($validated['service_id'])
            ->where('is_active', true)
            ->first();

        if (! $service) {
            return back()->withInput()->withErrors([
                'service_id' => 'บริการที่เลือกไม่พร้อมใช้งาน กรุณาเลือกใหม่',
            ]);
        }

        $pages = (int) $validated['pages'];
        $quantity = (int) $validated['quantity'];
        $unitPrice = (float) $service->price;
        $isPerPage = $service->unit === 'หน้า' || strtolower((string) $service->unit) === 'page';
        $subtotal = $isPerPage ? $unitPrice * $pages * $quantity : $unitPrice * $quantity;
        $storedPaths = [];

        try {
            DB::beginTransaction();

            do {
                $orderNo = 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
            } while (Order::where('order_no', $orderNo)->exists());

            do {
                $queueNo = 'Q-' . now()->format('ymd') . '-' . random_int(1000, 9999);
            } while (Order::where('queue_no', $queueNo)->exists());

            $order = Order::create([
                'user_id' => auth()->id(),
                'order_no' => $orderNo,
                'queue_no' => $queueNo,
                // เป็นเพียงยอดประมาณการจนกว่าพนักงานจะตรวจไฟล์
                'total_price' => round($subtotal, 2),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'note' => $validated['note'] ?? null,
            ]);

            $order->items()->create([
                'service_id' => $service->id,
                'paper_size' => $validated['paper_size'],
                'print_color' => $validated['print_color'],
                'print_side' => $validated['print_side'],
                'pages' => $pages,
                'quantity' => $quantity,
                'unit_price' => round($unitPrice, 2),
                'subtotal' => round($subtotal, 2),
                'note' => $validated['note'] ?? null,
            ]);

            foreach ($request->file('files', []) as $uploadedFile) {
                if (! $uploadedFile->isValid()) {
                    throw new \RuntimeException('ไฟล์อัปโหลดไม่สมบูรณ์');
                }

                $extension = strtolower($uploadedFile->getClientOriginalExtension());
                $storedName = Str::uuid() . '.' . $extension;
                $path = $uploadedFile->storeAs('order-files/' . $order->id, $storedName, 'public');

                if (! $path) {
                    throw new \RuntimeException('ไม่สามารถบันทึกไฟล์งานได้');
                }

                $storedPaths[] = $path;
                $order->files()->create([
                    'original_name' => basename($uploadedFile->getClientOriginalName()),
                    'stored_name' => $storedName,
                    'file_path' => $path,
                    'mime_type' => $uploadedFile->getMimeType(),
                    'extension' => $extension,
                    'file_size' => (int) $uploadedFile->getSize(),
                ]);
            }

            DB::commit();

            return redirect()->route('customer.orders.show', $order)
                ->with('success', 'ส่งงานเรียบร้อยแล้ว กรุณารอร้านตรวจไฟล์และยืนยันราคาก่อนชำระเงิน');
        } catch (Throwable $e) {
            DB::rollBack();

            foreach ($storedPaths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            report($e);

            return back()->withInput()->withErrors([
                'system' => 'ส่งงานไม่สำเร็จ กรุณาตรวจข้อมูลแล้วลองใหม่อีกครั้ง',
            ]);
        }
    }

    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load(['items.service', 'files', 'payment']);

        return view('customer.orders.show', compact('order'));
    }
}
