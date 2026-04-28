SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE reservation_logs;
TRUNCATE reservation_tables;
TRUNCATE reservations;
TRUNCATE verification_tokens;
TRUNCATE users;
TRUNCATE restaurant_tables;
TRUNCATE roles;

ALTER TABLE reservation_logs
    AUTO_INCREMENT = 1;
ALTER TABLE reservations
    AUTO_INCREMENT = 1;
ALTER TABLE verification_tokens
    AUTO_INCREMENT = 1;
ALTER TABLE users
    AUTO_INCREMENT = 1;
ALTER TABLE restaurant_tables
    AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO roles (id, name)
VALUES (1, 'USER'),
       (2, 'STAFF'),
       (3, 'ADMIN');

INSERT INTO users (email, password_hash, first_name, last_name, phone, role_id, email_verified)
VALUES ('katjatodic@gmail.com', '$2a$04$M1wJu6e1JxtMnLbi0N5VC..xmYefn8bD2xkM/Lewk/3/8IeokOn2i', 'Katja', 'Todic',
        '0600000003',
        1, TRUE),
       ('pavicevicstrahinja99@gmail.com', '$2a$04$M1wJu6e1JxtMnLbi0N5VC..xmYefn8bD2xkM/Lewk/3/8IeokOn2i', 'Strahinja',
        'Pavicevic',
        '0600000002', 2, TRUE),
       ('admin@gmail.com', '$2a$04$M1wJu6e1JxtMnLbi0N5VC..xmYefn8bD2xkM/Lewk/3/8IeokOn2i', 'Admin', 'Adminović',
        '0600000001', 3, TRUE);

INSERT
INTO restaurant_tables (name, seats, location_description, is_smoking)
VALUES ('Sto 1', 2, 'Pored prozora', FALSE),
       ('Sto 2', 4, 'Sredina restorana', FALSE),
       ('Sto 3', 6, 'Pušački deo', TRUE),
       ('Sto 4', 8, 'VIP kutak', FALSE);

INSERT INTO reservations (user_id, reservation_code, reservation_date, start_time, end_time, status)
VALUES
    (1, 'REZ-SUTRA1', '2026-04-05', '14:00:00', '16:00:00', 'ACTIVE'),
    (1, 'REZ-PREKOS', '2026-04-06', '20:00:00', '22:00:00', 'ACTIVE'),
    (1, 'REZ-Danas', '2026-04-04', '22:00:00', '23:30:00', 'ACTIVE'),
    (1, 'REZ-LOCK12', '2026-04-04', '10:00:00', '12:00:00', 'ACTIVE');

INSERT INTO reservation_tables (reservation_id, table_id)
VALUES (1, 1),
       (1, 2),
       (2, 3),
       (3, 2);

INSERT INTO reservation_logs (reservation_id, staff_id, arrived, comment)
VALUES (1, 2, TRUE, 'Gost je stigao na vreme'),
       (3, 2, TRUE, 'Rezervacija uspešno realizovana');

INSERT INTO verification_tokens (user_id, token, expires_at)
VALUES (3, 'reset-token-123', DATE_ADD(NOW(), INTERVAL 1 DAY));