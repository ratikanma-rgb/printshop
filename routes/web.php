<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminServiceController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerNotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StaffOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| หน้าแรก
|--------------------------------------------------------------------------
|
| เมื่อเข้า http://127.0.0.1:8000
| ให้ไปหน้า Login ทันที
|
*/

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');


/*
|--------------------------------------------------------------------------
| ผู้ที่ยังไม่ได้เข้าสู่ระบบ
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | สมัครสมาชิก
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/register',
        [AuthController::class, 'showRegister']
    )->name('register');

    Route::post(
        '/register',
        [AuthController::class, 'register']
    )
        ->middleware('throttle:10,1')
        ->name('register.store');


    /*
    |--------------------------------------------------------------------------
    | เข้าสู่ระบบ
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/login',
        [AuthController::class, 'showLogin']
    )->name('login');

    Route::post(
        '/login',
        [AuthController::class, 'login']
    )
        ->middleware('throttle:6,1')
        ->name('login.store');
});


/*
|--------------------------------------------------------------------------
| ผู้ที่เข้าสู่ระบบแล้ว
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Redirect Dashboard ตามสิทธิ์
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'redirect']
    )->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard Admin
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/admin/dashboard',
            [AdminController::class, 'index']
        )->name('admin.dashboard');


        /*
        |--------------------------------------------------------------------------
        | จัดการบริการ
        |--------------------------------------------------------------------------
        */

        // รายการบริการ
        Route::get(
            '/admin/services',
            [AdminServiceController::class, 'index']
        )->name('admin.services.index');

        // หน้าเพิ่มบริการ
        Route::get(
            '/admin/services/create',
            [AdminServiceController::class, 'create']
        )->name('admin.services.create');

        // บันทึกบริการใหม่
        Route::post(
            '/admin/services',
            [AdminServiceController::class, 'store']
        )->name('admin.services.store');

        // หน้าแก้ไขบริการ
        Route::get(
            '/admin/services/{service}/edit',
            [AdminServiceController::class, 'edit']
        )->name('admin.services.edit');

        // บันทึกการแก้ไข
        Route::put(
            '/admin/services/{service}',
            [AdminServiceController::class, 'update']
        )->name('admin.services.update');

        // เปิด / ปิดบริการ
        Route::patch(
            '/admin/services/{service}/toggle',
            [AdminServiceController::class, 'toggle']
        )->name('admin.services.toggle');

        // ลบบริการ
        Route::delete(
            '/admin/services/{service}',
            [AdminServiceController::class, 'destroy']
        )->name('admin.services.destroy');


        /*
        |--------------------------------------------------------------------------
        | จัดการผู้ใช้งาน
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/admin/users',
            [AdminUserController::class, 'index']
        )->name('admin.users.index');

        Route::patch(
            '/admin/users/{user}/role',
            [AdminUserController::class, 'updateRole']
        )->name('admin.users.role');


        /*
        |--------------------------------------------------------------------------
        | รายงาน
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/admin/reports',
            [AdminReportController::class, 'index']
        )->name('admin.reports.index');
    });


    /*
    |--------------------------------------------------------------------------
    | Staff + Admin
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin,staff')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard พนักงาน
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/staff/dashboard',
            [StaffOrderController::class, 'index']
        )->name('staff.dashboard');


        /*
        |--------------------------------------------------------------------------
        | ดูสลิปการชำระเงิน
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/staff/orders/{order}/payment/slip',
            [StaffOrderController::class, 'paymentSlip']
        )->name('staff.payments.slip');


        /*
        |--------------------------------------------------------------------------
        | หน้าสั่งพิมพ์งานลูกค้า
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/staff/orders/{order}/print',
            [StaffOrderController::class, 'print']
        )->name('staff.orders.print');


        /*
        |--------------------------------------------------------------------------
        | รายละเอียดงาน
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/staff/orders/{order}',
            [StaffOrderController::class, 'show']
        )->name('staff.orders.show');


        /*
        |--------------------------------------------------------------------------
        | เปลี่ยนสถานะงาน
        |--------------------------------------------------------------------------
        */

        Route::patch(
            '/staff/orders/{order}/status',
            [StaffOrderController::class, 'updateStatus']
        )->name('staff.orders.status');

        // ตรวจไฟล์ แก้รายละเอียด/ราคา และอนุมัติให้ลูกค้าชำระเงิน
        Route::patch(
            '/staff/orders/{order}/review',
            [StaffOrderController::class, 'review']
        )->name('staff.orders.review');


        /*
        |--------------------------------------------------------------------------
        | ตรวจสอบการชำระเงิน
        |--------------------------------------------------------------------------
        */

        // ยืนยันการชำระเงิน
        Route::patch(
            '/staff/orders/{order}/payment/confirm',
            [PaymentController::class, 'confirm']
        )->name('staff.payments.confirm');

        // ปฏิเสธสลิป
        Route::patch(
            '/staff/orders/{order}/payment/reject',
            [PaymentController::class, 'reject']
        )->name('staff.payments.reject');
    });


    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:customer')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard ลูกค้า
        |--------------------------------------------------------------------------
        */

        Route::view(
            '/customer/dashboard',
            'customer.dashboard'
        )->name('customer.dashboard');


        /*
        |--------------------------------------------------------------------------
        | งานของลูกค้า
        |--------------------------------------------------------------------------
        */

        // รายการงานทั้งหมด
        Route::get(
            '/customer/orders',
            [OrderController::class, 'index']
        )->name('customer.orders.index');

        // หน้าสั่งงานใหม่
        Route::get(
            '/customer/orders/create',
            [OrderController::class, 'create']
        )->name('customer.orders.create');

        // บันทึกงานใหม่ + ไฟล์งาน + สลิป
        Route::post(
            '/customer/orders',
            [OrderController::class, 'store']
        )->name('customer.orders.store');


        /*
        |--------------------------------------------------------------------------
        | หน้าชำระเงิน
        |--------------------------------------------------------------------------
        |
        | เก็บ Route นี้ไว้รองรับกรณีต้องดูรายการชำระเงิน
        | หรืออัปโหลดใหม่ภายหลัง
        |
        */

        Route::get(
            '/customer/payments',
            [OrderController::class, 'payments']
        )->name('customer.payments.index');


        /*
        |--------------------------------------------------------------------------
        | อัปโหลดสลิปใหม่
        |--------------------------------------------------------------------------
        |
        | ใช้กรณี Staff ปฏิเสธสลิป
        |
        */

        Route::post(
            '/customer/orders/{order}/payment',
            [PaymentController::class, 'store']
        )->name('customer.payments.store');


        /*
        |--------------------------------------------------------------------------
        | รายละเอียดงาน
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/customer/orders/{order}',
            [OrderController::class, 'show']
        )->name('customer.orders.show');


        /*
        |--------------------------------------------------------------------------
        | การแจ้งเตือน
        |--------------------------------------------------------------------------
        */

        // ดูรายการแจ้งเตือน
        Route::get(
            '/customer/notifications',
            [CustomerNotificationController::class, 'index']
        )->name('customer.notifications.index');

        // อ่านการแจ้งเตือนทั้งหมด
        Route::patch(
            '/customer/notifications/read-all',
            [CustomerNotificationController::class, 'markAllRead']
        )->name('customer.notifications.read-all');

        // อ่านการแจ้งเตือนรายการเดียว
        Route::patch(
            '/customer/notifications/{notification}/read',
            [CustomerNotificationController::class, 'markRead']
        )->name('customer.notifications.read');
    });


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    )->name('logout');
});