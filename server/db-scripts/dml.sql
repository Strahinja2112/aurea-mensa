-- =====================================================
-- RESET DATABASE
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE reservation_logs;
TRUNCATE TABLE reservation_tables;
TRUNCATE TABLE reservations;
TRUNCATE TABLE verification_tokens;
TRUNCATE TABLE users;
TRUNCATE TABLE restaurant_tables;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- USERS
-- =====================================================

INSERT INTO users (email, password_hash, first_name, last_name, phone, role_id, email_verified)
VALUES ('admin@gmail.com', '$2a$04$M9I6c4g6ZYPipFzhlmYeVe7moz319SfDEpZ58e1HEnOrKWo0MXgLm', 'Admin', 'Adminović',
        '0600000001', 2, TRUE),
       ('pavicevicstrahinja99@gmail.com', '$2a$04$M9I6c4g6ZYPipFzhlmYeVe7moz319SfDEpZ58e1HEnOrKWo0MXgLm', 'Strahinja',
        'Pavicevic',
        '0600000002', 3, TRUE),
       ('katjatodic@gmail.com', '$2a$04$M9I6c4g6ZYPipFzhlmYeVe7moz319SfDEpZ58e1HEnOrKWo0MXgLm', 'Katja', 'Todic',
        '0600000003',
        1, TRUE);

-- =====================================================
-- RESTAURANT TABLES
-- =====================================================

INSERT INTO restaurant_tables (name, seats, location_description, is_smoking)
VALUES ('Sto 1', 2, 'Pored prozora', FALSE),
       ('Sto 2', 4, 'Sredina restorana', FALSE),
       ('Sto 3', 6, 'Pušački deo', TRUE),
       ('Sto 4', 8, 'VIP kutak', FALSE);

-- =====================================================
-- RESERVATIONS
-- =====================================================

INSERT INTO reservations (user_id, reservation_code, reservation_date, start_time, end_time, status)
VALUES (2, 'REZ-ABC123', '2026-04-01', '18:00:00', '20:00:00', 'ACTIVE'),
       (3, 'REZ-XYZ789', '2026-04-01', '19:00:00', '22:00:00', 'ACTIVE'),
       (2, 'REZ-STARA1', '2026-03-20', '17:00:00', '19:00:00', 'COMPLETED'),
       (2, 'REZ-STARA2', '2026-03-20', '17:00:00', '19:00:00', 'COMPLETED'),
       (2, 'REZ-STARA3', '2026-03-20', '17:00:00', '19:00:00', 'COMPLETED'),
       (2, 'REZ-STARA12', '2026-03-20', '17:00:00', '19:00:00', 'COMPLETED'),
       (2, 'REZ-STARA1213', '2026-03-20', '17:00:00', '19:00:00', 'COMPLETED'),
       (2, 'REZ-STARA112', '2026-03-20', '17:00:00', '19:00:00', 'COMPLETED'),
       (2, 'REZ-STARA122', '2026-03-20', '17:00:00', '19:00:00', 'COMPLETED'),
       (2, 'REZ-STARA21', '2026-03-20', '17:00:00', '19:00:00', 'COMPLETED');

-- =====================================================
-- RESERVATION TABLES
-- =====================================================

INSERT INTO reservation_tables (reservation_id, table_id)
VALUES (1, 1),
       (1, 2),
       (2, 3),
       (3, 2);

-- =====================================================
-- RESERVATION LOGS
-- =====================================================

INSERT INTO reservation_logs (reservation_id, staff_id, arrived, comment)
VALUES (1, 2, TRUE, 'Gost je stigao na vreme'),
       (3, 2, TRUE, 'Rezervacija uspešno realizovana');

-- =====================================================
-- PASSWORD RESETS
-- =====================================================

INSERT INTO verification_tokens (user_id, token, expires_at)
VALUES (3, 'reset-token-123', DATE_ADD(NOW(), INTERVAL 1 DAY));