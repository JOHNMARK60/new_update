-- Client product seed
-- Run this after the app database migration if you need to restore the
-- submitted 20-product catalog with image paths.

START TRANSACTION;

INSERT IGNORE INTO categories (name)
VALUES
    ('General'),
    ('Beverages'),
    ('Snacks'),
    ('Personal Care'),
    ('Household'),
    ('School Supplies'),
    ('Frozen Goods');

INSERT INTO products
    (name, price, quantity, image_path, category_id, low_stock_level, expiration_date, sku)
SELECT
    seed.name,
    seed.price,
    seed.quantity,
    seed.image_path,
    c.id,
    seed.low_stock_level,
    seed.expiration_date,
    seed.sku
FROM (
    SELECT 'Bottled Water' AS name, 25.00 AS price, 39 AS quantity, 'assets/uploads/products/product_20260513121655_47904b25.jpg' AS image_path, NULL AS category_name, 5 AS low_stock_level, NULL AS expiration_date, NULL AS sku
    UNION ALL SELECT 'Instant Coffee', 12.00, 68, 'assets/uploads/products/product_20260513121549_9f309a7d.jpg', NULL, 5, NULL, NULL
    UNION ALL SELECT 'Notebook', 35.00, 2, 'assets/uploads/products/product_20260513121509_d9f65b1f.jpg', NULL, 5, NULL, NULL
    UNION ALL SELECT 'Ballpen', 10.00, 86, 'assets/uploads/products/product_20260513121439_675629c5.jpg', NULL, 5, NULL, NULL
    UNION ALL SELECT 'Papers', 20.00, 97, 'assets/uploads/products/product_20260513121348_ec8a57ef.jpg', NULL, 5, NULL, NULL
    UNION ALL SELECT 'Kanto Iced Tea 500ml', 35.00, 9, 'assets/images/sample-products/beverages.svg', 'Beverages', 5, NULL, 'BEV-001'
    UNION ALL SELECT 'Kanto Potato Chips 60g', 42.00, 10, 'assets/images/sample-products/snacks.svg', 'Snacks', 5, NULL, 'SNK-001'
    UNION ALL SELECT 'Safe Guard', 48.00, 9, 'assets/uploads/products/product_20260517135758_a0352d5b.jpg', 'Personal Care', 5, NULL, 'PC-001'
    UNION ALL SELECT 'JOY', 55.00, 9, 'assets/uploads/products/product_20260517140016_655203af.jpg', 'Household', 5, NULL, 'HH-001'
    UNION ALL SELECT 'Classic Notebook 80 Leaves', 35.00, 10, 'assets/uploads/products/product_20260517140105_962ab560.jpg', 'School Supplies', 5, NULL, 'SS-001'
    UNION ALL SELECT 'L''Oreal Paris Elvive Color Vibrancy Protecting Shampoo and Conditioner Set, 12.6 Ounce Each', 80.00, 19, 'assets/uploads/products/product_20260517140249_99501261.jpg', 'Personal Care', 5, NULL, NULL
    UNION ALL SELECT 'Pantene', 150.00, 10, 'assets/uploads/products/product_20260517140400_9d9ee09d.jpg', 'Personal Care', 5, '2027-07-17', NULL
    UNION ALL SELECT 'Magnolia CS Chicken Korean Barbeque | 500g-550g', 180.00, 9, 'assets/uploads/products/product_20260517141445_1414e474.webp', 'Frozen Goods', 5, '2026-06-17', NULL
    UNION ALL SELECT 'Purefoods Stuffed Nuggets Bacon & Cheese | 200g', 119.00, 10, 'assets/uploads/products/product_20260517141556_cddbf206.webp', 'Frozen Goods', 5, '2026-06-17', NULL
    UNION ALL SELECT 'PureFoods Tender Juicy Hotdog Classic | 1kg', 189.00, 20, 'assets/uploads/products/product_20260517141724_f3dc7b86.webp', 'Frozen Goods', 5, '2026-07-17', NULL
    UNION ALL SELECT 'Purefoods Chicken Nuggets Fun Stuff Letters & Numbers | 200g', 110.00, 20, 'assets/uploads/products/product_20260517141929_309f6ac0.webp', 'Frozen Goods', 5, '2026-06-17', NULL
    UNION ALL SELECT 'Tang Powdered Juice Drink Calamansi | 25g', 20.00, 20, 'assets/uploads/products/product_20260517142149_0f90ae3b.webp', 'Beverages', 5, '2028-01-17', NULL
    UNION ALL SELECT 'Milo Active Go Twinpack | 48g 8pcs', 142.00, 50, 'assets/uploads/products/product_20260517142244_df9e14ff.webp', 'Beverages', 5, '2028-01-17', NULL
    UNION ALL SELECT 'Great Taste White Crema 3-in-1 Coffee Mix Twin Pack | 50g 5Pcs', 70.95, 19, 'assets/uploads/products/product_20260517142332_190fba4c.webp', 'Beverages', 5, NULL, NULL
    UNION ALL SELECT 'Del Monte Pineapple Juice Drink Fiber Enriched | 1L Tetra', 134.00, 18, 'assets/uploads/products/product_20260517142423_3ae49373.webp', 'Beverages', 5, '2027-01-17', NULL
) AS seed
LEFT JOIN categories c ON c.name = seed.category_name
WHERE NOT EXISTS (
    SELECT 1
    FROM products p
    WHERE p.name = seed.name
    OR (seed.sku IS NOT NULL AND p.sku = seed.sku)
);

COMMIT;
