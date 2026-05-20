-- KANTO GOODS 3NF / ERD compliance migration
-- Backup reminder:
--   In phpMyAdmin, export the full `cashieringinventorysystem` database before running this file.
--   This migration is intentionally non-destructive. It does not drop legacy compatibility columns.

SET @kg_database_name = DATABASE();

INSERT IGNORE INTO roles (name, label)
VALUES ('admin', 'Administrator'), ('cashier', 'Cashier');

DELIMITER $$

DROP PROCEDURE IF EXISTS kg_add_column $$
CREATE PROCEDURE kg_add_column(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_sql TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
        AND table_name = p_table_name
        AND column_name = p_column_name
    ) THEN
        SET @kg_sql = p_column_sql;
        PREPARE kg_stmt FROM @kg_sql;
        EXECUTE kg_stmt;
        DEALLOCATE PREPARE kg_stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS kg_add_index $$
CREATE PROCEDURE kg_add_index(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_sql TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
        AND table_name = p_table_name
        AND index_name = p_index_name
    ) THEN
        SET @kg_sql = p_index_sql;
        PREPARE kg_stmt FROM @kg_sql;
        EXECUTE kg_stmt;
        DEALLOCATE PREPARE kg_stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS kg_add_fk_for_column $$
CREATE PROCEDURE kg_add_fk_for_column(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_constraint_name VARCHAR(64),
    IN p_constraint_sql TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.key_column_usage
        WHERE table_schema = DATABASE()
        AND table_name = p_table_name
        AND column_name = p_column_name
        AND referenced_table_name IS NOT NULL
    ) THEN
        SET @kg_sql = p_constraint_sql;
        PREPARE kg_stmt FROM @kg_sql;
        EXECUTE kg_stmt;
        DEALLOCATE PREPARE kg_stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS kg_add_receipt_sale_unique $$
CREATE PROCEDURE kg_add_receipt_sale_unique()
BEGIN
    DECLARE v_duplicate_sales INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_duplicate_sales
    FROM (
        SELECT sale_id
        FROM receipts
        GROUP BY sale_id
        HAVING COUNT(*) > 1
    ) duplicate_receipts;

    IF v_duplicate_sales = 0 AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
        AND table_name = 'receipts'
        AND index_name = 'uniq_receipts_sale_id'
    ) THEN
        ALTER TABLE receipts ADD UNIQUE KEY uniq_receipts_sale_id (sale_id);
    END IF;
END $$

DELIMITER ;

-- A. Users and roles: keep users.role for existing PHP compatibility, add normalized role_id.
CALL kg_add_column('users', 'role_id', 'ALTER TABLE users ADD COLUMN role_id INT NULL AFTER role');

-- B. Product, sales, inventory, and closing columns expected by the normalized ERD.
CALL kg_add_column('products', 'category_id', 'ALTER TABLE products ADD COLUMN category_id INT NULL');
CALL kg_add_column('products', 'supplier_id', 'ALTER TABLE products ADD COLUMN supplier_id INT NULL');
CALL kg_add_column('products', 'low_stock_level', 'ALTER TABLE products ADD COLUMN low_stock_level INT NOT NULL DEFAULT 5');
CALL kg_add_column('products', 'expiration_date', 'ALTER TABLE products ADD COLUMN expiration_date DATE NULL');
CALL kg_add_column('products', 'sku', 'ALTER TABLE products ADD COLUMN sku VARCHAR(50) NULL');

CALL kg_add_column('sales', 'receipt_no', 'ALTER TABLE sales ADD COLUMN receipt_no VARCHAR(50) NULL');
CALL kg_add_column('sales', 'cashier_id', 'ALTER TABLE sales ADD COLUMN cashier_id INT NULL');
CALL kg_add_column('sales', 'cashier_name', 'ALTER TABLE sales ADD COLUMN cashier_name VARCHAR(201) NULL');
CALL kg_add_column('sales', 'subtotal_amount', 'ALTER TABLE sales ADD COLUMN subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00');
CALL kg_add_column('sales', 'discount', 'ALTER TABLE sales ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0.00');
CALL kg_add_column('sales', 'tax', 'ALTER TABLE sales ADD COLUMN tax DECIMAL(10,2) NOT NULL DEFAULT 0.00');
CALL kg_add_column('sales', 'tendered_amount', 'ALTER TABLE sales ADD COLUMN tendered_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00');
CALL kg_add_column('sales', 'change_amount', 'ALTER TABLE sales ADD COLUMN change_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00');
CALL kg_add_column('sales', 'payment_method', 'ALTER TABLE sales ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT ''cash''');
CALL kg_add_column('sales', 'status', 'ALTER TABLE sales ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT ''paid''');
CALL kg_add_column('sales', 'closing_status', 'ALTER TABLE sales ADD COLUMN closing_status VARCHAR(20) NOT NULL DEFAULT ''open''');
CALL kg_add_column('sales', 'closed_at', 'ALTER TABLE sales ADD COLUMN closed_at DATETIME NULL');

