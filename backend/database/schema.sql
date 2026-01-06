-- Database schema for UMKM Checker (Sertif-Kilat)
-- Create database
CREATE DATABASE IF NOT EXISTS umkm_checker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE umkm_checker;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('consumer','umkm','admin') NOT NULL DEFAULT 'consumer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    producer VARCHAR(255) NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('pending','verified','expired','rejected') DEFAULT 'pending',
    owner_id INT NULL,
    certification_type VARCHAR(50) NULL,
    certification_link TEXT NULL,
    hygiene_score INT DEFAULT 0,
    quality_score INT DEFAULT 0,
    trust_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_owner
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: product_certifications
CREATE TABLE IF NOT EXISTS product_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    certification_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password, role) VALUES
('Konsumen Uji Coba', 'user@test.com', '$2y$10$GAFXUdlOt7yFAkb8BJ1LA.ssUGU2AX59MZZJpEuHReOw9TJeGviX6', 'consumer'),
('UMKM Berkah', 'umkm@berkah.com', '$2y$10$GAFXUdlOt7yFAkb8BJ1LA.ssUGU2AX59MZZJpEuHReOw9TJeGviX6', 'umkm'),
('Admin Sistem', 'admin@umkm.local', '$2y$10$GAFXUdlOt7yFAkb8BJ1LA.ssUGU2AX59MZZJpEuHReOw9TJeGviX6', 'admin')
ON DUPLICATE KEY UPDATE name=VALUES(name), role=VALUES(role);

-- Insert dummy products
INSERT INTO products (product_code, name, producer, expiry_date, status, hygiene_score, quality_score, trust_score) VALUES
('BPOM-12345', 'Keripik Singkong Original', 'UMKM Berkah Jaya', '2026-12-31', 'verified', 95, 92, 98),
('HALAL-67890', 'Sambal Khas Nusantara', 'UMKM Pedas Mantap', '2026-08-15', 'verified', 88, 90, 85),
('BPOM-99999', 'Kue Tradisional Kering', 'UMKM Manis Lezat', '2024-06-30', 'expired', 75, 70, 65),
('HALAL-11111', 'Dodol Durian Premium', 'UMKM Durian Sejati', '2027-03-20', 'verified', 97, 95, 96)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert certifications for products
INSERT INTO product_certifications (product_id, certification_type) VALUES
((SELECT id FROM products WHERE product_code = 'BPOM-12345'), 'BPOM'),
((SELECT id FROM products WHERE product_code = 'BPOM-12345'), 'Halal MUI'),
((SELECT id FROM products WHERE product_code = 'BPOM-12345'), 'SNI'),
((SELECT id FROM products WHERE product_code = 'HALAL-67890'), 'Halal MUI'),
((SELECT id FROM products WHERE product_code = 'HALAL-67890'), 'BPOM'),
((SELECT id FROM products WHERE product_code = 'BPOM-99999'), 'BPOM'),
((SELECT id FROM products WHERE product_code = 'HALAL-11111'), 'Halal MUI'),
((SELECT id FROM products WHERE product_code = 'HALAL-11111'), 'BPOM'),
((SELECT id FROM products WHERE product_code = 'HALAL-11111'), 'SNI')
ON DUPLICATE KEY UPDATE certification_type=VALUES(certification_type);

