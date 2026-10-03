@extends('layouts.staff')
@section('title','ตรวจงานลูกค้า | PrintShop')
@section('page-title','ตรวจงานลูกค้า')
@section('page-subtitle','เปิดไฟล์ ตรวจรายละเอียด และยืนยันราคาก่อนให้ลูกค้าชำระเงิน')

@section('content')
@php
$item=$order->items->first();
$statusText=match($order->status){'pending'=>'รอตรวจไฟล์','waiting_payment'=>'รอชำระเงิน','processing'=>'กำลังดำเนินการ','ready'=>'พร้อมรับ','completed'=>'เสร็จสิ้น','cancelled'=>'ไม่อนุมัติ/ยกเลิก',default=>$order->status};
$statusClass=match($order->status){'pending'=>'b-pending','waiting_payment'=>'b-waiting','processing'=>'b-processing','ready'=>'b-ready','completed'=>'b-completed','cancelled'=>'b-cancelled',default=>'b-pending'};
$nextStatuses=($order->payment_status==='paid') ? match($order->status){'processing'=>['ready'=>'พร้อมรับ','cancelled'=>'ยกเลิก'],'ready'=>['completed'=>'เสร็จสิ้น'],default=>[]} : [];
@endphp

<div class="panel">
    <div class="detail-grid">
        <div class="detail-row"><span class="label">เลขคิว</span><div class="value queue" style="font-size:24px">{{ $order->queue_no ?? '-' }}</div></div>
        <div class="detail-row"><span class="label">สถานะ</span><div class="value"><span class="badge {{ $statusClass }}">{{ $statusText }}</span></div></div>
        <div class="detail-row"><span class="label">ลูกค้า</span><div class="value">{{ $order->user?->name ?? '-' }}</div></div>
        <div class="detail-row"><span class="label">อีเมล</span><div class="value">{{ $order->user?->email ?? '-' }}</div></div>
        <div class="detail-row"><span class="label">ยอดปัจจุบัน</span><div class="value money">{{ number_format($order->total_price,2) }} บาท</div></div>
        <div class="detail-row"><span class="label">หมายเหตุลูกค้า</span><div class="value">{{ $order->note ?: '-' }}</div></div>
    </div>
</div>

<div class="panel" style="margin-top:18px">
    <div class="section-head" style="margin-top:0"><div><h2>ไฟล์จากลูกค้า</h2><p>เปิดไฟล์เพื่อตรวจรายละเอียดก่อนยืนยันราคา</p></div></div>
    <div class="files">@forelse($order->files as $file)<div class="file-row"><div>📎 <strong>{{ $file->original_name }}</strong> <small>({{ strtoupper($file->extension ?? '') }}, {{ number_format($file->file_size/1024,1) }} KB)</small></div><a class="btn btn-primary" href="{{ asset('storage/'.$file->file_path) }}" target="_blank" rel="noopener">เปิดไฟล์</a></div>@empty<div class="flash error">ไม่พบไฟล์แนบ กรุณาให้ลูกค้าส่งไฟล์ใหม่</div>@endforelse</div>
</div>

@if($order->status==='pending')
<div class="form-card" style="margin-top:18px;max-width:none">
    <div class="section-head" style="margin-top:0"><div><h2>ตรวจรายละเอียดงาน</h2><p>ตรวจขนาดกระดาษ จำนวนหน้า จำนวนชุด และราคาให้ตรงกับไฟล์ที่ลูกค้าส่ง</p></div></div>
    <form method="POST" action="{{ route('staff.orders.review',$order) }}">
        @csrf @method('PATCH')
        <div class="form-grid">
            <div class="field full">
                <label>บริการ</label>
                <select name="service_id" required>
                    @foreach($services as $service)<option value="{{ $service->id }}" data-unit="{{ $service->unit }}" {{ old('service_id',$item?->service_id)==$service->id?'selected':'' }}>{{ $service->name }} (หน่วย: {{ $service->unit }})</option>@endforeach
                </select>
            </div>
            <div class="field"><label>ขนาดกระดาษ</label><select name="paper_size" required><option value="A4" {{ old('paper_size',$item?->paper_size)==='A4'?'selected':'' }}>A4</option><option value="A3" {{ old('paper_size',$item?->paper_size)==='A3'?'selected':'' }}>A3</option></select></div>
            <div class="field"><label>สี</label><select name="print_color" required><option value="black_white" {{ old('print_color',$item?->print_color)==='black_white'?'selected':'' }}>ขาวดำ</option><option value="color" {{ old('print_color',$item?->print_color)==='color'?'selected':'' }}>สี</option></select></div>
            <div class="field"><label>ด้าน</label><select name="print_side" required><option value="single" {{ old('print_side',$item?->print_side)==='single'?'selected':'' }}>หน้าเดียว</option><option value="double" {{ old('print_side',$item?->print_side)==='double'?'selected':'' }}>สองหน้า</option></select></div>
            <div class="field"><label>จำนวนหน้าจริง</label><input type="number" name="pages" min="1" max="100000" value="{{ old('pages',$item?->pages ?? 1) }}" required></div>
            <div class="field"><label>จำนวนชุด</label><input type="number" name="quantity" min="1" max="100000" value="{{ old('quantity',$item?->quantity ?? 1) }}" required></div>
            <div class="field"><label>ราคาต่อหน่วย (บาท)</label><input type="number" name="unit_price" min="0" max="9999999.99" step="0.01" value="{{ old('unit_price',$item?->unit_price ?? 0) }}" required></div>
            <div class="field full"><label>หมายเหตุถึงลูกค้า</label><textarea name="review_note" maxlength="2000" placeholder="เช่น ไฟล์เป็น A3 จำนวน 50 หน้า จึงปรับราคาให้ตรงกับงาน">{{ old('review_note') }}</textarea></div>
        </div>
        <div class="actions" style="margin-top:16px">
            <button class="btn btn-green" type="submit" name="action" value="approve" onclick="return confirm('ยืนยันราคาและเปิดให้ลูกค้าชำระเงินตามยอดนี้?')">ยืนยันราคาและให้ลูกค้าชำระเงิน</button>
            <button class="btn btn-red" type="submit" name="action" value="reject" formnovalidate onclick="return confirm('ไม่อนุมัติรายการนี้และให้ลูกค้าแก้ไขหรือส่งใหม่?')">ไม่อนุมัติ / ให้ลูกค้าแก้ไข</button>
        </div>
    </form>
