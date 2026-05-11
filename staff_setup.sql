
USE beauty_salon_db;

CREATE TABLE IF NOT EXISTS staff (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    role        ENUM('hairstylist','nail_tech','makeup_artist','lash_tech','other') DEFAULT 'other',
    role_label  VARCHAR(60),
    bio         TEXT,
    avatar_initials VARCHAR(3),
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff_schedule (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    staff_id   INT NOT NULL,
    work_date  DATE NOT NULL,
    is_working TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_schedule (staff_id, work_date),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);

ALTER TABLE appointments ADD COLUMN IF NOT EXISTS staff_id INT DEFAULT NULL;

ALTER TABLE appointments
    ADD CONSTRAINT fk_appt_staff_fixed FOREIGN KEY (staff_id) REFERENCES staff(id);

INSERT INTO staff (full_name, role, role_label, bio, avatar_initials, is_active)
SELECT * FROM (
    SELECT 'Нарантуяа Б.' AS full_name, 'hairstylist' AS role, 'Үсчин' AS role_label, '10 жилийн туршлагатай, тренд засалтын мэргэжилтэн.' AS bio, 'НБ' AS avatar_initials, 1 AS is_active
    UNION ALL SELECT 'Энхтуяа Д.', 'hairstylist', 'Үсчин', 'Будалт болон балаяж үйлчилгээний мэргэжилтэн.', 'ЭД', 1
    UNION ALL SELECT 'Оюунцэцэг М.', 'hairstylist', 'Үсчин', 'Дорно дахины засалт болон уламжлалт техникийн мэргэжилтэн.', 'ОМ', 1
    UNION ALL SELECT 'Уянга Т.', 'nail_tech', 'Маникюрч', 'Гел хумс болон урлалын дизайны мэргэжилтэн.', 'УТ', 1
    UNION ALL SELECT 'Болортуяа Ж.', 'nail_tech', 'Маникюрч', 'Хурдан, үнэн зөв, гоёмсог хумсны дизайн.', 'БЖ', 1
    UNION ALL SELECT 'Мишээл А.', 'makeup_artist', 'Make-up артист', 'Өдөр тутмын болон арга хэмжээний нүүр будалтын мэргэжилтэн.', 'МА', 1
    UNION ALL SELECT 'Саруул Э.', 'lash_tech', 'Сормуус артист', 'Сормуус суулгалт болон засварын мэргэжилтэн.', 'СЭ', 1
) AS x
WHERE NOT EXISTS (
    SELECT 1 FROM staff s WHERE s.full_name = x.full_name AND s.role = x.role
);

DROP PROCEDURE IF EXISTS generate_staff_schedule_3_months;

DELIMITER //
CREATE PROCEDURE generate_staff_schedule_3_months()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE d DATE;


    -- Өнөөдрөөс эхлээд 3 сар урагш бүх идэвхтэй артистад хуваарь үүсгэнэ.
    -- Ням гарагт is_working=0, бусад өдөр is_working=1.
    WHILE i <= 92 DO
        SET d = DATE_ADD(CURDATE(), INTERVAL i DAY);

        INSERT INTO staff_schedule (staff_id, work_date, is_working)
        SELECT id, d, IF(DAYOFWEEK(d) = 1, 0, 1)
        FROM staff
        WHERE is_active = 1
        ON DUPLICATE KEY UPDATE
            is_working = VALUES(is_working);

        SET i = i + 1;
    END WHILE;
END //
DELIMITER ;

CALL generate_staff_schedule_3_months();

-- Жинхэнэ автоматаар сунгах EVENT. XAMPP дээр event_scheduler OFF байж магадгүй.
-- Асаах боломжтой бол phpMyAdmin SQL дээр: SET GLOBAL event_scheduler = ON;
DROP EVENT IF EXISTS ev_extend_staff_schedule_3_months;
CREATE EVENT ev_extend_staff_schedule_3_months
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    CALL generate_staff_schedule_3_months();
