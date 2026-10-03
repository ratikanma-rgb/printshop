<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_no',
        'queue_no',
        'total_price',
        'status',
        'payment_status',
        'note',
        'review_note',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    // เจ้าของคำสั่งงาน
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // รายละเอียดงาน เช่น พิมพ์สี A4 จำนวน 5 ชุด
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ไฟล์ที่ลูกค้าอัปโหลด
    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    // ข้อมูลการชำระเงิน
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}