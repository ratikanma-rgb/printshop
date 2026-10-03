@extends('layouts.staff')
@section('title','พิมพ์งาน | PrintShop')
@section('page-title','พิมพ์งาน')
@section('page-subtitle','ตรวจการตั้งค่าก่อนเปิดหน้าต่างพิมพ์')

@section('content')
@php
    $item = $order->items->first();
    $paper = strtoupper($item?->paper_size ?? 'A4');
    $quantity = max(1, (int)($item?->quantity ?? 1));
    $isColor = ($item?->print_color === 'color');
    $isDouble = ($item?->print_side === 'double');
    $imageExt = ['jpg','jpeg','png','gif','webp'];
@endphp

<style>
.print-wrap{display:flex;flex-direction:column;gap:18px}.print-card{background:#fff;border:1px solid #dce8f3;border-radius:18px;padding:22px;box-shadow:0 8px 24px rgba(20,60,90,.06)}.print-card h2{margin:0 0 14px;color:#123b63;font-size:20px}.setting-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}.setting-box{background:#f7fbfe;border:1px solid #e1edf4;border-radius:12px;padding:14px}.setting-box span{display:block;color:#77899b;font-size:12px;margin-bottom:5px}.setting-box strong{color:#173d5f}.notice{padding:14px 16px;border-radius:12px;background:#fff8e8;border:1px solid #f3d69a;color:#785815;line-height:1.65}.file-list{display:flex;flex-direction:column;gap:12px}.file-row{display:flex;align-items:center;justify-content:space-between;gap:14px;border:1px solid #e1eaf1;border-radius:12px;padding:14px 16px}.file-name{min-width:0;overflow-wrap:anywhere;font-weight:700;color:#173d5f}.preview{margin-top:12px;padding:16px;background:#eef3f6;border-radius:12px;text-align:center}.preview img{max-width:100%;max-height:520px;object-fit:contain;background:#fff}.actions{display:flex;gap:10px;flex-wrap:wrap}.btn-print{border:0;border-radius:10px;padding:11px 18px;background:#16a34a;color:#fff;font-weight:700;cursor:pointer}.btn-open{display:inline-flex;align-items:center;border-radius:10px;padding:10px 16px;background:#2563eb;color:#fff;text-decoration:none;font-weight:700}.print-output{display:none}
@media(max-width:850px){.setting-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:520px){.setting-grid{grid-template-columns:1fr}}
@media print{
  html,body,.print-output,.print-sheet,.print-sheet img{
    -webkit-print-color-adjust:exact!important;
    print-color-adjust:exact!important;
  }
  .sidebar,.topbar,.page-header,.system-badge,.no-print,.print-wrap{display:none!important}
  html,body,.main,.main-inner,.page,.container{margin:0!important;padding:0!important;width:100%!important;max-width:none!important;background:#fff!important}
  .print-output{display:block!important}
  .print-sheet{width:100%;height:100vh;display:flex;align-items:center;justify-content:center;page-break-after:always;break-after:page;overflow:hidden}
  .print-sheet:last-child{page-break-after:auto;break-after:auto}
  .print-sheet img{display:block;max-width:100%;max-height:100%;width:auto;height:auto;object-fit:contain;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
}
</style>

<div class="print-wrap no-print">
  <section class="print-card">
    <h2>ตั้งค่าตามรายการนี้ก่อนพิมพ์</h2>
    <div class="setting-grid">
      <div class="setting-box"><span>กระดาษ</span><strong>{{ $paper }}</strong></div>
      <div class="setting-box"><span>สี</span><strong>{{ $isColor ? 'สี' : 'ขาวดำ' }}</strong></div>
      <div class="setting-box"><span>การพิมพ์</span><strong>{{ $isDouble ? 'สองหน้า' : 'หน้าเดียว' }}</strong></div>
      <div class="setting-box"><span>จำนวนชุด</span><strong>{{ $quantity }} ชุด</strong></div>
      <div class="setting-box"><span>ราคารวม</span><strong>{{ number_format((float)$order->total_price, 2) }} บาท</strong></div>
    </div>
  </section>

  <section class="print-card">
    <h2>ไฟล์งาน</h2>
    <div class="file-list">
      @forelse($order->files as $file)
        @php
          $ext = strtolower($file->extension ?: pathinfo($file->original_name, PATHINFO_EXTENSION));
          $url = asset('storage/'.$file->file_path);
        @endphp
        <div>
          <div class="file-row">
            <div class="file-name">📎 {{ $file->original_name }}</div>
            @if(!in_array($ext,$imageExt,true))
              <a class="btn-open" href="{{ $url }}" target="_blank" rel="noopener">เปิดไฟล์ต้นฉบับ</a>
            @endif
          </div>
          @if(in_array($ext,$imageExt,true))
            <div class="preview"><img class="screen-preview" src="{{ $url }}" alt="{{ $file->original_name }}"></div>
          @elseif($ext==='pdf')
            <div class="notice" style="margin-top:10px">ไฟล์ PDF ให้กด “เปิดไฟล์ต้นฉบับ” แล้วสั่งพิมพ์จาก PDF โดยตรง เพื่อให้จำนวนหน้าและขนาดเอกสารถูกต้อง</div>
          @endif
        </div>
      @empty
        <div class="notice">ไม่พบไฟล์งาน</div>
      @endforelse
    </div>
  </section>

  <div class="actions">
    <a class="btn-open" style="background:#e9f0f5;color:#38556d" href="{{ route('staff.orders.show',$order) }}">← กลับหน้ารายละเอียด</a>
    @if($order->files->contains(function($f) use ($imageExt){$e=strtolower($f->extension ?: pathinfo($f->original_name,PATHINFO_EXTENSION));return in_array($e,$imageExt,true);}))
      <button class="btn-print" type="button" onclick="printImages()">🖨️ พิมพ์ไฟล์รูปภาพ</button>
    @endif
  </div>
</div>

<div class="print-output" id="printOutput" aria-hidden="true">
  @foreach($order->files as $file)
    @php
      $ext = strtolower($file->extension ?: pathinfo($file->original_name, PATHINFO_EXTENSION));
      $url = asset('storage/'.$file->file_path);
    @endphp
    @if(in_array($ext,$imageExt,true))
      @for($copy=1;$copy<=$quantity;$copy++)
        <div class="print-sheet"><img class="print-source-image" src="{{ $url }}" alt=""></div>
      @endfor
    @endif
  @endforeach
</div>

<script>
function printImages(){
  const first = document.querySelector('.print-source-image');
  if(!first){ alert('ไม่พบไฟล์รูปภาพสำหรับพิมพ์'); return; }
  const doPrint = () => {
    const orientation = first.naturalWidth >= first.naturalHeight ? 'landscape' : 'portrait';
    let pageStyle = document.getElementById('dynamicPrintPage');
    if(!pageStyle){ pageStyle=document.createElement('style'); pageStyle.id='dynamicPrintPage'; document.head.appendChild(pageStyle); }
    pageStyle.textContent = `@page { size: {{ $paper }} ${orientation}; margin: 0; }`;
    window.print();
  };
  if(first.complete && first.naturalWidth){ doPrint(); } else { first.onload=doPrint; first.onerror=()=>alert('โหลดไฟล์รูปภาพไม่สำเร็จ'); }
}
</script>
@endsection
