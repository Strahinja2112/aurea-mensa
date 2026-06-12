CREATE TABLE IF NOT EXISTS job_applications
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    position     VARCHAR(120) NOT NULL,
    availability TEXT NOT NULL,
    experience   TEXT NOT NULL,
    motivation   TEXT NOT NULL,
    status       ENUM ('PENDING', 'REVIEWED', 'ACCEPTED', 'REJECTED') DEFAULT 'PENDING',
    reviewed_at  DATETIME,
    reviewed_by  INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_job_application_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_job_application_reviewer FOREIGN KEY (reviewed_by) REFERENCES users (id)
);
