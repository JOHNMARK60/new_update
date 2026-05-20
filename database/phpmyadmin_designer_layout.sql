-- KANTO GOODS phpMyAdmin Designer layout
-- Run after phpMyAdmin configuration storage is enabled.
-- This creates a saved Designer/PDF page named after the database and positions
-- the tables from parent/master tables to transaction/detail/history tables.

USE phpmyadmin;

INSERT INTO pma__pdf_pages (db_name, page_descr)
SELECT 'cashieringinventorysystem', 'cashieringinventorysystem'
WHERE NOT EXISTS (
    SELECT 1
    FROM pma__pdf_pages
    WHERE db_name = 'cashieringinventorysystem'
    AND page_descr = 'cashieringinventorysystem'
);

SET @kg_page_nr := (
    SELECT page_nr
    FROM pma__pdf_pages
    WHERE db_name = 'cashieringinventorysystem'
    AND page_descr = 'cashieringinventorysystem'
    ORDER BY page_nr DESC
    LIMIT 1
);

DELETE FROM pma__table_coords
WHERE db_name = 'cashieringinventorysystem'
AND pdf_page_number = @kg_page_nr;

INSERT INTO pma__table_coords (db_name, table_name, pdf_page_number, x, y)
VALUES
    ('cashieringinventorysystem', 'roles', @kg_page_nr, 40, 40),
    ('cashieringinventorysystem', 'categories', @kg_page_nr, 520, 40),
    ('cashieringinventorysystem', 'suppliers', @kg_page_nr, 900, 40),
    ('cashieringinventorysystem', 'admin_notifications', @kg_page_nr, 1280, 40),
    ('cashieringinventorysystem', 'users', @kg_page_nr, 40, 320),
    ('cashieringinventorysystem', 'products', @kg_page_nr, 520, 320),
    ('cashieringinventorysystem', 'sales', @kg_page_nr, 40, 760),
    ('cashieringinventorysystem', 'inventory_logs', @kg_page_nr, 900, 760),
    ('cashieringinventorysystem', 'closing_reports', @kg_page_nr, 40, 1220),
    ('cashieringinventorysystem', 'sale_items', @kg_page_nr, 520, 1220),
    ('cashieringinventorysystem', 'payments', @kg_page_nr, 900, 1220),
    ('cashieringinventorysystem', 'receipts', @kg_page_nr, 1280, 1220);
