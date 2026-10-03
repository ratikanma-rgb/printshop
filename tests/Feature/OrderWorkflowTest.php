<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_order_with_file(): void
    {
        Storage::fake('public');

        $customer = User::factory()->create(['role' => 'customer']);
        $service = Service::create([
            'name' => 'พิมพ์ขาวดำ',
            'description' => 'ทดสอบ',
            'price' => 2.00,
            'unit' => 'หน้า',
            'is_active' => true,
        ]);

        $response = $this->actingAs($customer)->post(route('customer.orders.store'), [
            'service_id' => $service->id,
            'paper_size' => 'A4',
            'print_color' => 'black_white',
            'print_side' => 'single',
            'pages' => 3,
            'quantity' => 2,
            'note' => 'งานทดสอบ',
            'files' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
        ]);

        $response->assertRedirect(route('customer.orders.index'));

        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'total_price' => 12.00,
            'status' => 'pending',
        ]);

        $order = Order::firstOrFail();
        $this->assertCount(1, $order->files);
        Storage::disk('public')->assertExists($order->files->first()->file_path);
    }

    public function test_staff_status_change_notifies_customer(): void
    {
        Notification::fake();

        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        $order = Order::create([
            'user_id' => $customer->id,
            'order_no' => 'ORD-TEST-001',
            'queue_no' => 'Q-TEST-001',
            'total_price' => 100,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($staff)
            ->patch(route('staff.orders.status', $order), ['status' => 'waiting_payment'])
            ->assertRedirect(route('staff.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'waiting_payment',
        ]);

        Notification::assertSentTo($customer, OrderStatusChanged::class);
    }
    public function test_staff_can_open_print_sheet_for_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        $order = Order::create([
            'user_id' => $customer->id,
            'order_no' => 'ORD-PRINT-001',
            'queue_no' => 'Q-PRINT-001',
            'total_price' => 50,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($staff)
            ->get(route('staff.orders.print', $order))
            ->assertOk()
            ->assertSee('ใบสั่งงานพิมพ์')
            ->assertSee('Q-PRINT-001');
    }

    public function test_customer_cannot_open_staff_print_sheet(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::create([
            'user_id' => $customer->id,
            'order_no' => 'ORD-PRINT-002',
            'queue_no' => 'Q-PRINT-002',
            'total_price' => 50,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($customer)
            ->get(route('staff.orders.print', $order))
            ->assertForbidden();
    }

}
