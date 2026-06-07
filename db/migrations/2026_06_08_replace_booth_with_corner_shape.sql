ALTER TABLE restaurant_tables
    MODIFY COLUMN table_shape ENUM ('round', 'square', 'rectangle', 'oval', 'booth', 'corner') DEFAULT 'round';

UPDATE restaurant_tables
SET table_shape = 'corner'
WHERE table_shape = 'booth';

ALTER TABLE restaurant_tables
    MODIFY COLUMN table_shape ENUM ('round', 'square', 'rectangle', 'oval', 'corner') DEFAULT 'round';
