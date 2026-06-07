ALTER TABLE restaurant_tables
    ADD COLUMN layout_x DECIMAL(5, 2) DEFAULT 50.00 AFTER is_smoking,
    ADD COLUMN layout_y DECIMAL(5, 2) DEFAULT 50.00 AFTER layout_x,
    ADD COLUMN layout_rotation SMALLINT UNSIGNED DEFAULT 0 AFTER layout_y,
    ADD COLUMN table_shape ENUM ('round', 'square', 'rectangle', 'oval', 'corner') DEFAULT 'round' AFTER layout_rotation;

UPDATE restaurant_tables
SET layout_x = CASE
        WHEN id % 4 = 1 THEN 18.00
        WHEN id % 4 = 2 THEN 42.00
        WHEN id % 4 = 3 THEN 66.00
        ELSE 82.00
    END,
    layout_y = 22.00 + (FLOOR((id - 1) / 4) * 24.00),
    layout_rotation = 0,
    table_shape = CASE WHEN seats >= 4 THEN 'square' ELSE 'round' END
WHERE layout_x IS NULL
   OR layout_y IS NULL
   OR layout_rotation IS NULL
   OR table_shape IS NULL;
