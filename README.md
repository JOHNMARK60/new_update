# KANTO GOODS Cashiering and Inventory System

KANTO GOODS is a native PHP cashiering and inventory management system for small-store operations. It supports role-based admin and cashier workspaces, product inventory, POS sales, receipt printing, sales reports, inventory logs, and end-of-day closing validation.

## OOP Assessment

Yes, this system follows OOP principles in its core application layer. It is best described as:

```text
Native PHP pages + OOP service/repository/model layer
```

The page files in `admin/`, `auth/`, `cashier/`, and `user/` still handle routing, forms, and HTML output in a procedural PHP style. The main business logic is separated into OOP classes under `app/`.

OOP principles used:

- **Encapsulation**: `Database`, repositories, services, and models keep database access and business rules inside focused classes.
- **Inheritance**: models extend `AbstractModel`; repositories extend `BaseRepository`; report types extend `Report`; `Admin` and `Cashier` extend `User`.
- **Abstraction**: contracts and abstract base classes define shared behavior without exposing each implementation detail.
- **Polymorphism**: reports, payments, repositories, and receipts can be handled through shared interfaces or parent types.

More detail is available in [docs/OOP_REVIEW.md](docs/OOP_REVIEW.md).

## Main Features

### Public Landing and Login

- Landing page with system overview and feature highlights.
- Separate cashier and administrator login paths.
- Session-based authentication.
- Role-based redirection after login.
- Password reset flow using generated testing-mode reset links.
- Public registration is disabled; accounts are created by an administrator.

### Administrator Workspace

- Admin dashboard with product count, cashier count, daily sales, low-stock count, daily charts, monthly charts, and top-selling products.
- Product inventory management:
  - Add, update, view, and delete products.
  - Upload product images.
  - Assign categories, suppliers, SKU, expiration date, price, stock, and low-stock level.
  - Search products by name, category, supplier, or SKU.
- Category management from the inventory page.
- Inventory movement logs for product creation, updates, and sales stock deductions.
- User management:
  - Create admin or cashier accounts.
  - Edit account details.
  - Reset account password.
  - Delete accounts except the currently logged-in admin account.
- Sales reports:
  - Daily, weekly, monthly, and yearly report views.
  - Filter by date, cashier, product, category, and status.
  - View transaction totals, item summaries, payment summaries, cashier performance, and transaction history.
  - Export daily sales as CSV or PDF.
- End-of-day closing:
  - View open paid sales by cashier and date.
  - Close a cashier day by entering actual cash.
  - Compare actual cash against expected sales.
  - Store closing reports with balanced, over, or short status.
  - Generate automatic feedback for cash shortages.
- Role separation page explaining admin and cashier access.
- Admin notifications for cashier-submitted closing reports.

### Cashier Workspace

- Cashier dashboard with today sales, items sold, low-stock count, available products, sales charts, and top-selling items.
- POS screen:
  - Search products by name, SKU, or barcode text.
  - Filter products by category.
  - Add products to a cart.
  - Edit quantities and remove items.
  - Apply discount and tax.
  - Accept cash tendered amount.
  - Calculate total and change.
  - Complete sale and generate receipt.
- Receipt screen:
  - Shows official receipt details.
  - Prints receipt using receipt-friendly print layout.
- Product catalog:
  - Browse product images, prices, stock status, category, and SKU.
  - Search and filter products.
- Stock page:
  - View total catalog, low-stock count, out-of-stock count.
  - View stock status and low-stock level per product.
- Cashier reports:
  - View own sales today and total revenue.
  - View recent transactions.
  - View top products chart.
- Cashier closing:
  - Close own daily sales.
  - Actual cash is auto-filled from expected sales.
  - View own closing history and feedback.

## How the System Works

### 1. Application Bootstrapping

