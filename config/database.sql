-- Database Creation Script for Don Vlavonou Platform
-- Execute this script to create the database and tables

CREATE DATABASE IF NOT EXISTS don_vlavonou CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE don_vlavonou;

-- Table des donations
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('fedapay', 'kkiapay') NOT NULL,
    message TEXT,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_reference VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des logs de sécurité
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des tokens CSRF
CREATE TABLE IF NOT EXISTS csrf_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des reçus envoyés
CREATE TABLE IF NOT EXISTS receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donation_id INT NOT NULL,
    email_sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    email_status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE,
    INDEX idx_donation_id (donation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nettoyage automatique des tokens expirés (événement planifié)
CREATE EVENT IF NOT EXISTS cleanup_expired_tokens
ON SCHEDULE EVERY 1 HOUR
DO
    DELETE FROM csrf_tokens WHERE expires_at < NOW() OR (used = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Vue pour les statistiques
CREATE OR REPLACE VIEW donation_statistics AS
SELECT 
    DATE(created_at) as donation_date,
    COUNT(*) as total_donations,
    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount,
    AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as average_amount,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_donations,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_donations,
    payment_method
FROM donations
GROUP BY DATE(created_at), payment_method
ORDER BY donation_date DESC;

-- Insertion de données de test (optionnel - à supprimer en production)
-- INSERT INTO donations (transaction_id, first_name, last_name, email, phone, amount, payment_method, status) 
-- VALUES 
-- ('TEST001', 'Jean', 'Dupont', 'jean@example.com', '+229123456789', 5000.00, 'fedapay', 'completed'),
-- ('TEST002', 'Marie', 'Martin', 'marie@example.com', '+229987654321', 10000.00, 'kkiapay', 'completed');

-- Afficher les tables créées
SHOW TABLES;
