# KANTO GOODS Database Normalization and ERD

## Table List

- `roles`: role lookup table. Primary key: `id`. Unique key: `name`.
- `users`: admin and cashier accounts. Primary key: `id`. Foreign keys: `role_id` references `roles.id`, and legacy-compatible `role` references `roles.name`.
- `categories`: product category lookup. Primary key: `id`.
- `suppliers`: supplier lookup. Primary key: `id`.
- `products`: sellable inventory items. Primary key: `id`. Foreign keys: `category_id`, `supplier_id`.
- `sales`: transaction header. Primary key: `id`. Foreign keys: `cashier_id`, `user_id`, legacy `product_id`.
- `sale_items`: transaction line items. Primary key: `id`. Foreign keys: `sale_id`, `product_id`.
- `payments`: payment records for sales. Primary key: `id`. Foreign key: `sale_id`.
- `inventory_logs`: inventory movement history. Primary key: `id`. Foreign keys: `product_id`, `created_by`.
- `receipts`: receipt records. Primary key: `id`. Foreign key: `sale_id`.
- `closing_reports`: daily cashier closing records. Primary key: `id`. Foreign keys: `cashier_id`, `closed_by`, `reviewed_by`.
- `admin_notifications`: admin notification records. Primary key: `id`. It uses `related_type` and `related_id` as a generic pointer, so no direct FK is enforced.

## Mermaid ERD

Legend:

- `PK` means Primary Key.
- `FK` means Foreign Key.
- `UK` means Unique Key.
- The quoted text after an FK shows which table and column it references.

```mermaid
erDiagram
    ROLES ||--o{ USERS : role_id
    ROLES ||--o{ USERS : role
    USERS ||--o{ SALES : cashier_id
    USERS ||--o{ SALES : user_id
    USERS ||--o{ INVENTORY_LOGS : created_by
    USERS ||--o{ CLOSING_REPORTS : cashier_id
    USERS ||--o{ CLOSING_REPORTS : closed_by
    USERS ||--o{ CLOSING_REPORTS : reviewed_by
    CATEGORIES ||--o{ PRODUCTS : category_id
    SUPPLIERS ||--o{ PRODUCTS : supplier_id
    PRODUCTS ||--o{ SALES : product_id
    PRODUCTS ||--o{ SALE_ITEMS : product_id
    PRODUCTS ||--o{ INVENTORY_LOGS : product_id
    SALES ||--o{ SALE_ITEMS : sale_id
    SALES ||--o{ PAYMENTS : sale_id
    SALES ||--o| RECEIPTS : sale_id

    ROLES {
        int id PK "primary key"
        varchar name UK "unique role name"
        varchar label
        datetime created_at
    }

    USERS {
        int id PK "primary key"
        varchar first_name
        varchar last_name
        varchar email UK "unique login email"
        varchar phone
        varchar password
        varchar role FK "roles.name"
        int role_id FK "roles.id"
        varchar reset_token
        datetime token_expires_at
        datetime created_at
        datetime updated_at
    }

    CATEGORIES {
        int id PK "primary key"
        varchar name UK "unique category name"
        datetime created_at
    }

    SUPPLIERS {
        int id PK "primary key"
        varchar name UK "unique supplier name"
        varchar contact_no
        datetime created_at
    }

    PRODUCTS {
        int id PK "primary key"
        varchar name
        decimal price
        int quantity
        varchar image_path
        int category_id FK "categories.id"
        int supplier_id FK "suppliers.id"
        int low_stock_level
        date expiration_date
        varchar sku
        datetime created_at
        datetime updated_at
    }

    SALES {
        int id PK "primary key"
        varchar receipt_no
        int product_id FK "products.id legacy"
        int quantity
        decimal total_price
        int user_id FK "users.id legacy"
        int cashier_id FK "users.id"
        varchar cashier_name
        decimal subtotal_amount
        decimal discount
        decimal tax
        decimal total_amount
        decimal tendered_amount
        decimal change_amount
        varchar payment_method
        varchar status
        varchar closing_status
        datetime sale_date
        datetime closed_at
    }

    SALE_ITEMS {
        int id PK "primary key"
        int sale_id FK "sales.id"
        int product_id FK "products.id"
        varchar product_name
        int quantity
        decimal unit_price
        decimal total_price
        datetime created_at
    }

    PAYMENTS {
        int id PK "primary key"
        int sale_id FK "sales.id"
        decimal amount
        decimal tendered_amount
        decimal change_amount
        varchar currency
        varchar payment_method
        datetime payment_date
    }

    INVENTORY_LOGS {
        int id PK "primary key"
        int product_id FK "products.id"
        varchar action
        int quantity_change
        int stock_before
        int stock_after
        varchar reference_type
        int reference_id
        int created_by FK "users.id"
        datetime created_at
    }

    RECEIPTS {
        int id PK "primary key"
        int sale_id FK "sales.id"
        varchar receipt_no UK "unique receipt number"
        json receipt_data
        datetime printed_at
        datetime created_at
    }

    CLOSING_REPORTS {
        int id PK "primary key"
        date closing_date
        int cashier_id FK "users.id"
        varchar cashier_name
        int total_transactions
        int total_items_sold
        decimal total_sales
        decimal total_cash_received
        decimal expected_cash_amount
        decimal actual_cash_amount
        decimal difference_amount
        datetime closing_time
        int closed_by FK "users.id"
        varchar status
        varchar notes
        varchar review_status
        varchar admin_feedback
        int reviewed_by FK "users.id"
        datetime reviewed_at
    }

    ADMIN_NOTIFICATIONS {
        int id PK "primary key"
        varchar type
        varchar title
        varchar body
        varchar link_url
        varchar related_type
        int related_id
        datetime read_at
        datetime created_at
    }
```