Every authenticated page loads `config/auth.php`, which loads `config/db.php`. The database config loads `app/bootstrap.php`, registers the `App\` autoloader, runs database migration checks, and creates reusable `$pdo` and `$conn` database handles.

Important files:

- `app/bootstrap.php`: autoloads classes under the `App\` namespace.
- `config/db.php`: initializes database migration and connection variables.
- `config/auth.php`: starts secure sessions and defines authentication helpers.
- `config/app_head.php`, `config/app_header.php`, `config/app_footer.php`: shared UI layout pieces.

### 2. Database Initialization

`App\Core\Database::migrate()` creates or updates the database tables automatically when the system loads.

Default database settings:

```text
DB_HOST = localhost
DB_USER = root
DB_PASS =
DB_NAME = cashieringinventorysystem
```

These can be overridden with environment variables.

Main tables:

- `roles`
- `users`
- `categories`
- `suppliers`
- `products`
- `sales`
- `sale_items`
- `payments`
- `receipts`
- `inventory_logs`
- `closing_reports`
- `admin_notifications`

The system also seeds starter roles, categories, sample products, and a default administrator account if no admin account exists.

Default admin account:

```text
Email: admin@system.local
Password: admin123
```

### 3. Authentication and Roles

Users log in through `auth/login_process.php`. The login process validates email, password, selected role, and account status. After successful login:

- Admin users are redirected to `admin/dashboard.php`, which forwards to `admin/admin_dashboard.php`.
- Cashier users are redirected to `cashier/dashboard.php`, which forwards to `user/user_dashboard.php`.

Role protection is handled by:

- `require_login()`
- `require_role('admin')`
- `require_role('cashier')`

The `Permission` service also defines role capabilities, with admins receiving full access and cashiers receiving POS, receipt, own reports, product viewing, and own closing access.

### 4. Product and Inventory Flow

Admins manage products from `admin/admin_inventory.php`.

When a product is added or updated:

1. The page validates request data.
2. `ProductRepository` creates or updates the product.
3. Categories and suppliers are created if needed.
4. Product images are uploaded to `assets/uploads/products/`.
5. Stock changes are written to `inventory_logs`.
6. Low-stock products trigger a warning toast.

Cashiers can browse products and stock but do not manage product records.

### 5. POS Sales Flow

Cashiers process sales from `user/cashier_sales.php`.

The sale flow:

1. Cashier selects products and quantities.
2. The browser builds a cart and computes subtotal, discount, tax, total, tendered amount, and change.
3. The form posts to `user/cashier_receipt.php`.
4. A `Payment` model validates the cash amount.
5. `SaleRepository::createSale()` starts a database transaction.
6. Product rows are locked with `FOR UPDATE`.
7. Stock availability is checked.
8. The sale is inserted into `sales`.
9. Line items are inserted into `sale_items`.
10. Product stock is reduced.
11. Stock deductions are logged in `inventory_logs`.
12. Payment details are inserted into `payments`.
13. A receipt number is inserted into `receipts`.
14. The transaction commits.
15. `Receipt` and `ReceiptPrinter` render the official receipt.

If stock changes during checkout or payment is insufficient, the sale is rejected and the transaction is rolled back.

### 6. Receipt Printing

Receipt generation uses:

- `App\Services\Receipt`
- `App\Services\ReceiptPrinter`
- `App\Contracts\PrintableReceiptInterface`

The receipt includes store details, receipt number, sale date and time, cashier, item lines, subtotal, discount, tax, total, tendered amount, and change.

### 7. Reporting Flow

Reports are generated through classes under `app/Reports/`.

Report classes:

- `DailyReport`
- `WeeklyReport`
- `MonthlyReport`
- `YearlyReport`

Each class extends the abstract `Report` class and defines its own date range. The shared report generation process returns:

- Summary totals.
- Item sales summary.
- Payment summary.
- Cashier performance.
- Transaction list.

Admins can view reports for all cashiers. Cashiers can view only their own sales reports.

### 8. Closing Validation Flow

End-of-day closing is handled by `App\Services\ClosingValidation`.

Cashier closing:

1. Cashier opens `user/cashier_closing.php`.
2. The system calculates expected cash from open paid sales for the selected date.
3. Cashier submits actual cash and optional notes.
4. A closing report is created.
5. Related sales are marked as closed.
6. Admin notification is created.

Admin closing:

1. Admin opens `admin/closing_validation.php`.
2. Admin selects date, cashier, actual cash, and notes.
3. The system saves the closing report and closes matching sales.

Closing status is based on cash difference:

- `balanced`: actual cash equals expected cash.
- `over`: actual cash is greater than expected cash.
- `short`: actual cash is less than expected cash.

## Project Structure

```text
admin/          Administrator pages and workflows
app/            OOP application layer
auth/           Login, logout, and password reset pages
cashier/        Cashier route redirect
config/         Shared database, auth, layout, and helper files
database/       SQL migrations, ERD, seed data, and normalization scripts
docs/           Documentation and OOP/database explanations
user/           Cashier dashboard, POS, reports, products, stock, and closing
assets/         CSS, JavaScript, images, and product uploads
```

Important OOP folders:

```text
app/Core/           Database, base model, base repository, base controller
app/Contracts/      Interfaces for repositories, reports, payments, receipts
app/Models/         User, Admin, Cashier, Product, Sale, SaleItem, Payment, Role
app/Repositories/   Product, Sale, and User database operations
app/Reports/        Daily, weekly, monthly, and yearly sales reports
app/Services/       Auth, inventory, receipts, closing, permissions, notifications
app/Support/        Shared support utilities such as money formatting
```

## Setup

1. Install PHP 8.0 or newer with PDO MySQL enabled.
2. Install MySQL or MariaDB.
3. Place the project in a local web server folder, for example:

```text
C:\laragon\www\CashieringInventorySystem
```

4. Start Apache/Nginx and MySQL in Laragon or your local server.
5. Open the project in the browser:

```text
http://localhost/CashieringInventorySystem/
```

6. Log in with the default admin account:

```text
Email: admin@system.local
Password: admin123
```

7. Create cashier accounts from `Admin > Users`.

## ERD Transfer Safety

The ERD table structure will not be ruined when the project is transferred to another laptop. The database schema is recreated by `App\Core\Database::migrate()` and the SQL files in `database/`.

There are two ERD parts to preserve:

- **Relationships and table structure**: preserved by the migration/database schema.
- **phpMyAdmin Designer visual arrangement**: preserved by importing `database/phpmyadmin_designer_layout.sql`.

To keep the same ERD arrangement on another laptop:

1. Copy or clone the project.
2. Start Apache/Nginx and MySQL.
3. Open the system once in the browser so the database and tables are created.
4. If you need the same records, export the old MySQL database and import it on the new laptop.
5. In phpMyAdmin, make sure phpMyAdmin configuration storage is enabled.
6. Import this file:

```text
database/phpmyadmin_designer_layout.sql
```

Important: keep the database name as `cashieringinventorysystem`. If the database is renamed, update the database name inside `database/phpmyadmin_designer_layout.sql` before importing it.

## External Frontend Dependencies

The UI loads some frontend libraries from CDNs:

- Tailwind CSS CDN
- Font Awesome
- Google Fonts
- SweetAlert2
- Chart.js on dashboard/report pages

Internet access is useful for full styling, icons, alerts, and charts while developing locally.

## Notes for Maintenance

- Keep business rules in `app/Services/` or `app/Repositories/` when adding features.
- Keep database operations in repositories when possible.
- Use models for structured domain data such as payment and receipt data.
- Keep direct page files focused on request handling and rendering.
- Avoid duplicating SQL in many pages if a repository method can handle it.
- Uploaded product images are stored in `assets/uploads/products/`.
- Database schema updates should be added to `App\Core\Database::migrate()` or SQL files under `database/`.