CALL kg_add_column('inventory_logs', 'created_by', 'ALTER TABLE inventory_logs ADD COLUMN created_by INT NULL');
CALL kg_add_column('closing_reports', 'review_status', 'ALTER TABLE closing_reports ADD COLUMN review_status VARCHAR(20) NOT NULL DEFAULT ''pending''');
CALL kg_add_column('closing_reports', 'admin_feedback', 'ALTER TABLE closing_reports ADD COLUMN admin_feedback VARCHAR(255) DEFAULT NULL');
CALL kg_add_column('closing_reports', 'reviewed_by', 'ALTER TABLE closing_reports ADD COLUMN reviewed_by INT NULL');
CALL kg_add_column('closing_reports', 'reviewed_at', 'ALTER TABLE closing_reports ADD COLUMN reviewed_at DATETIME NULL');

UPDATE users
SET role = 'cashier'
WHERE role IS NULL OR role = '' OR role NOT IN (SELECT name FROM roles);

UPDATE users u
LEFT JOIN roles r ON r.name = u.role
SET u.role_id = r.id
WHERE r.id IS NOT NULL
AND (u.role_id IS NULL OR u.role_id <> r.id);

-- C/D/E/F/G: Clean optional orphan references before adding SET NULL foreign keys.
UPDATE products
SET category_id = NULL
WHERE category_id IS NOT NULL
AND category_id NOT IN (SELECT id FROM categories);

UPDATE products
SET supplier_id = NULL
WHERE supplier_id IS NOT NULL
AND supplier_id NOT IN (SELECT id FROM suppliers);

UPDATE sales
SET cashier_id = NULL
WHERE cashier_id IS NOT NULL
AND cashier_id NOT IN (SELECT id FROM users);

UPDATE sales
SET user_id = NULL
WHERE user_id IS NOT NULL
AND user_id NOT IN (SELECT id FROM users);

UPDATE sales
SET product_id = NULL
WHERE product_id IS NOT NULL
AND product_id NOT IN (SELECT id FROM products);

UPDATE sale_items
SET product_id = NULL
WHERE product_id IS NOT NULL
AND product_id NOT IN (SELECT id FROM products);

UPDATE inventory_logs
SET product_id = NULL
WHERE product_id IS NOT NULL
AND product_id NOT IN (SELECT id FROM products);

UPDATE inventory_logs
SET created_by = NULL
WHERE created_by IS NOT NULL
AND created_by NOT IN (SELECT id FROM users);

UPDATE closing_reports
SET cashier_id = NULL
WHERE cashier_id IS NOT NULL
AND cashier_id NOT IN (SELECT id FROM users);

UPDATE closing_reports
SET closed_by = NULL
WHERE closed_by IS NOT NULL
AND closed_by NOT IN (SELECT id FROM users);

UPDATE closing_reports
SET reviewed_by = NULL
WHERE reviewed_by IS NOT NULL
AND reviewed_by NOT IN (SELECT id FROM users);

-- Indexes required for FK performance and phpMyAdmin Designer relationships.
CALL kg_add_index('users', 'idx_users_role', 'CREATE INDEX idx_users_role ON users (role)');
CALL kg_add_index('users', 'idx_users_role_id', 'CREATE INDEX idx_users_role_id ON users (role_id)');
CALL kg_add_index('products', 'idx_products_category_id', 'CREATE INDEX idx_products_category_id ON products (category_id)');
CALL kg_add_index('products', 'idx_products_supplier_id', 'CREATE INDEX idx_products_supplier_id ON products (supplier_id)');
CALL kg_add_index('products', 'idx_products_sku', 'CREATE INDEX idx_products_sku ON products (sku)');
CALL kg_add_index('sales', 'idx_sales_cashier_id', 'CREATE INDEX idx_sales_cashier_id ON sales (cashier_id)');
CALL kg_add_index('sales', 'idx_sales_user_id', 'CREATE INDEX idx_sales_user_id ON sales (user_id)');
CALL kg_add_index('sales', 'idx_sales_product_id', 'CREATE INDEX idx_sales_product_id ON sales (product_id)');
CALL kg_add_index('sales', 'idx_sales_sale_date', 'CREATE INDEX idx_sales_sale_date ON sales (sale_date)');
CALL kg_add_index('sales', 'idx_sales_closing_status', 'CREATE INDEX idx_sales_closing_status ON sales (closing_status)');
CALL kg_add_index('sale_items', 'idx_sale_items_sale_id', 'CREATE INDEX idx_sale_items_sale_id ON sale_items (sale_id)');
CALL kg_add_index('sale_items', 'idx_sale_items_product_id', 'CREATE INDEX idx_sale_items_product_id ON sale_items (product_id)');
CALL kg_add_index('payments', 'idx_payments_sale_id', 'CREATE INDEX idx_payments_sale_id ON payments (sale_id)');
CALL kg_add_index('receipts', 'idx_receipts_sale_id', 'CREATE INDEX idx_receipts_sale_id ON receipts (sale_id)');
CALL kg_add_index('inventory_logs', 'idx_inventory_logs_product_id', 'CREATE INDEX idx_inventory_logs_product_id ON inventory_logs (product_id)');
CALL kg_add_index('inventory_logs', 'idx_inventory_logs_created_by', 'CREATE INDEX idx_inventory_logs_created_by ON inventory_logs (created_by)');
CALL kg_add_index('closing_reports', 'idx_closing_cashier_id', 'CREATE INDEX idx_closing_cashier_id ON closing_reports (cashier_id)');
CALL kg_add_index('closing_reports', 'idx_closing_closed_by', 'CREATE INDEX idx_closing_closed_by ON closing_reports (closed_by)');
CALL kg_add_index('closing_reports', 'idx_closing_reviewed_by', 'CREATE INDEX idx_closing_reviewed_by ON closing_reports (reviewed_by)');
CALL kg_add_index('admin_notifications', 'idx_admin_notifications_related', 'CREATE INDEX idx_admin_notifications_related ON admin_notifications (related_type, related_id)');
CALL kg_add_index('admin_notifications', 'idx_admin_notifications_read', 'CREATE INDEX idx_admin_notifications_read ON admin_notifications (read_at)');
CALL kg_add_index('admin_notifications', 'idx_admin_notifications_created', 'CREATE INDEX idx_admin_notifications_created ON admin_notifications (created_at)');

