# KANTO GOODS Normalization Explanation

## What Was Normalized

The database was organized around separate master, transaction, and history tables:

- User roles are stored in `roles`, while accounts are stored in `users`.
- Product categories are stored in `categories`, suppliers in `suppliers`, and items in `products`.
- Sales are split into `sales` for the transaction header and `sale_items` for the products sold.
- Payment details are stored in `payments`, and receipt details are stored in `receipts`.
- Stock movement history is stored in `inventory_logs`.
- Cashier closing records are stored in `closing_reports`.

## 1NF

First Normal Form means each field contains one value only, and there are no repeating groups.

In this system, one sale with many products is not stored as a list inside the `sales` table. Instead, each product sold is stored as a separate row in `sale_items`. Product category, supplier, cashier, and payment values are also stored in proper fields instead of mixed text lists.

## 2NF

Second Normal Form means every non-key column depends on the full primary key of its own table.

For example, product name, price, stock, category, and supplier belong in `products` because they describe a product. Sale total, tendered amount, change, and sale date belong in `sales` because they describe the whole transaction. Quantity, unit price, and line total belong in `sale_items` because they describe one product line inside one sale.

## 3NF

Third Normal Form means non-key columns should not depend on other non-key columns. Related information is separated into lookup or child tables.

The role name is managed by `roles`, while `users.role_id` points to that role. Category names are managed by `categories`, while `products.category_id` points to the category. Supplier names are managed by `suppliers`, while `products.supplier_id` points to the supplier. This avoids repeatedly typing the same role, category, and supplier names in many rows.

## Why `sale_items` Is Separate From `sales`

The `sales` table represents one receipt or transaction. A single transaction can contain many products, so the products cannot be stored cleanly in only one row of `sales`. The `sale_items` table stores each product line separately with its quantity, unit price, and total price. This makes reports and receipts more accurate.

## Why `cashier_id` Is Better Than `cashier_name`

The cashier is a user account, so the correct relationship is `sales.cashier_id` referencing `users.id`. This avoids duplicate cashier names and keeps reports connected to the real account. `cashier_name` is kept only as a receipt snapshot so old receipts still show the name printed during the sale.

## Why `category_id` And `supplier_id` Are Foreign Keys

Categories and suppliers are repeated across many products. Storing them in separate tables avoids duplicate spelling and keeps product data cleaner. Products only need to store `category_id` and `supplier_id`, then the system can join those tables when displaying product details.

## Why `inventory_logs` Is Used

The `products.quantity` column shows the current stock. The `inventory_logs` table explains why the stock changed. It records stock in, stock out, adjustments, and sale deductions, including the user who created the movement and the before/after stock values.

## Legacy Compatibility Columns

Some older columns are kept to avoid breaking the existing PHP pages and reports:

- `users.role` remains for current login/session checks while `users.role_id` is added for normalized role relationships.
- `sales.product_id`, `sales.quantity`, and `sales.total_price` remain for old records, while new multi-item sales use `sale_items`.
- `sales.cashier_name`, `closing_reports.cashier_name`, and `sale_items.product_name` remain as historical snapshots for receipts and reports.

These columns are documented as compatibility fields, not as the main normalized design.

## Why The ERD Matches The Implementation

The ERD uses the same tables, primary keys, foreign keys, and relationships created by `app/Core/Database.php` and strengthened by `database/normalize_3nf_migration.sql`. The POS checkout still saves the sale header in `sales`, product lines in `sale_items`, payment in `payments`, receipt in `receipts`, and stock deductions in `inventory_logs`.
