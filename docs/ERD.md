# KANTO GOODS ERD

This ERD matches the normalized database used by the current native PHP system and the safe migration in `database/normalize_3nf_migration.sql`.

## Mermaid ERD

```mermaid
erDiagram
    ROLES ||--o{ USERS : assigns
    USERS ||--o{ SALES : processes
    USERS ||--o{ INVENTORY_LOGS : records
    USERS ||--o{ CLOSING_REPORTS : cashier
    USERS ||--o{ CLOSING_REPORTS : closes
    USERS ||--o{ CLOSING_REPORTS : reviews
    CATEGORIES ||--o{ PRODUCTS : contains
    SUPPLIERS ||--o{ PRODUCTS : supplies
    PRODUCTS ||--o{ SALE_ITEMS : sold_as
    PRODUCTS ||--o{ INVENTORY_LOGS : tracks
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
        int role_id FK
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

    RECEIPTS {
        int id PK
        int sale_id FK
        varchar receipt_no UK
        json receipt_data
        datetime printed_at
        datetime created_at
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
        varchar review_status
        varchar admin_feedback
        int reviewed_by FK
        datetime reviewed_at
    }

    ADMIN_NOTIFICATIONS {
        int id PK
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

## Table List

- `roles`: master list for user roles. Primary key: `id`. Unique key: `name`.
- `users`: admin and cashier accounts. Primary key: `id`. Foreign keys: `role_id` to `roles.id`, legacy-compatible `role` to `roles.name`.
- `categories`: master list for product categories. Primary key: `id`.
- `suppliers`: master list for suppliers. Primary key: `id`.
- `products`: current inventory items and stock count. Primary key: `id`. Foreign keys: `category_id`, `supplier_id`.
- `sales`: transaction header. Primary key: `id`. Foreign keys: `cashier_id`, `user_id`, and legacy `product_id`.
- `sale_items`: transaction line items. Primary key: `id`. Foreign keys: `sale_id`, `product_id`.
- `payments`: payment records in Philippine Peso. Primary key: `id`. Foreign key: `sale_id`.
- `receipts`: generated receipt records. Primary key: `id`. Foreign key: `sale_id`.
- `inventory_logs`: stock movement history. Primary key: `id`. Foreign keys: `product_id`, `created_by`.
- `closing_reports`: cashier daily closing records. Primary key: `id`. Foreign keys: `cashier_id`, `closed_by`, `reviewed_by`.
- `admin_notifications`: admin alert records. Primary key: `id`. It uses `related_type` and `related_id` as a generic pointer, so no direct FK is enforced.

## Cardinality

- One role can be assigned to many users.
- One cashier user can process many sales.
- One category can contain many products.
- One supplier can supply many products.
- One sale can contain many sale items.
- One product can appear in many sale items.
- One sale can have one or more payment records.
- One sale should generate one receipt record.
- One product can have many inventory log entries.
- One user can create many inventory log entries.
- One cashier can have many closing reports.