## PK and FK Reference

| Table | Primary Key | Foreign Keys |
| --- | --- | --- |
| `roles` | `id` | None |
| `users` | `id` | `role_id` -> `roles.id`; `role` -> `roles.name` |
| `categories` | `id` | None |
| `suppliers` | `id` | None |
| `products` | `id` | `category_id` -> `categories.id`; `supplier_id` -> `suppliers.id` |
| `sales` | `id` | `cashier_id` -> `users.id`; `user_id` -> `users.id`; `product_id` -> `products.id` |
| `sale_items` | `id` | `sale_id` -> `sales.id`; `product_id` -> `products.id` |
| `payments` | `id` | `sale_id` -> `sales.id` |
| `receipts` | `id` | `sale_id` -> `sales.id` |
| `inventory_logs` | `id` | `product_id` -> `products.id`; `created_by` -> `users.id` |
| `closing_reports` | `id` | `cashier_id` -> `users.id`; `closed_by` -> `users.id`; `reviewed_by` -> `users.id` |
| `admin_notifications` | `id` | None. `related_type` and `related_id` are a generic pointer, not an enforced FK. |

## Transfer Safety

The ERD structure is safe to transfer to another laptop because the table definitions and foreign keys live in the migration/database files. The phpMyAdmin Designer box arrangement is stored separately in `database/phpmyadmin_designer_layout.sql`.

After moving the project, open the system once to create the database, then import `database/phpmyadmin_designer_layout.sql` into phpMyAdmin. Keep the database name as `cashieringinventorysystem`, or edit that SQL file to match the new database name before importing.

## Cardinality

- One role can have many users.
- One cashier user can process many sales.
- One category can contain many products.
- One supplier can supply many products.
- One sale can have many sale items.
- One product can appear in many sale items.
- One sale can have one or more payment records.
- One sale generates one receipt record.
- One product can have many inventory log entries.
- One cashier can have many daily closing reports.

## Student-Friendly Normalization Explanation

### 1NF

Each table stores atomic values. Products, users, sales, payments, and receipts do not store lists inside one column. A sale with multiple products uses multiple rows in `sale_items` instead of putting many products into one `sales` column.

### 2NF

Each non-key column depends on the whole primary key of its table. Product details stay in `products`, user details stay in `users`, and each sale item row stores the product, quantity, unit price, and line total for that specific sale line.

### 3NF

Lookup data was separated to avoid repeated text. Roles are stored in `roles`, product categories are stored in `categories`, and suppliers are stored in `suppliers`. Products reference those tables by foreign keys. Payments are separated from sales so payment details depend on the payment record, not on product rows.

### Historical Snapshots

`sales.cashier_name` and `sale_items.product_name` are intentionally kept as receipt snapshots. They preserve the name printed at transaction time even if the user or product name changes later. The live relationship still uses foreign keys such as `sales.cashier_id` and `sale_items.product_id`.

### Why The ERD Matches The Implementation

The ERD follows the current PHP schema in `app/Core/Database.php` plus the safe migration in `database/migrations/2026_05_17_kanto_goods_3nf.sql`. It uses the same table names, primary keys, foreign keys, and transaction flow used by the POS, receipts, reports, inventory deduction, and daily closing modules.
