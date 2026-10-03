@extends('layouts.admin')
@section('title','ภาพรวมร้าน | PrintShop')
@section('page-title','ภาพรวมร้านวันนี้')
@section('page-subtitle','ติดตามยอดงาน ผู้ใช้งาน บริการ และรายรับจากจุดเดียว')
@section('content')
<section class="hero"><div class="hero-main"><small>PRINTSHOP CONTROL CENTER</small><h2>งานทุกคิว อยู่ในสายตาคุณ</h2><p>ดูภาพรวมร้านและเข้าถึงเครื่องมือหลังร้านได้อย่างเป็นระบบ</p></div><div class="revenue-card"><small>รายรับที่ชำระแล้ว</small><strong>{{ number_format($totalRevenue,2) }}</strong><span>บาท</span></div></section>
<section class="stats">
<div class="stat" style="--accent:#0891b2"><div class="stat-label">ผู้ใช้งานทั้งหมด</div><div class="stat-value">{{ $totalUsers }}</div></div>
<div class="stat" style="--accent:#7c5ac7"><div class="stat-label">ลูกค้า</div><div class="stat-value">{{ $totalCustomers }}</div></div>
<div class="stat" style="--accent:#f57b20"><div class="stat-label">พนักงาน</div><div class="stat-value">{{ $totalStaff }}</div></div>
<div class="stat" style="--accent:#18a05e"><div class="stat-label">บริการ</div><div class="stat-value">{{ $totalServices }}</div></div>
<div class="stat" style="--accent:#2563eb"><div class="stat-label">งานทั้งหมด</div><div class="stat-value">{{ $totalOrders }}</div></div>
<div class="stat" style="--accent:#7c5ac7"><div class="stat-label">รอชำระเงิน</div><div class="stat-value">{{ $waitingPaymentOrders }}</div></div>
<div class="stat" style="--accent:#11a8c3"><div class="stat-label">กำลังดำเนินการ</div><div class="stat-value">{{ $processingOrders }}</div></div>
<div class="stat" style="--accent:#18a05e"><div class="stat-label">พร้อมรับ</div><div class="stat-value">{{ $readyOrders }}</div></div>
</section>
<div class="section-head"><div><h2>เครื่องมือหลังร้าน</h2><p>แต่ละเมนูแยกหน้าที่ชัดเจน</p></div></div>
<section class="tool-grid">
<div class="tool-card"><div class="tool-icon">🖨️</div><h3>จัดการบริการ</h3><p>เพิ่ม แก้ไข ราคา หน่วย และสถานะบริการที่ลูกค้าสามารถเลือกได้</p><a class="btn btn-primary" href="{{ route('admin.services.index') }}">จัดการบริการ</a></div>
<div class="tool-card" style="--c1:#8b6bd5;--c2:#7150bd"><div class="tool-icon">👥</div><h3>จัดการผู้ใช้งาน</h3><p>ตรวจสอบบัญชีลูกค้า พนักงาน และกำหนดสิทธิ์การใช้งาน</p><a class="btn btn-purple" href="{{ route('admin.users.index') }}">จัดการผู้ใช้งาน</a></div>
<div class="tool-card" style="--c1:#20b96b;--c2:#168f50"><div class="tool-icon">📊</div><h3>รายงานร้าน</h3><p>ดูยอดงาน สถานะการทำงาน การชำระเงิน และรายรับรวม</p><a class="btn btn-green" href="{{ route('admin.reports.index') }}">ดูรายงาน</a></div>
</section>
<div class="section-head"><div><h2>งานล่าสุด</h2><p>รายการที่เข้าระบบล่าสุด</p></div></div>
<div class="table-card"><div class="table-scroll"><table><thead><tr><th>เลขคิว</th><th>ลูกค้า</th><th>บริการ</th><th>ราคา</th><th>สถานะ</th><th>วันที่</th></tr></thead><tbody>
@forelse($latestOrders as $order) @php $statusText=match($order->status){'pending'=>'รอตรวจไฟล์','waiting_payment'=>'รอชำระเงิน','processing'=>'กำลังดำเนินการ','ready'=>'พร้อมรับ','completed'=>'เสร็จสิ้น','cancelled'=>'ยกเลิก',default=>$order->status}; $cls=match($order->status){'pending'=>'b-pending','waiting_payment'=>'b-waiting','processing'=>'b-processing','ready'=>'b-ready','completed'=>'b-completed','cancelled'=>'b-cancelled',default=>'b-pending'}; @endphp
<tr><td class="queue">{{ $order->queue_no ?? '-' }}</td><td>{{ $order->user?->name ?? '-' }}</td><td>{{ $order->items->first()?->service?->name ?? '-' }}</td><td class="money">{{ number_format($order->total_price,2) }} ฿</td><td><span class="badge {{ $cls }}">{{ $statusText }}</span></td><td>{{ $order->created_at->format('d/m/Y H:i') }}</td></tr>
@empty<tr><td colspan="6" class="empty">ยังไม่มีรายการงาน</td></tr>@endforelse
</tbody></table></div></div>
@endsection