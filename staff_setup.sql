-- ====================================================
-- АЖИЛЧДЫН ХҮСНЭГТ (staff)
-- ====================================================
CREATE TABLE IF NOT EXISTS staff (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    role        ENUM('hairstylist','nail_tech','makeup_artist','lash_tech','other') DEFAULT 'other',
    role_label  VARCHAR(60),        -- Монгол нэр
    bio         TEXT,
    avatar_initials VARCHAR(3),
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ====================================================
-- АЖЛЫН ЦАГ (staff_schedule) — өдөр тутмын ажиллах цаг
-- Хэрэв тухайн өдөр байхгүй бол амралт
-- ====================================================
CREATE TABLE IF NOT EXISTS staff_schedule (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    staff_id   INT NOT NULL,
    work_date  DATE NOT NULL,
    is_working TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_schedule (staff_id, work_date),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- ====================================================
-- APPOINTMENTS-Д staff_id нэмэх
-- ====================================================
ALTER TABLE appointments
    ADD COLUMN IF NOT EXISTS staff_id INT DEFAULT NULL,
    ADD CONSTRAINT fk_appt_staff FOREIGN KEY (staff_id) REFERENCES staff(id);

-- ====================================================
-- ЖИШЭЭ АЖИЛЧИД
-- Үсчин: 3 хүн, Маникюрч: 2 хүн
-- ====================================================
INSERT INTO staff (full_name, role, role_label, bio, avatar_initials, is_active)
VALUES
    ('Нарантуяа Б.',    'hairstylist',   'Үсчин',     '10 жилийн туршлагатай, тренд засалтын мэргэжилтэн.',   'НБ', 1),
    ('Энхтуяа Д.',      'hairstylist',   'Үсчин',     'Будалт болон балаяж үйлчилгээний мэргэжилтэн.',        'ЭД', 1),
    ('Оюунцэцэг М.',    'hairstylist',   'Үсчин',     'Дорно дахины засалт болон уламжлалт техникийн гарамгай.','ОМ', 1),
    ('Уянга Т.',        'nail_tech',     'Маникюрч',  'Гел хумс болон урлалын дизайны мэргэжилтэн.',          'УТ', 1),
    ('Болортуяа Ж.',    'nail_tech',     'Маникюрч',  'Хурдан, үнэн зөв, гоёмсог хумсны дизайн.',            'БЖ', 1);

-- ====================================================
-- ӨНӨӨДРӨӨС 30 ХОНОГИЙН АЖЛЫН ХУВААРЬ ОРУУЛАХ
-- Үсчид: ээлжилсэн (2 хүн нэг өдөр, 1 хүн амарна)
-- Маникюрчид: ижил ээлж
-- ====================================================

-- Процедур ашиглан хуваарь үүсгэх
DROP PROCEDURE IF EXISTS generate_schedule;

DELIMITER //
CREATE PROCEDURE generate_schedule()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE d DATE;
    DECLARE dow INT;  -- 0=Sunday, 1=Mon,...6=Sat
    DECLARE hair_on_leave INT;    -- 1,2,3 ээлжлэн
    DECLARE nail_on_leave INT;    -- 4 эсвэл 5

    WHILE i < 60 DO
        SET d = DATE_ADD(CURDATE(), INTERVAL i DAY);
        SET dow = DAYOFWEEK(d) - 1;  -- 0=Sun

        -- Ням гаригт хаалттай
        IF dow = 0 THEN
            INSERT IGNORE INTO staff_schedule (staff_id, work_date, is_working)
            VALUES (1,d,0),(2,d,0),(3,d,0),(4,d,0),(5,d,0);
        ELSE
            -- Үсчид: i mod 3 + 1 дугаартай нь амарна
            SET hair_on_leave = (i MOD 3) + 1;

            -- staff 1,2,3 — hair
            INSERT IGNORE INTO staff_schedule (staff_id, work_date, is_working)
            VALUES
                (1, d, IF(hair_on_leave=1, 0, 1)),
                (2, d, IF(hair_on_leave=2, 0, 1)),
                (3, d, IF(hair_on_leave=3, 0, 1));

            -- Маникюрчид: ижил ажиллана, зөвхөн нэг нь амарна
            SET nail_on_leave = IF((i MOD 2) = 0, 4, 5);
            INSERT IGNORE INTO staff_schedule (staff_id, work_date, is_working)
            VALUES
                (4, d, IF(nail_on_leave=4, 0, 1)),
                (5, d, IF(nail_on_leave=5, 0, 1));
        END IF;

        SET i = i + 1;
    END WHILE;
END //
DELIMITER ;

CALL generate_schedule();
DROP PROCEDURE IF EXISTS generate_schedule;
