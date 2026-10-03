PrintShop Unified Layout

ปรับให้หน้า Admin / Staff / Customer ใช้โครง Sidebar เดียวกัน แต่เมนูและข้อมูลแยกตามบทบาทและหน้าที่
- Admin: ภาพรวมร้าน / บริการ / ผู้ใช้งาน / รายงาน
- Staff: คิวงานหน้าร้าน / รายละเอียดงาน / พิมพ์งาน / ดูสลิป
- Customer: หน้าหลัก / สั่งงานใหม่ / งานของฉัน / การแจ้งเตือน
- Login/Register ใช้ธีมเดียวกันโดยไม่แสดง Sidebar

ไฟล์ CSS กลาง: public/css/portal-shell.css
Layout: resources/views/layouts/admin.blade.php, staff.blade.php, customer.blade.php
