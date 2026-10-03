# PrintShop update 2026-10-03

## New required workflow
1. Customer selects print options and uploads job files.
2. Order is created as `pending` and `payment_status=unpaid`. Customer cannot pay yet.
3. Staff opens the uploaded file, verifies paper size / color / sides / actual pages / quantity / unit price.
4. Staff either:
   - Approves: corrected values are saved, total is recalculated, status becomes `waiting_payment`.
   - Rejects: status becomes `cancelled` and a reason is required.
5. Only `waiting_payment` orders can submit payment details/slips.
6. Staff can confirm payment only when payment amount equals the latest approved total.
7. Confirmed payment sets `payment_status=paid` and status=`processing`.
8. Printing page is blocked unless payment is confirmed.

## Error/abuse guards added
- Payment submission before staff approval is rejected server-side.
- Payment confirmation outside `waiting_payment` is rejected.
- Payment amount must match the latest approved total.
- Bank transfer requires an uploaded slip.
- Missing order file prevents staff approval.
- Invalid status jumps are rejected server-side.
- Print page is forbidden until payment is confirmed.
- Upload limits/types and numeric bounds are validated server-side.
- Old unpaid payment/slip records from the previous flow are cleared when staff approves a newly reviewed total.

## Database migration
Run:

```bash
php artisan migrate
```

This adds `review_note`, `reviewed_at`, and `reviewed_by` to `orders`.

## Environment note
The automated container used for this repair has PHP without `mbstring` and without PDO database drivers, so full Laravel `artisan migrate/route:list/view:cache` execution could not run here. All PHP files under app/routes/database were syntax-checked successfully with `php -l`.

On the target server, ensure PHP extensions include at least:
- mbstring
- pdo_mysql (for MySQL) or pdo_sqlite (for SQLite)
- fileinfo
- openssl

Then run:

```bash
composer install
php artisan migrate
php artisan storage:link
php artisan optimize:clear
php artisan route:list
```
