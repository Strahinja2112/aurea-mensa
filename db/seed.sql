CREATE DATABASE IF NOT EXISTS sn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sn;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reservation_tables;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS auth_sessions;
DROP TABLE IF EXISTS verification_tokens;
DROP TABLE IF EXISTS restaurant_tables;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS roles
(
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS users
(
    id             INT AUTO_INCREMENT PRIMARY KEY,
    email          VARCHAR(255) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    first_name     VARCHAR(100),
    last_name      VARCHAR(100),
    phone          VARCHAR(30) UNIQUE,
    role_id        INT          NOT NULL,
    email_verified BOOLEAN   DEFAULT FALSE,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles (id)
);

CREATE TABLE IF NOT EXISTS auth_sessions
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT      NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_auth_session_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS verification_tokens
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    token      VARCHAR(255) NOT NULL,
    expires_at DATETIME     NOT NULL,

    CONSTRAINT fk_verification_token_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS restaurant_tables
(
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(50) NOT NULL,
    seats                INT         NOT NULL,
    location_description VARCHAR(255),
    is_smoking           BOOLEAN DEFAULT FALSE,
    layout_x             DECIMAL(5, 2) DEFAULT 50.00,
    layout_y             DECIMAL(5, 2) DEFAULT 50.00,
    layout_rotation      SMALLINT UNSIGNED DEFAULT 0,
    table_shape          ENUM ('round', 'square', 'rectangle', 'oval', 'corner') DEFAULT 'round'
);

CREATE TABLE IF NOT EXISTS reservations
(
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT         NOT NULL,
    reservation_code VARCHAR(50) NOT NULL UNIQUE,
    reservation_date DATE        NOT NULL,
    start_time       TIME        NOT NULL,
    end_time         TIME        NOT NULL,
    status           ENUM ('ACTIVE', 'CANCELLED', 'COMPLETED') DEFAULT 'ACTIVE',
    arrived          BOOLEAN                                   DEFAULT FALSE,
    arrived_at       DATETIME,
    arrival_staff_id INT,
    comment          TEXT,
    created_at       TIMESTAMP                                 DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reservation_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_reservation_arrival_staff FOREIGN KEY (arrival_staff_id) REFERENCES users (id)
);

CREATE TABLE IF NOT EXISTS reservation_tables
(
    reservation_id INT NOT NULL,
    table_id       INT NOT NULL,

    PRIMARY KEY (reservation_id, table_id),

    CONSTRAINT fk_reservation_table_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE CASCADE,
    CONSTRAINT fk_reservation_table_table FOREIGN KEY (table_id) REFERENCES restaurant_tables (id) ON DELETE CASCADE
);

INSERT INTO roles (name)
VALUES ('USER'),
       ('STAFF'),
       ('ADMIN');

INSERT INTO users (email, password_hash, first_name, last_name, phone, role_id, email_verified)
VALUES ('user@gmail.com', '$2y$10$Wk6p.6trltuJCVddcGtscuFOkH7uX7Gj5U2n6b9d/ZDIUA0/wYjXW', 'Test', 'Korisnik',
        '0600000003', 1, TRUE),
       ('staff@gmail.com', '$2y$10$Wk6p.6trltuJCVddcGtscuFOkH7uX7Gj5U2n6b9d/ZDIUA0/wYjXW', 'Staff', 'Korisnik',
        '0600000002', 2, TRUE),
       ('admin@gmail.com', '$2y$10$Wk6p.6trltuJCVddcGtscuFOkH7uX7Gj5U2n6b9d/ZDIUA0/wYjXW', 'Admin', 'Korisnik',
        '0600000001', 3, TRUE);

INSERT INTO restaurant_tables
    (id, name, seats, location_description, is_smoking, layout_x, layout_y, layout_rotation, table_shape)
VALUES
    (6, 'Sto 3', 4, NULL, TRUE, 89.76, 12.29, 90, 'corner'),
    (7, 'Sto 4', 4, NULL, TRUE, 91.72, 37.73, 90, 'rectangle'),
    (8, 'Sto 5', 4, NULL, TRUE, 91.94, 60.04, 90, 'rectangle'),
    (9, 'Sto 6', 4, NULL, TRUE, 90.20, 86.36, 180, 'corner'),
    (10, 'Sto 7', 4, NULL, TRUE, 9.59, 12.46, 0, 'corner'),
    (11, 'Sto 6', 6, NULL, TRUE, 8.06, 37.56, 90, 'rectangle'),
    (12, 'Sto 7', 4, NULL, TRUE, 8.06, 60.22, 90, 'rectangle'),
    (13, 'Sto 8', 4, NULL, TRUE, 9.80, 86.36, 270, 'corner'),
    (14, 'Sto 9', 6, NULL, FALSE, 31.15, 28.85, 0, 'rectangle'),
    (15, 'Sto 10', 6, NULL, FALSE, 30.07, 72.42, 0, 'rectangle'),
    (16, 'Sto 11', 6, NULL, FALSE, 66.01, 29.19, 0, 'rectangle'),
    (17, 'Sto 12', 6, NULL, FALSE, 66.01, 72.07, 0, 'rectangle'),
    (18, 'Sto 13', 4, NULL, FALSE, 49.67, 50.81, 0, 'round');

ALTER TABLE restaurant_tables AUTO_INCREMENT = 19;

INSERT INTO reservations
    (user_id, reservation_code, reservation_date, start_time, end_time, status, arrived, arrived_at, arrival_staff_id, comment)
VALUES
    (1, 'ABCD2345', CURDATE(), '18:00:00', '20:00:00', 'ACTIVE', FALSE, NULL, NULL, NULL),
    (1, 'EFGH6789', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:00:00', '21:00:00', 'ACTIVE', FALSE, NULL, NULL, NULL),
    (1, 'JKLM2345', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '17:00:00', '19:00:00', 'COMPLETED', TRUE, DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 'Gost je stigao na vreme.'),
    (1, 'NPQR6789', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '20:00:00', '22:00:00', 'CANCELLED', FALSE, NULL, NULL, NULL);

INSERT INTO reservation_tables (reservation_id, table_id)
VALUES (1, 14),
       (2, 18),
       (3, 7),
       (4, 16);
