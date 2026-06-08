DROP DATABASE IF EXISTS Restaurant;
CREATE DATABASE Restaurant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Restaurant;

CREATE TABLE roles
(
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (name)
VALUES ('USER'),
       ('STAFF'),
       ('ADMIN');

CREATE TABLE users
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

INSERT INTO users (email, password_hash, first_name, last_name, phone, role_id, email_verified)
VALUES ('user@test.com', '$2y$10$Jt1UViY2MocolMv8vuoeLehjUE234ZU5OCZQrbC9rIcACCTW1j1B2', 'Test', 'Korisnik',
        '0600000003', 1, TRUE),
       ('staff@test.com', '$2y$10$Jt1UViY2MocolMv8vuoeLehjUE234ZU5OCZQrbC9rIcACCTW1j1B2', 'Staff', 'Korisnik',
        '0600000002', 2, TRUE),
       ('admin@test.com', '$2y$10$Jt1UViY2MocolMv8vuoeLehjUE234ZU5OCZQrbC9rIcACCTW1j1B2', 'Admin', 'Korisnik',
        '0600000001', 3, TRUE);

CREATE TABLE auth_sessions
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT      NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_auth_session_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX idx_auth_session_user ON auth_sessions (user_id);
CREATE INDEX idx_auth_session_expires ON auth_sessions (expires_at);

CREATE TABLE verification_tokens
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    token      VARCHAR(255) NOT NULL,
    expires_at DATETIME     NOT NULL,

    CONSTRAINT fk_verification_token_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX idx_verification_token_user ON verification_tokens (user_id);
CREATE INDEX idx_verification_token_expires ON verification_tokens (expires_at);

CREATE TABLE restaurant_tables
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

CREATE INDEX idx_table_seats ON restaurant_tables (seats);

INSERT INTO restaurant_tables
    (name, seats, location_description, is_smoking, layout_x, layout_y, layout_rotation, table_shape)
VALUES
    ('Sto 1', 2, 'Pored prozora', FALSE, 16.00, 22.00, 0, 'round'),
    ('Sto 2', 4, 'Sredina restorana', FALSE, 42.00, 24.00, 0, 'square'),
    ('Sto 3', 6, 'Nepusacki deo', FALSE, 70.00, 24.00, 15, 'rectangle'),
    ('Sto 4', 6, 'VIP kutak', FALSE, 24.00, 58.00, 90, 'oval'),
    ('Sto 5', 5, 'Ugaoni deo sale', FALSE, 54.00, 58.00, 0, 'corner'),
    ('Sto 6', 4, 'Pusacka terasa', TRUE, 82.00, 58.00, 0, 'square');

CREATE TABLE reservations
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

CREATE INDEX idx_reservation_date ON reservations (reservation_date);
CREATE INDEX idx_reservation_user ON reservations (user_id);
CREATE INDEX idx_reservation_code ON reservations (reservation_code);
CREATE INDEX idx_reservation_status ON reservations (status);

CREATE TABLE reservation_tables
(
    reservation_id INT NOT NULL,
    table_id       INT NOT NULL,

    PRIMARY KEY (reservation_id, table_id),

    CONSTRAINT fk_reservation_table_reservation FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE CASCADE,
    CONSTRAINT fk_reservation_table_table FOREIGN KEY (table_id) REFERENCES restaurant_tables (id) ON DELETE CASCADE
);

INSERT INTO reservations
    (user_id, reservation_code, reservation_date, start_time, end_time, status, arrived, arrived_at, arrival_staff_id, comment)
VALUES
    (1, 'ABCD2345', CURDATE(), '18:00:00', '20:00:00', 'ACTIVE', FALSE, NULL, NULL, NULL),
    (1, 'EFGH6789', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:00:00', '21:00:00', 'ACTIVE', FALSE, NULL, NULL, NULL),
    (1, 'JKLM2345', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '17:00:00', '19:00:00', 'COMPLETED', TRUE, DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 'Gost je stigao na vreme.'),
    (1, 'NPQR6789', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '20:00:00', '22:00:00', 'CANCELLED', FALSE, NULL, NULL, NULL);

INSERT INTO reservation_tables (reservation_id, table_id)
VALUES (1, 2),
       (2, 5),
       (3, 1),
       (4, 4);
