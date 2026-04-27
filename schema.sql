CREATE DATABASE IF NOT EXISTS beauty_salon_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE beauty_salon_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

INSERT INTO services (name, description, duration_minutes, price)
SELECT * FROM (
    SELECT 'Үс засалт', 'Эмэгтэй болон эрэгтэй үс засалт', 30, 25000
    UNION ALL SELECT 'Үс будалт', 'Мэргэжлийн үс будалт', 120, 150000
    UNION ALL SELECT 'Маникюр', 'Хумс арчилгаа болон будах үйлчилгээ', 60, 45000
    UNION ALL SELECT 'Педикюр', 'Хөлийн хумс арчилгаа', 60, 50000
    UNION ALL SELECT 'Make-up', 'Өдөр тутмын болон арга хэмжээний нүүр будалт', 90, 120000
    UNION ALL SELECT 'Сормуус', 'Сормуус суулгалт болон засвар', 90, 80000
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM services);