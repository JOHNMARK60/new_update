-- KANTO GOODS 3NF safety migration
-- Backup reminder: export the full database before running this script.
-- This migration preserves existing tables and data. It adds missing indexes
-- and foreign keys around the current normalized tables without dropping
-- legacy compatibility columns used by existing PHP routes.

START TRANSACTION;

INSERT IGNORE INTO roles (name, label)
VALUES ('admin', 'Administrator'), ('cashier', 'Cashier');

INSERT IGNORE INTO categories (name)
VALUES
    ('General'),
    ('Beverages'),
    ('Snacks'),
    ('Personal Care'),
    ('Household'),
    ('School Supplies');

INSERT INTO products (name, price, quantity, image_path, category_id, low_stock_level, sku)
SELECT 'Kanto Iced Tea 500ml', 35.00, 10, 'assets/images/sample-products/beverages.svg', c.id, 5, 'BEV-001'
FROM categories c
WHERE c.name = 'Beverages'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.sku = 'BEV-001' OR p.name = 'Kanto Iced Tea 500ml');

INSERT INTO products (name, price, quantity, image_path, category_id, low_stock_level, sku)
SELECT 'Kanto Potato Chips 60g', 42.00, 10, 'assets/images/sample-products/snacks.svg', c.id, 5, 'SNK-001'
FROM categories c
WHERE c.name = 'Snacks'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.sku = 'SNK-001' OR p.name = 'Kanto Potato Chips 60g');

INSERT INTO products (name, price, quantity, image_path, category_id, low_stock_level, sku)
SELECT 'Fresh Care Soap 90g', 48.00, 10, 'assets/images/sample-products/personal-care.svg', c.id, 5, 'PC-001'
FROM categories c
WHERE c.name = 'Personal Care'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.sku = 'PC-001' OR p.name = 'Fresh Care Soap 90g');

INSERT INTO products (name, price, quantity, image_path, category_id, low_stock_level, sku)
SELECT 'Home Clean Dish Soap 250ml', 55.00, 10, 'assets/images/sample-products/household.svg', c.id, 5, 'HH-001'
FROM categories c
WHERE c.name = 'Household'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.sku = 'HH-001' OR p.name = 'Home Clean Dish Soap 250ml');

INSERT INTO products (name, price, quantity, image_path, category_id, low_stock_level, sku)
SELECT 'Classic Notebook 80 Leaves', 35.00, 10, 'assets/images/sample-products/school-supplies.svg', c.id, 5, 'SS-001'
FROM categories c
WHERE c.name = 'School Supplies'
AND NOT EXISTS (SELECT 1 FROM products p WHERE p.sku = 'SS-001' OR p.name = 'Classic Notebook 80 Leaves');

UPDATE products
SET category_id = NULL
WHERE category_id IS NOT NULL
AND category_id NOT IN (SELECT id FROM categories);

UPDATE products
SET supplier_id = NULL
WHERE supplier_id IS NOT NULL
AND supplier_id NOT IN (SELECT id FROM suppliers);

UPDATE users
SET role = 'cashier'
WHERE role IS NULL OR role = '' OR role NOT IN (SELECT name FROM roles);

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

COMMIT;

DELIMITER $$

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

DROP PROCEDURE IF EXISTS kg_add_fk $$
CREATE PROCEDURE kg_add_fk(
    IN p_table_name VARCHAR(64),
    IN p_constraint_name VARCHAR(64),
    IN p_constraint_sql TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.table_constraints
        WHERE table_schema = DATABASE()
        AND table_name = p_table_name
        AND constraint_name = p_constraint_name
        AND constraint_type = 'FOREIGN KEY'
    ) THEN
        SET @kg_sql = p_constraint_sql;
        PREPARE kg_stmt FROM @kg_sql;
        EXECUTE kg_stmt;
        DEALLOCATE PREPARE kg_stmt;
    END IF;
END $$

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

DELIMITER ;

CALL kg_add_column('closing_reports', 'review_status', 'ALTER TABLE closing_reports ADD COLUMN review_status VARCHAR(20) NOT NULL DEFAULT ''pending''');
CALL kg_add_column('closing_reports', 'admin_feedback', 'ALTER TABLE closing_reports ADD COLUMN admin_feedback VARCHAR(255) DEFAULT NULL');
CALL kg_add_column('closing_reports', 'reviewed_by', 'ALTER TABLE closing_reports ADD COLUMN reviewed_by INT NULL');
CALL kg_add_column('closing_reports', 'reviewed_at', 'ALTER TABLE closing_reports ADD COLUMN reviewed_at DATETIME NULL');

