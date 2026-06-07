ALTER TABLE restaurant_tables
    ADD COLUMN layout_rotation SMALLINT UNSIGNED DEFAULT 0 AFTER layout_y;
