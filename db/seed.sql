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

    -- TODO: zameni kad zapravo budes koristio MAILER
    email_verified BOOLEAN   DEFAULT FALSE,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles (id)
);

INSERT INTO users (email, password_hash, first_name, last_name, phone, role_id, email_verified)
VALUES ('novakpavicevic@gmail.com', '$2a$04$M1wJu6e1JxtMnLbi0N5VC..xmYefn8bD2xkM/Lewk/3/8IeokOn2i', 'Novak', 'Pavicevic',
        '0600000003',
        1, TRUE),
       ('pavicevicstrahinja99@gmail.com', '$2a$04$M1wJu6e1JxtMnLbi0N5VC..xmYefn8bD2xkM/Lewk/3/8IeokOn2i', 'Strahinja',
        'Pavicevic',
        '0600000002', 2, TRUE),
       ('admin@gmail.com', '$2a$04$M1wJu6e1JxtMnLbi0N5VC..xmYefn8bD2xkM/Lewk/3/8IeokOn2i', 'Katja', 'Todic',
        '0600000001', 3, TRUE);

CREATE TABLE auth_sessions
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT      NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX idx_auth_session_user ON auth_sessions (user_id);

CREATE TABLE verification_tokens
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    token      VARCHAR(255) NOT NULL,
    expires_at DATETIME     NOT NULL,
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX idx_verification_token_expires ON verification_tokens (expires_at);

-- TODO: ovo nije ni blizu gotovo, popravi fuj
CREATE TABLE restaurant_tables
(
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(50) NOT NULL,
    seats                INT         NOT NULL,
    location_description VARCHAR(255),
    is_smoking           BOOLEAN DEFAULT FALSE,
    layout_x             DECIMAL(5, 2) DEFAULT 50.00,
    layout_y             DECIMAL(5, 2) DEFAULT 50.00,
    table_shape          ENUM ('round', 'square') DEFAULT 'round'
);

INSERT
INTO restaurant_tables (name, seats, location_description, is_smoking, layout_x, layout_y, table_shape)
VALUES ('Sto 1', 2, 'Pored prozora', FALSE, 18.00, 24.00, 'round'),
       ('Sto 2', 4, 'Sredina restorana', FALSE, 48.00, 28.00, 'square'),
       ('Sto 3', 6, 'Pušački deo', TRUE, 76.00, 58.00, 'round'),
       ('Sto 4', 8, 'VIP kutak', FALSE, 30.00, 70.00, 'square');

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

    CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_arrival_staff FOREIGN KEY (arrival_staff_id) REFERENCES users (id)
);

CREATE TABLE reservation_tables
(
    reservation_id INT NOT NULL,
    table_id       INT NOT NULL,

    PRIMARY KEY (reservation_id, table_id),

    CONSTRAINT fk_rt_res FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE CASCADE,
    CONSTRAINT fk_rt_table FOREIGN KEY (table_id) REFERENCES restaurant_tables (id) ON DELETE CASCADE
);

CREATE INDEX idx_reservation_date ON reservations (reservation_date);
CREATE INDEX idx_reservation_user ON reservations (user_id);
CREATE INDEX idx_table_seats ON restaurant_tables (seats);
