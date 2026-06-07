ALTER TABLE restaurant_tables
    MODIFY COLUMN table_shape ENUM ('round', 'square', 'rectangle', 'oval', 'corner') DEFAULT 'round';
