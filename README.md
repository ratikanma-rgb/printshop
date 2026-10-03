# PrintShop Management System

ระบบจัดการร้านรับพิมพ์งานและถ่ายเอกสารออนไลน์ พัฒนาด้วย Laravel 12 สำหรับบริหารคำสั่งงาน ไฟล์เอกสาร การชำระเงิน คิวงาน ผู้ใช้งาน บริการ และรายงานภายในระบบเดียว

## ความสามารถหลัก

- Authentication: สมัครสมาชิก เข้าสู่ระบบ ออกจากระบบ
- Authorization: แบ่งสิทธิ์ `admin`, `staff`, `customer` และควบคุมด้วย Role Middleware
- Customer: สร้างคำสั่งงาน อัปโหลดไฟล์ ติดตามสถานะ อัปโหลดหลักฐานการชำระเงิน และดูการแจ้งเตือน
- Staff: ตรวจสอบคำสั่งงาน เปิดไฟล์ สั่งพิมพ์/พิมพ์ใบงาน เปลี่ยนสถานะ และตรวจสอบ/ยืนยัน/ปฏิเสธหลักฐานการชำระเงิน
- Admin: Dashboard, CRUD บริการ, จัดการ Role ผู้ใช้งาน และรายงานภาพรวม
- Database Relationships: User → Orders → OrderItems / OrderFiles / Payment และ Service → OrderItems
- File Management: จัดเก็บไฟล์งานและสลิปใน `storage/app/public`; เมื่อลูกค้าอัปโหลดสลิปใหม่ ระบบแทนที่ข้อมูลอย่างปลอดภัยและลบไฟล์เดิมหลังบันทึกสำเร็จ
- Notification + Queue: แจ้งลูกค้าเมื่อสถานะงานหรือผลการตรวจสอบการชำระเงินเปลี่ยน โดยใช้ Database Notification และ Laravel Queue
- Automated Tests: Feature Tests สำหรับสิทธิ์การเข้าถึง การสร้างคำสั่งงาน และ workflow การแจ้งเตือน

## เทคโนโลยี

- PHP 8.2+
- Laravel 12
- MySQL / MariaDB
- Blade
- Laravel Database Queue
- PHPUnit 11


## เปิดระบบแบบคลิกเดียว (Windows)

หลังตั้งค่าครั้งแรก สามารถดับเบิลคลิกไฟล์ `START_PRINTSHOP.bat` ที่โฟลเดอร์โปรเจกต์ได้เลย ระบบจะ:

1. เปิด MySQL จาก XAMPP ให้อัตโนมัติถ้ายังไม่ทำงาน (ไม่ต้องเปิด XAMPP Control Panel)
2. ตรวจ Migration และล้าง Cache ที่จำเป็น
3. เปิด Laravel Server ที่ `http://127.0.0.1:8000`
4. เปิด Queue Worker สำหรับ Notification
5. เปิดหน้า Login ใน Browser อัตโนมัติ

สำหรับเครื่องใหม่ ให้รัน `SETUP_PRINTSHOP.bat` หนึ่งครั้งเพื่อเตรียม dependencies, database, seed และ storage link ก่อน

## ติดตั้งบน Windows + XAMPP

```powershell
cd C:\xampp\htdocs\printshop
composer install
copy .env.example .env
php artisan key:generate
```

แก้ `.env` ให้ตรงกับฐานข้อมูล เช่น:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=printshop_db
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
```

จากนั้นสร้างฐานข้อมูล `printshop_db` ใน phpMyAdmin แล้วรัน:

```powershell
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
```

เปิด Web Server:

```powershell
php artisan serve
```

เปิด Queue Worker ใน PowerShell อีกหน้าต่างหนึ่งและปล่อยให้ทำงานตลอดเวลาที่ทดสอบระบบ:

```powershell
cd C:\xampp\htdocs\printshop
php artisan queue:work --tries=3
```

เว็บไซต์สำหรับ Local Development:

```text
http://127.0.0.1:8000
```

## บัญชีสำหรับทดสอบหลัง `php artisan migrate --seed`

| สิทธิ์ | Email | Password |
|---|---|---|
| Admin | admin@printshop.local | Printshop123! |
| Staff | staff@printshop.local | Printshop123! |
| Customer | customer@printshop.local | Printshop123! |

> บัญชีข้างต้นใช้สำหรับการสาธิต/ทดสอบเท่านั้น ต้องเปลี่ยนรหัสผ่านก่อนนำระบบขึ้น Production

## Workflow ของระบบ

1. Customer สมัคร/เข้าสู่ระบบและสร้างคำสั่งงานพร้อมแนบไฟล์
2. งานเริ่มสถานะ `pending`
3. Staff ตรวจสอบและเปลี่ยนเป็น `waiting_payment`
4. Queue สร้าง Notification แจ้ง Customer ให้ชำระเงิน
5. Customer อัปโหลดสลิป
6. Staff ยืนยันสลิป → ระบบเปลี่ยนเป็น `processing` และแจ้ง Customer
7. Staff เปลี่ยน `processing` → `ready`
8. เมื่อลูกค้ารับงานแล้ว Staff เปลี่ยน `ready` → `completed`
9. Admin ดูสรุปงาน รายรับ บริการ และจัดการสิทธิ์ผู้ใช้งาน

## ทดสอบระบบอัตโนมัติ

ระบบ Test ใช้ฐานข้อมูล MySQL แยกชื่อ `printshop_test` เพื่อไม่แตะข้อมูลจริงใน `printshop_db` (ไฟล์ Setup/Start จะสร้างฐานข้อมูลนี้ให้อัตโนมัติ):

```powershell
php artisan test
```

หรือ:

```powershell
vendor\bin\phpunit
```

## คำสั่งตรวจสอบก่อนส่งงาน

```powershell
php artisan route:list
php artisan test
php artisan migrate:status
php artisan queue:failed
php artisan optimize:clear
```

## Production Checklist

ตั้งค่า `.env` บน Production อย่างน้อย:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
QUEUE_CONNECTION=database
```

จากนั้นรัน:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Queue Worker ต้องทำงานต่อเนื่องบน Production (เช่น Supervisor/systemd หรือ process manager ของผู้ให้บริการ) และควรตั้ง permission ให้ `storage` และ `bootstrap/cache` เขียนได้

## Security Notes

- ทุก Route สำคัญอยู่หลัง `auth` และ `role` middleware
- Customer เปิดดูเฉพาะ Order ของตัวเอง
- Customer ไม่สามารถชำระเงินให้ Order ของผู้ใช้อื่น
- Admin ไม่สามารถเปลี่ยน Role ของบัญชีตัวเองจากหน้าจัดการผู้ใช้ เพื่อลดความเสี่ยงล็อกตัวเองออกจากระบบ
- Validate ชนิด/ขนาดไฟล์ก่อนจัดเก็บ
- ใช้ CSRF protection กับ Form ที่แก้ไขข้อมูล
- การเปลี่ยนสถานะงานถูกจำกัดตาม Workflow เพื่อรักษาความถูกต้องของข้อมูล

## โครงสร้างข้อมูลสำคัญ

- `users`
- `services`
- `orders`
- `order_items`
- `order_files`
- `payments`
- `notifications`
- `jobs`, `failed_jobs`, `job_batches`

## หมายเหตุสำหรับ GitHub

ไม่ควร Commit `.env`, ไฟล์ใน `storage` ที่เป็นข้อมูลลูกค้าจริง หรือ Credential/Secret ใด ๆ ลง Public Repository ให้ใช้ `.env.example` เป็นตัวอย่างการตั้งค่าแทน
