<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?string $customTitle = null,
        public ?string $customMessage = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusText = match ($this->order->status) {
            'pending' => 'รอตรวจสอบ',
            'waiting_payment' => 'รอชำระเงิน',
            'processing' => 'กำลังดำเนินการ',
            'ready' => 'พร้อมรับงาน',
            'completed' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิก',
            default => $this->order->status,
        };

        return [
            'order_id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'queue_no' => $this->order->queue_no,
            'status' => $this->order->status,
            'status_text' => $statusText,
            'title' => $this->customTitle ?? 'สถานะงานมีการเปลี่ยนแปลง',
            'message' => $this->customMessage
                ?? "งาน {$this->order->order_no} เปลี่ยนสถานะเป็น {$statusText}",
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