UPDATE closing_reports
SET review_status = CASE
    WHEN difference_amount < 0 THEN 'short'
    WHEN difference_amount > 0 THEN 'over'
    ELSE 'balanced'
END
WHERE review_status IS NULL OR review_status = '' OR review_status = 'pending';

UPDATE closing_reports
SET admin_feedback = CONCAT('Short PHP ', FORMAT(ABS(difference_amount), 2), '. Please explain missing cash before next shift.')
WHERE difference_amount < 0
AND (admin_feedback IS NULL OR admin_feedback = '');

CALL kg_add_index('users', 'idx_users_role', 'CREATE INDEX idx_users_role ON users (role)');
CALL kg_add_index('products', 'idx_products_category_id', 'CREATE INDEX idx_products_category_id ON products (category_id)');
CALL kg_add_index('products', 'idx_products_supplier_id', 'CREATE INDEX idx_products_supplier_id ON products (supplier_id)');
CALL kg_add_index('products', 'idx_products_sku', 'CREATE INDEX idx_products_sku ON products (sku)');
CALL kg_add_index('sales', 'idx_sales_cashier_id', 'CREATE INDEX idx_sales_cashier_id ON sales (cashier_id)');
CALL kg_add_index('sales', 'idx_sales_closing_status', 'CREATE INDEX idx_sales_closing_status ON sales (closing_status)');
CALL kg_add_index('sales', 'idx_sales_sale_date', 'CREATE INDEX idx_sales_sale_date ON sales (sale_date)');
CALL kg_add_index('inventory_logs', 'idx_inventory_logs_created_by', 'CREATE INDEX idx_inventory_logs_created_by ON inventory_logs (created_by)');
CALL kg_add_index('closing_reports', 'idx_closing_reports_closed_by', 'CREATE INDEX idx_closing_reports_closed_by ON closing_reports (closed_by)');
CALL kg_add_index('closing_reports', 'idx_closing_reports_review_status', 'CREATE INDEX idx_closing_reports_review_status ON closing_reports (review_status)');

CALL kg_add_fk(
    'users',
    'fk_users_role',
    'ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role) REFERENCES roles(name) ON UPDATE CASCADE'
);

CALL kg_add_fk(
    'products',
    'fk_products_category',
    'ALTER TABLE products ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL'
);

CALL kg_add_fk(
    'products',
    'fk_products_supplier',
    'ALTER TABLE products ADD CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL'
);

CALL kg_add_fk(
    'sales',
    'fk_sales_cashier',
    'ALTER TABLE sales ADD CONSTRAINT fk_sales_cashier FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL'
);

CALL kg_add_fk(
    'sales',
    'fk_sales_user',
    'ALTER TABLE sales ADD CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL'
);

CALL kg_add_fk(
    'sales',
    'fk_sales_legacy_product',
    'ALTER TABLE sales ADD CONSTRAINT fk_sales_legacy_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL'
);

CALL kg_add_fk(
    'inventory_logs',
    'fk_inventory_logs_product',
    'ALTER TABLE inventory_logs ADD CONSTRAINT fk_inventory_logs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL'
);

CALL kg_add_fk(
    'inventory_logs',
    'fk_inventory_logs_created_by',
    'ALTER TABLE inventory_logs ADD CONSTRAINT fk_inventory_logs_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL'
);

CALL kg_add_fk(
    'closing_reports',
    'fk_closing_reports_reviewed_by',
    'ALTER TABLE closing_reports ADD CONSTRAINT fk_closing_reports_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL'
);

DROP PROCEDURE IF EXISTS kg_add_fk;
DROP PROCEDURE IF EXISTS kg_add_index;
DROP PROCEDURE IF EXISTS kg_add_column;

-- Migration notes:
-- 1. sale_items is the normalized line-item table for sales. The legacy
--    sales.product_id, sales.quantity, and sales.total_price columns are kept
--    only for backward compatibility with older records and reports.
-- 2. sales.cashier_name and sale_items.product_name are historical snapshots
--    for receipts. They preserve what was printed at the time of sale even if
--    a user or product name later changes.
-- 3. No existing table or data is dropped by this migration.
