# KANTO GOODS Database Normalization and ERD

## Table List

- `roles`: role lookup table. Primary key: `id`. Unique key: `name`.
- `users`: admin and cashier accounts. Primary key: `id`. Foreign key: `role` references `roles.name`.
- `categories`: product category lookup. Primary key: `id`.
- `suppliers`: supplier lookup. Primary key: `id`.
- `products`: sellable inventory items. Primary key: `id`. Foreign keys: `category_id`, `supplier_id`.
- `sales`: transaction header. Primary key: `id`. Foreign keys: `cashier_id`, `user_id`, legacy `product_id`.
- `sale_items`: transaction line items. Primary key: `id`. Foreign keys: `sale_id`, `product_id`.
- `payments`: payment records for sales. Primary key: `id`. Foreign key: `sale_id`.
- `inventory_logs`: inventory movement history. Primary key: `id`. Foreign keys: `product_id`, `created_by`.
- `receipts`: receipt records. Primary key: `id`. Foreign key: `sale_id`.
- `closing_reports`: daily cashier closing records. Primary key: `id`. Foreign keys: `cashier_id`, `closed_by`.
- `admin_notifications`: admin notification records. Primary key: `id`.

## Mermaid ERD

```mermaid
erDiagram
    ROLES ||--o{ USERS : has
    USERS ||--o{ SALES : processes
    USERS ||--o{ INVENTORY_LOGS : records
    USERS ||--o{ CLOSING_REPORTS : cashier
    USERS ||--o{ CLOSING_REPORTS : closed_by
    CATEGORIES ||--o{ PRODUCTS : contains
    SUPPLIERS ||--o{ PRODUCTS : supplies
    PRODUCTS ||--o{ SALE_ITEMS : included_in
    PRODUCTS ||--o{ INVENTORY_LOGS : moves
    SALES ||--o{ SALE_ITEMS : has
    SALES ||--o{ PAYMENTS : paid_by
    SALES ||--o| RECEIPTS : generates

    ROLES {
        int id PK
        varchar name UK
        varchar label
        datetime created_at
    }

    USERS {
        int id PK
        varchar first_name
        varchar last_name
        varchar email UK
        varchar phone
        varchar password
        varchar role FK
        varchar reset_token
        datetime token_expires_at
        datetime created_at
        datetime updated_at
    }

    CATEGORIES {
        int id PK
        varchar name UK
        datetime created_at
    }

    SUPPLIERS {
        int id PK
        varchar name UK
        varchar contact_no
        datetime created_at
    }

    PRODUCTS {
        int id PK
        varchar name
        decimal price
        int quantity
        varchar image_path
        int category_id FK
        int supplier_id FK
        int low_stock_level
        date expiration_date
        varchar sku
        datetime created_at
        datetime updated_at
    }

    SALES {
        int id PK
        varchar receipt_no
        int cashier_id FK
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
        int id PK
        int sale_id FK
        int product_id FK
        varchar product_name
        int quantity
        decimal unit_price
        decimal total_price
        datetime created_at
    }

    PAYMENTS {
        int id PK
        int sale_id FK
        decimal amount
        decimal tendered_amount
        decimal change_amount
        varchar currency
        varchar payment_method
        datetime payment_date
    }

    INVENTORY_LOGS {
        int id PK
        int product_id FK
        varchar action
        int quantity_change
        int stock_before
        int stock_after
        varchar reference_type
        int reference_id
        int created_by FK
        datetime created_at
    }

    RECEIPTS {
        int id PK
        int sale_id FK
        varchar receipt_no UK
        json receipt_data
        datetime printed_at
        datetime created_at
    }

    CLOSING_REPORTS {
        int id PK
        date closing_date
        int cashier_id FK
        varchar cashier_name
        int total_transactions
        int total_items_sold
        decimal total_sales
        decimal total_cash_received
        decimal expected_cash_amount
        decimal actual_cash_amount
        decimal difference_amount
        datetime closing_time
        int closed_by FK
        varchar status
        varchar notes
    }
```

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
