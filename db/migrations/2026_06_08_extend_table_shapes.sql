ALTER TABLE restaurant_tables
    MODIFY COLUMN table_shape ENUM ('round', 'square', 'rectangle', 'oval', 'booth') DEFAULT 'round';