-- Foreign keys. Existing constraints with different names are skipped if the column already has a FK.
CALL kg_add_fk_for_column(
    'users',
    'role',
    'fk_users_role_name',
    'ALTER TABLE users ADD CONSTRAINT fk_users_role_name FOREIGN KEY (role) REFERENCES roles(name) ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'users',
    'role_id',
    'fk_users_role_id',
    'ALTER TABLE users ADD CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'products',
    'category_id',
    'fk_products_category',
    'ALTER TABLE products ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'products',
    'supplier_id',
    'fk_products_supplier',
    'ALTER TABLE products ADD CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'sales',
    'cashier_id',
    'fk_sales_cashier',
    'ALTER TABLE sales ADD CONSTRAINT fk_sales_cashier FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'sales',
    'user_id',
    'fk_sales_user',
    'ALTER TABLE sales ADD CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'sales',
    'product_id',
    'fk_sales_legacy_product',
    'ALTER TABLE sales ADD CONSTRAINT fk_sales_legacy_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'sale_items',
    'sale_id',
    'fk_sale_items_sale',
    'ALTER TABLE sale_items ADD CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'sale_items',
    'product_id',
    'fk_sale_items_product',
    'ALTER TABLE sale_items ADD CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'payments',
    'sale_id',
    'fk_payments_sale',
    'ALTER TABLE payments ADD CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'receipts',
    'sale_id',
    'fk_receipts_sale',
    'ALTER TABLE receipts ADD CONSTRAINT fk_receipts_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'inventory_logs',
    'product_id',
    'fk_inventory_logs_product',
    'ALTER TABLE inventory_logs ADD CONSTRAINT fk_inventory_logs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'inventory_logs',
    'created_by',
    'fk_inventory_logs_created_by',
    'ALTER TABLE inventory_logs ADD CONSTRAINT fk_inventory_logs_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'closing_reports',
    'cashier_id',
    'fk_closing_reports_cashier',
    'ALTER TABLE closing_reports ADD CONSTRAINT fk_closing_reports_cashier FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'closing_reports',
    'closed_by',
    'fk_closing_reports_closed_by',
    'ALTER TABLE closing_reports ADD CONSTRAINT fk_closing_reports_closed_by FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

CALL kg_add_fk_for_column(
    'closing_reports',
    'reviewed_by',
    'fk_closing_reports_reviewed_by',
    'ALTER TABLE closing_reports ADD CONSTRAINT fk_closing_reports_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
);

-- One receipt per sale is the intended ERD cardinality. This unique key is skipped if duplicates exist.
CALL kg_add_receipt_sale_unique();

DROP PROCEDURE IF EXISTS kg_add_receipt_sale_unique;
DROP PROCEDURE IF EXISTS kg_add_fk_for_column;
DROP PROCEDURE IF EXISTS kg_add_index;
DROP PROCEDURE IF EXISTS kg_add_column;

-- Migration notes:
-- 1. users.role remains for current PHP login/session compatibility. users.role_id is the normalized FK to roles.id.
-- 2. sales.product_id, sales.quantity, and sales.total_price are legacy compatibility columns. New checkout data is normalized through sale_items.
-- 3. sales.cashier_name, closing_reports.cashier_name, and sale_items.product_name are historical display snapshots for receipts/reports.
-- 4. admin_notifications uses related_type + related_id as a generic notification pointer, so no direct FK is enforced.
-- 5. If phpMyAdmin reports duplicate receipts.sale_id, review duplicates before manually adding uniq_receipts_sale_id.
