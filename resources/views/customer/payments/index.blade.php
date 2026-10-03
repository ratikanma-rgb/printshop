@extends('layouts.customer')
@section('title','การชำระเงิน | PrintShop')
@section('page-title','รายการที่ร้านตรวจแล้ว')
@section('page-subtitle','แสดงงานที่ร้านตรวจไฟล์และยืนยันราคาแล้ว')
@section('content')
<div class="table-card"><div class="table-top"><h2>งานที่รอชำระเงิน</h2></div><div class="table-scroll"><table><thead><tr><th>เลขคิว</th><th>บริการ</th><th>ยอดที่ยืนยัน</th><th>สถานะชำระ</th><th>จัดการ</th></tr></thead><tbody>
@forelse($orders as $order)<tr><td class="queue">{{ $order->queue_no ?? '-' }}</td><td>{{ $order->items->first()?->service?->name ?? '-' }}</td><td class="money">{{ number_format($order->total_price,2) }} บาท</td><td>@if($order->payment?->status==='pending')<span class="badge b-waiting">รอตรวจการชำระ</span>@elseif($order->payment?->status==='rejected')<span class="badge b-cancelled">สลิปไม่ผ่าน</span>@else<span class="badge b-unpaid">ยังไม่ชำระ</span>@endif</td><td><a class="btn btn-primary" href="{{ route('customer.orders.show',$order) }}">ชำระ / ดูรายละเอียด</a></td></tr>
@empty<tr><td colspan="5" class="empty">ไม่มีงานที่เปิดให้ชำระเงิน</td></tr>@endforelse
</tbody></table></div></div>
@endsection