</div>
@endif

@if($order->review_note)
<div class="panel" style="margin-top:18px"><strong>หมายเหตุจากร้าน:</strong> {{ $order->review_note }}</div>
@endif

<div class="panel" style="margin-top:18px">
    <div class="section-head" style="margin-top:0"><div><h2>การชำระเงิน</h2><p>ยอดที่ต้องชำระ {{ number_format($order->total_price,2) }} บาท</p></div></div>
    @if($order->status==='pending')
        <div class="flash error">ยังยืนยันการชำระเงินไม่ได้ กรุณาตรวจไฟล์และยืนยันราคาก่อน</div>
    @elseif($order->payment)
        <div class="detail-grid">
            <div class="detail-row"><span class="label">วิธีชำระเงิน</span><div class="value">{{ $order->payment->payment_method==='bank_transfer'?'โอนเงิน / คิวอาร์โค้ด':'ชำระหน้าร้าน' }}</div></div>
            <div class="detail-row"><span class="label">ยอดชำระ</span><div class="value money">{{ number_format($order->payment->amount,2) }} บาท</div></div>
            <div class="detail-row"><span class="label">สถานะการชำระเงิน</span><div class="value">{{ $order->payment->status==='paid'?'ยืนยันแล้ว':($order->payment->status==='rejected'?'ไม่ผ่าน':'รอตรวจ') }}</div></div>
        </div>
        @if($order->payment->status==='pending')
            <div class="actions" style="margin-top:14px">
                @if($order->payment->slip_path)<a class="btn btn-primary" href="{{ route('staff.payments.slip',$order) }}">ดูหลักฐานการชำระเงิน</a>@endif
                <form method="POST" action="{{ route('staff.payments.confirm',$order) }}">@csrf @method('PATCH')<button class="btn btn-green" type="submit" onclick="return confirm('ยืนยันว่าได้รับเงินครบ {{ number_format($order->total_price,2) }} บาท?')">ยืนยันการชำระเงิน</button></form>
                @if($order->payment->payment_method==='bank_transfer')<form method="POST" action="{{ route('staff.payments.reject',$order) }}">@csrf @method('PATCH')<button class="btn btn-red" type="submit" onclick="return confirm('ยืนยันว่าหลักฐานการชำระเงินนี้ไม่ผ่าน?')">หลักฐานไม่ผ่าน</button></form>@endif
            </div>
        @endif
    @else
        <div class="flash" style="background:#f4f9fd;border:1px solid #dbe8f1;color:#40566d">ยังไม่มีการชำระเงินจากลูกค้า</div>
    @endif
</div>

<div class="panel" style="margin-top:18px">
    <div class="section-head" style="margin-top:0"><div><h2>ดำเนินงาน</h2><p>เริ่มพิมพ์งานได้หลังยืนยันการชำระเงินแล้ว</p></div></div>

    @if($order->payment_status!=='paid')
        <div class="flash" style="background:#f4f9fd;border:1px solid #dbe8f1;color:#40566d">ยังดำเนินงานไม่ได้ กรุณารอให้ลูกค้าชำระเงินและยืนยันการชำระเงินก่อน</div>
    @else
        <div class="actions">
            @if($order->status==='processing')
                <a class="btn btn-primary" href="{{ route('staff.orders.print',$order) }}">เปิดหน้าพิมพ์งาน</a>
                <form method="POST" action="{{ route('staff.orders.status',$order) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="ready">
                    <button class="btn btn-green" type="submit" onclick="return confirm('ยืนยันว่าพิมพ์งานเสร็จและพร้อมให้ลูกค้ารับงานแล้ว?')">พิมพ์เสร็จ / พร้อมรับงาน</button>
                </form>
                <form method="POST" action="{{ route('staff.orders.status',$order) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="cancelled">
                    <button class="btn btn-red" type="submit" onclick="return confirm('ยืนยันว่าต้องการยกเลิกงานนี้?')">ยกเลิกงาน</button>
                </form>
            @elseif($order->status==='ready')
                <a class="btn btn-primary" href="{{ route('staff.orders.print',$order) }}">เปิดหน้าพิมพ์งาน</a>
                <form method="POST" action="{{ route('staff.orders.status',$order) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="completed">
                    <button class="btn btn-green" type="submit" onclick="return confirm('ยืนยันว่าลูกค้ารับงานแล้วและปิดงานนี้?')">ลูกค้ารับงานแล้ว / เสร็จสิ้น</button>
                </form>
            @elseif($order->status==='completed')
                <div class="flash" style="margin:0;background:#eefbf4;border:1px solid #ccebd9;color:#25724a">งานนี้เสร็จสิ้นแล้ว</div>
            @endif
        </div>
    @endif
</div>
@endsection
