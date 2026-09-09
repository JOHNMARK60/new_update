# KANTO GOODS - Laravel Cashiering and Inventory

KANTO GOODS is a Laravel 13 application for small-store cashiering and inventory operations. It uses Laravel routing, middleware, validation, authentication, Eloquent, migrations, services, Blade views, and CSRF protection throughout.

## Retained features

- Separate administrator and cashier login paths, role authorization, and local password-reset links
- Admin and cashier dashboards with daily/monthly charts and top-selling products
- Product CRUD, images, SKU, categories, suppliers, expiration dates, and low-stock levels
- Category and supplier management
- Product catalog, inventory status summaries, and stock movement logs
- Transaction-safe POS cart with discounts, tax, cash validation, change, stock locking, and receipts
- Daily, weekly, monthly, and yearly reports
- Filters for date, cashier, product, category, and sale status
- Item, payment, cashier-performance, and transaction summaries
- CSV and PDF sales exports
- Cashier closing with auto-filled expected cash, automatic shortage feedback, and history
- Administrator closing notifications and review feedback
- Administrator user and role management
- Existing database and uploaded-image compatibility

## Installation

Requirements: PHP 8.3+, PDO MySQL, MySQL/MariaDB, and Composer 2.

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

On Linux/macOS, use `cp .env.example .env` instead of `copy`.

The default database is `cashieringinventorysystem` with MySQL user `root` and no password. Adjust the `DB_*` values in `.env` when needed.

For Laragon, open `http://localhost/CashieringInventorySystem/`; it redirects to Laravel's public entry point. For a virtual host, set its document root to the `public/` directory.

## Default administrator

- Email: `admin@system.local`
- Password: `admin123`

Change the default password after the first login.

## Verification

```bash
php artisan migrate:status
php artisan route:list
composer test
vendor/bin/pint --test
```

Uploaded product images are stored in `public/assets/uploads/products`.
