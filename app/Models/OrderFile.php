<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'extension',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    // ไฟล์นี้เป็นของคำสั่งงานใด
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}