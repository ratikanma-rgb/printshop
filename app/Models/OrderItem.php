<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'service_id',
        'paper_size',
        'print_color',
        'print_side',
        'pages',
        'quantity',
        'unit_price',
        'subtotal',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'pages' => 'integer',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    // รายการนี้อยู่ในคำสั่งงานใด
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // รายการนี้ใช้บริการอะไร
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}