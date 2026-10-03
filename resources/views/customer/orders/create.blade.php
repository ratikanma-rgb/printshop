@extends('layouts.customer')
@section('title','สั่งพิมพ์งานใหม่ | PrintShop')
@section('page-title','สั่งพิมพ์งานใหม่')
@section('page-subtitle','กรอกรายละเอียดและแนบไฟล์ให้ร้านตรวจ ก่อนชำระเงิน')

@section('content')
<div class="panel" style="margin-bottom:16px;background:#fffaf3;border-color:#f4d9b2">
    <strong>ขั้นตอนการสั่งงาน:</strong> 1) ส่งรายละเอียดและไฟล์ → 2) ร้านตรวจไฟล์และยืนยันราคา → 3) ชำระเงิน → 4) ร้านเริ่มพิมพ์
</div>

<form method="POST" action="{{ route('customer.orders.store') }}" enctype="multipart/form-data" class="form-card">
    @csrf
    <div class="form-grid">
        <div class="field full">
            <label for="service_id">บริการ</label>
            <select name="service_id" id="service_id" required>
                <option value="">-- เลือกบริการ --</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-unit="{{ $service->unit }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                        {{ $service->name }} — {{ number_format($service->price,2) }} บาท / {{ $service->unit }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="paper_size">ขนาดกระดาษ</label>
            <select name="paper_size" id="paper_size" required>
                <option value="A4" {{ old('paper_size','A4')==='A4'?'selected':'' }}>A4</option>
                <option value="A3" {{ old('paper_size')==='A3'?'selected':'' }}>A3</option>
            </select>
        </div>

        <div class="field">
            <label for="print_color">รูปแบบสี</label>
            <select name="print_color" id="print_color" required>
                <option value="black_white" {{ old('print_color','black_white')==='black_white'?'selected':'' }}>ขาวดำ</option>
                <option value="color" {{ old('print_color')==='color'?'selected':'' }}>สี</option>
            </select>
        </div>

        <div class="field">
            <label for="print_side">รูปแบบการพิมพ์</label>
            <select name="print_side" id="print_side" required>
                <option value="single" {{ old('print_side','single')==='single'?'selected':'' }}>พิมพ์หน้าเดียว</option>
                <option value="double" {{ old('print_side')==='double'?'selected':'' }}>พิมพ์สองหน้า</option>
            </select>
        </div>

        <div class="field">
            <label for="pages">จำนวนหน้า</label>
            <input type="number" name="pages" id="pages" min="1" max="100000" value="{{ old('pages',1) }}" required>
        </div>

        <div class="field">
            <label for="quantity">จำนวนชุด</label>
            <input type="number" name="quantity" id="quantity" min="1" max="100000" value="{{ old('quantity',1) }}" required>
        </div>

        <div class="field full">
            <label>ยอดเบื้องต้น</label>
            <div class="panel" style="padding:14px;box-shadow:none;background:#f4f9fd">
                <strong id="estimated-price" class="money" style="font-size:22px">0.00 บาท</strong>
                <div style="color:#6d8093;font-size:12px;margin-top:4px">ราคานี้เป็นยอดเบื้องต้น ร้านจะตรวจไฟล์และยืนยันราคาอีกครั้งก่อนชำระเงิน</div>
            </div>
        </div>

        <div class="field full">
            <label for="files">แนบไฟล์งาน</label>
            <input type="file" name="files[]" id="files" multiple required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            <small style="color:#6d8093">รองรับ PDF, Word, JPG, PNG สูงสุด 10 ไฟล์ และไม่เกิน 40 MB ต่อไฟล์</small>
        </div>

        <div class="field full">
            <label for="note">รายละเอียดเพิ่มเติม</label>
            <textarea name="note" id="note" maxlength="2000" placeholder="เช่น พิมพ์หน้า 1-50, เรียงชุด, เข้าเล่ม หรือรายละเอียดอื่น ๆ">{{ old('note') }}</textarea>
        </div>
    </div>

    <div class="actions" style="margin-top:18px">
        <a class="btn btn-gray" href="{{ route('customer.orders.index') }}">ยกเลิก</a>
        <button class="btn btn-orange" type="submit">ส่งงานให้ร้านตรวจ</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function(){
    const service = document.getElementById('service_id');
    const pages = document.getElementById('pages');
    const quantity = document.getElementById('quantity');
    const out = document.getElementById('estimated-price');
    function recalc(){
        const option = service.options[service.selectedIndex];
        const price = Number(option?.dataset?.price || 0);
        const unit = String(option?.dataset?.unit || '').toLowerCase();
        const p = Math.max(1, Number(pages.value || 1));
        const q = Math.max(1, Number(quantity.value || 1));
        const total = (unit === 'หน้า' || unit === 'page') ? price*p*q : price*q;
        out.textContent = total.toLocaleString('th-TH',{minimumFractionDigits:2,maximumFractionDigits:2})+' บาท';
    }
    [service,pages,quantity].forEach(el => el && el.addEventListener('input',recalc));
    recalc();
})();
</script>
@endpush
