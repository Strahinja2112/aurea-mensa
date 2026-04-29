-- =====================================================
-- DATABASE: Restaurant
-- =====================================================

DROP
    DATABASE IF EXISTS Restaurant;
CREATE
    DATABASE Restaurant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE
    Restaurant;

-- =====================================================
-- ROLES
-- =====================================================

CREATE TABLE roles
(
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (name)
VALUES ('USER'),
       ('STAFF'),
       ('ADMIN');

-- =====================================================
-- USERS
-- =====================================================

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
    email_verified BOOLEAN   DEFAULT TRUE,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles (id)
);

CREATE TABLE auth_sessions
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT      NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- =====================================================
-- PASSWORD RESET TOKENS
-- =====================================================

CREATE TABLE verification_tokens
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    token      VARCHAR(255) NOT NULL,
    expires_at DATETIME     NOT NULL,

    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- =====================================================
-- TABLES (RESTAURANT TABLES)
-- =====================================================

CREATE TABLE restaurant_tables
(
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(50) NOT NULL,
    seats                INT         NOT NULL,
    location_description VARCHAR(255),
    is_smoking           BOOLEAN DEFAULT FALSE
);

-- =====================================================
-- RESERVATIONS
-- =====================================================

CREATE TABLE reservations
(
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT         NOT NULL,
    reservation_code VARCHAR(50) NOT NULL UNIQUE,
    reservation_date DATE        NOT NULL,
    start_time       TIME        NOT NULL,
    end_time         TIME        NOT NULL,
    status           ENUM ('ACTIVE', 'CANCELLED', 'COMPLETED') DEFAULT 'ACTIVE',
    arrived          BOOLEAN   DEFAULT FALSE,
    arrived_at       DATETIME,
    arrival_staff_id INT,
    comment          TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_arrival_staff FOREIGN KEY (arrival_staff_id) REFERENCES users (id)
);

-- =====================================================
-- RESERVATION TABLES (M:N)
-- =====================================================

CREATE TABLE reservation_tables
(
    reservation_id INT NOT NULL,
    table_id       INT NOT NULL,

    PRIMARY KEY (reservation_id, table_id),

    CONSTRAINT fk_rt_res FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE CASCADE,
    CONSTRAINT fk_rt_table FOREIGN KEY (table_id) REFERENCES restaurant_tables (id) ON DELETE CASCADE
);

-- =====================================================
-- STAFF COMMENTS / ARRIVAL CONFIRMATION
-- =====================================================

CREATE TABLE reservation_logs
(
    id             INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    staff_id       INT NOT NULL,
    arrived        BOOLEAN   DEFAULT FALSE,
    comment        TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_res FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE CASCADE,
    CONSTRAINT fk_log_staff FOREIGN KEY (staff_id) REFERENCES users (id)
);

-- =====================================================
-- INDEXES (performance)
-- =====================================================

CREATE INDEX idx_reservation_date ON reservations (reservation_date);
CREATE INDEX idx_reservation_user ON reservations (user_id);
CREATE INDEX idx_table_seats ON restaurant_tables (seats);

-- =====================================================
-- NOTES
-- =====================================================
-- Overlap validation (time conflicts) rešava se na aplikacionom nivou (PHP/JS)
-- Kod rezervacije generiše backend (npr UUID ili random string)
-- Email aktivacija i reset lozinke koriste verification_tokens tabelu