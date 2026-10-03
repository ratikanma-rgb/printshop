@extends('layouts.customer')
@section('title','รายละเอียดงาน | PrintShop')
@section('page-title','รายละเอียดงานของฉัน')
@section('page-subtitle','ร้านจะตรวจไฟล์และยืนยันราคาก่อนให้ชำระเงิน')

@section('content')
@php
$statusText=match($order->status){'pending'=>'รอร้านตรวจไฟล์','waiting_payment'=>'รอชำระเงิน','processing'=>'กำลังดำเนินการ','ready'=>'พร้อมรับ','completed'=>'เสร็จสิ้น','cancelled'=>'ไม่อนุมัติ/ยกเลิก',default=>$order->status};
$statusClass=match($order->status){'pending'=>'b-pending','waiting_payment'=>'b-waiting','processing'=>'b-processing','ready'=>'b-ready','completed'=>'b-completed','cancelled'=>'b-cancelled',default=>'b-pending'};
@endphp

<div class="panel">
    <div class="detail-grid">
        <div class="detail-row"><span class="label">เลขคิว</span><div class="value queue" style="font-size:24px">{{ $order->queue_no ?? '-' }}</div></div>
        <div class="detail-row"><span class="label">สถานะงาน</span><div class="value"><span class="badge {{ $statusClass }}">{{ $statusText }}</span></div></div>
        <div class="detail-row"><span class="label">เลขที่งาน</span><div class="value">{{ $order->order_no }}</div></div>
        <div class="detail-row"><span class="label">วันที่ส่ง</span><div class="value">{{ $order->created_at->format('d/m/Y H:i') }}</div></div>
        <div class="detail-row"><span class="label">ยอดปัจจุบัน</span><div class="value money">{{ number_format($order->total_price,2) }} บาท</div></div>
        <div class="detail-row"><span class="label">หมายเหตุลูกค้า</span><div class="value">{{ $order->note ?: '-' }}</div></div>
    </div>

    @if($order->status==='pending')
        <div class="flash" style="margin-top:16px;background:#fff5df;border:1px solid #f2d39b;color:#8d5b09">ยอดนี้ยังเป็นราคาเบื้องต้น กรุณารอร้านตรวจไฟล์และยืนยันราคาก่อนชำระเงิน</div>
    @endif
    @if($order->review_note)
        <div class="flash {{ $order->status==='cancelled'?'error':'success' }}" style="margin-top:16px"><strong>หมายเหตุจากร้าน:</strong> {{ $order->review_note }}</div>
    @endif

    <div class="section-head"><div><h2>รายละเอียดงาน</h2></div></div>
    <div class="table-card"><div class="table-scroll"><table><thead><tr><th>บริการ</th><th>กระดาษ</th><th>สี</th><th>ด้าน</th><th>หน้า</th><th>ชุด</th><th>ราคาต่อหน่วย</th><th>รวม</th></tr></thead><tbody>
    @foreach($order->items as $item)
        <tr><td>{{ $item->service?->name ?? '-' }}</td><td>{{ $item->paper_size }}</td><td>{{ $item->print_color==='color'?'สี':'ขาวดำ' }}</td><td>{{ $item->print_side==='double'?'หน้า-หลัง':'หน้าเดียว' }}</td><td>{{ $item->pages }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price,2) }}</td><td class="money">{{ number_format($item->subtotal,2) }} บาท</td></tr>
    @endforeach
    </tbody></table></div></div>

    <div class="section-head"><div><h2>ไฟล์ที่ส่ง</h2></div></div>
    <div class="files">@forelse($order->files as $file)<div class="file-row"><div>📎 {{ $file->original_name }}</div><a class="btn btn-gray" href="{{ asset('storage/'.$file->file_path) }}" target="_blank" rel="noopener">เปิดไฟล์</a></div>@empty<div class="empty">ไม่มีไฟล์แนบ</div>@endforelse</div>
</div>

@if($order->status==='waiting_payment' && $order->payment_status!=='paid')
<div class="panel" style="margin-top:18px">
    <div class="section-head" style="margin-top:0"><div><h2>ชำระเงิน</h2><p>ยอดชำระ {{ number_format($order->total_price,2) }} บาท</p></div></div>

    @if($order->payment?->status==='pending')
        <div class="flash success">ส่งหลักฐานการชำระเงินแล้ว กรุณารอร้านตรวจ</div>
    @elseif($order->payment?->status==='rejected')
        <div class="flash error">หลักฐานการชำระเงินก่อนหน้าไม่ผ่าน กรุณาส่งใหม่ตามยอด {{ number_format($order->total_price,2) }} บาท</div>
    @endif

    <form method="POST" action="{{ route('customer.payments.store',$order) }}" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="field">
                <label for="payment_method">วิธีชำระเงิน</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="bank_transfer">โอนเงิน / สแกนคิวอาร์โค้ด</option>
                    <option value="pay_at_store">ชำระที่หน้าร้าน</option>
                </select>
            </div>
            <div class="field" id="slip-wrap">
                <label for="slip">หลักฐานการชำระเงิน</label>
                <input type="file" name="slip" id="slip" accept=".jpg,.jpeg,.png" required>
            </div>
        </div>
        <div class="actions" style="margin-top:14px"><button class="btn btn-orange" type="submit">แจ้งการชำระเงิน</button></div>
    </form>
</div>
@endif

@if($order->payment)
<div class="panel" style="margin-top:18px">
    <h2 style="margin-top:0">ข้อมูลการชำระเงิน</h2>
    <div class="detail-grid">
        <div class="detail-row"><span class="label">ยอดชำระ</span><div class="value money">{{ number_format($order->payment->amount,2) }} บาท</div></div>
        <div class="detail-row"><span class="label">สถานะ</span><div class="value">{{ $order->payment->status==='paid'?'ชำระแล้ว':($order->payment->status==='rejected'?'ไม่ผ่าน':'รอตรวจ') }}</div></div>
    </div>
    @if($order->payment->slip_path)<div class="actions" style="margin-top:12px"><a class="btn btn-primary" href="{{ asset('storage/'.$order->payment->slip_path) }}" target="_blank" rel="noopener">ดูหลักฐานการชำระเงิน</a></div>@endif
</div>
@endif

<div class="actions" style="margin-top:18px"><a class="btn btn-gray" href="{{ route('customer.orders.index') }}">← กลับรายการงาน</a></div>
@endsection

@push('scripts')
<script>
(function(){
    const method=document.getElementById('payment_method');
    const wrap=document.getElementById('slip-wrap');
    const slip=document.getElementById('slip');
    if(!method||!wrap||!slip) return;
    function toggle(){const transfer=method.value==='bank_transfer';wrap.style.display=transfer?'grid':'none';slip.required=transfer;if(!transfer) slip.value='';}
    method.addEventListener('change',toggle);toggle();
})();
</script>
@endpush
