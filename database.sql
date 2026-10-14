CREATE DATABASE IF NOT EXISTS archive_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE archive_db;

DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS archive_files;
DROP TABLE IF EXISTS users;

-- user types: user | admin | subject (විෂය නිලධාරී)
-- status: pending = admin අනුමත කරන තෙක්, active = login විය හැක, disabled = අක්‍රීය
CREATE TABLE users (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(150) NOT NULL,
    nic            VARCHAR(15)  NOT NULL UNIQUE,
    email          VARCHAR(150) NOT NULL UNIQUE,
    designation    VARCHAR(120) NOT NULL,
    subject_number VARCHAR(40)  NOT NULL,
    role           ENUM('user','admin','subject') NOT NULL DEFAULT 'user',
    department     ENUM('ආයතන','සභා','ගිණුම්') NOT NULL,
    username       VARCHAR(60)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    status         ENUM('pending','active','disabled') NOT NULL DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE archive_files (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    submitted_by   INT NOT NULL,
    submit_date    DATE NOT NULL,
    subject_number VARCHAR(40)  NOT NULL,
    department     ENUM('ආයතන','සභා','ගිණුම්') NOT NULL,
    file_number    VARCHAR(60)  NOT NULL,
    file_name      VARCHAR(255) NOT NULL,
    start_date     DATE NOT NULL,
    end_date       DATE NOT NULL,
    page_count     INT NOT NULL,
    archive_number VARCHAR(60) NULL UNIQUE,
    status         ENUM('pending','archived','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by    INT NULL,
    reviewed_at    DATETIME NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (department), INDEX (subject_number), INDEX (status),
    FOREIGN KEY (submitted_by) REFERENCES users(id),
    FOREIGN KEY (reviewed_by)  REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX (token_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== පරීක්ෂා කිරීමට default accounts (පසුව password වෙනස් කරන්න) =====
--   admin    / Admin@123
--   subject1 / Subject@123
--   minasha  / User@123
INSERT INTO users (full_name, nic, email, designation, subject_number, role, department, username, password_hash, status) VALUES
('System Administrator', '199000000001', 'admin@example.com',   'Administrator',   'ADM-001', 'admin',   'ආයතන', 'admin',
 '$6$Pk9Ptna6ub4vRyQP$Jf7dM2YMx2zatZFirk/QItGcBMf9nL2u4E7RO9QVzwtle12dqM14DvwzQT/IL98gBlIOrIwe2fWgz82KvM7VV1', 'active'),
('Subject Officer',      '198500000002', 'subject@example.com', 'Subject Officer', 'SUB-100', 'subject', 'ගිණුම්', 'subject1',
 '$6$fEqLeEnJJYuCEynP$20d.w6EbnBvlJwoenHNf6OyHl8GKcJ4enBBzKEQvCdwGY3POVAa5UQnZKXe7NFLfmHpyPzIp7dah5dMcl3ir/1', 'active'),
('P.G. Minasha Shenali', '199500000003', 'minasha@example.com', 'Management Assistant', 'SUB-015', 'user', 'ආයතන', 'minasha',
 '$6$sCPSpyvZHQNnzGZO$MDu4uMQpMV6Y0utQ3Jg354reSXw1tH7tiSbKhznrMWkk/Yp407KPocFq6qH83rMWF2m47POHcRjjUG1gQqCU0.', 'active');

INSERT INTO archive_files (submitted_by, submit_date, subject_number, department, file_number, file_name, start_date, end_date, page_count, archive_number, status, reviewed_by, reviewed_at) VALUES
(3,'2026-06-15','SUB-001','ගිණුම්','F-101','වාර්ෂික මුදල් වාර්තාව 2026','2026-01-01','2026-05-30',120,'ARC-001','archived',2,NOW()),
(3,'2026-06-10','SUB-002','ගිණුම්','F-102','වාර්ෂික අයවැය සැලැස්ම','2026-01-01','2026-04-30',85,'ARC-002','archived',2,NOW()),
(3,'2025-12-01','SUB-015','ආයතන','F-115','කාර්ය මණ්ඩල විස්තර','2025-01-01','2025-11-30',64,'ARC-015','archived',2,NOW()),
(3,'2026-05-20','SUB-021','සභා','F-121','චක්‍රලේඛ අංක 07/2026','2026-03-01','2026-05-15',12,'ARC-021','archived',2,NOW()),
(3,'2026-04-18','SUB-030','ආයතන','F-130','තොරතුරු පද්ධති යෝජනාව','2026-02-01','2026-04-10',40,'ARC-030','archived',2,NOW()),
(3,'2025-11-25','SUB-045','සභා','F-145','ව්‍යාපෘති වාර්තාව','2025-06-01','2025-11-20',210,'ARC-045','archived',2,NOW()),
(3,'2025-11-20','SUB-046','සභා','F-146','මාසික ප්‍රගති වාර්තාව','2025-10-01','2025-10-31',18,'ARC-046','archived',2,NOW()),
(3,'2026-03-15','SUB-058','සභා','F-158','රැස්වීම් වාර්තා','2026-01-05','2026-03-10',33,'ARC-058','archived',2,NOW()),
(3,'2025-10-12','SUB-063','ගිණුම්','F-163','ප්‍රසම්පාදන සැලැස්ම','2025-04-01','2025-09-30',76,'ARC-063','archived',2,NOW()),
(3,'2025-09-30','SUB-070','ආයතන','F-170','නිවාඩු වාර්තා','2025-01-01','2025-09-15',52,'ARC-070','archived',2,NOW()),
(3,'2026-02-08','SUB-074','ආයතන','F-174','ජාලය විගණනය','2025-11-01','2026-01-31',28,'ARC-074','archived',2,NOW()),
(3,'2026-01-22','SUB-081','සභා','F-181','පුහුණු කාලසටහන','2026-01-01','2026-01-20',9,'ARC-081','archived',2,NOW()),
(3,'2026-10-12','SUB-090','ගිණුම්','F-190','සැපයුම් ගිණුම් වාර්තාව','2026-07-01','2026-09-30',44,NULL,'pending',NULL,NULL);
