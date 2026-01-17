-- Create Database
CREATE DATABASE IF NOT EXISTS myportfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE myportfolio;

-- User Preferences Table (for storing dark mode and other user preferences)
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL UNIQUE,
    dark_mode BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projects Table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(500),
    technologies JSON,
    category VARCHAR(100),
    featured BOOLEAN DEFAULT FALSE,
    github_url VARCHAR(500),
    live_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_featured (featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Skills Table
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(100),
    proficiency INT DEFAULT 0,
    icon VARCHAR(100),
    years_of_experience INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Testimonials Table
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255),
    company VARCHAR(255),
    content TEXT NOT NULL,
    avatar VARCHAR(500),
    rating INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact Submissions Table
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'unsubscribed') DEFAULT 'active',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate Limiting Table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_endpoint (ip_address, endpoint),
    INDEX idx_window_start (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Sessions Table
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Data for Projects
INSERT INTO projects (title, description, image, technologies, category, featured, github_url, live_url) VALUES
('E-Commerce Platform', 'A full-featured online shopping platform with payment integration', 'https://via.placeholder.com/400x300', '["Angular", "Node.js", "MongoDB", "Stripe"]', 'Web App', TRUE, 'https://github.com/example/ecommerce', 'https://example.com'),
('Task Management App', 'A collaborative task management tool for teams', 'https://via.placeholder.com/400x300', '["React", "Firebase", "Material-UI"]', 'Web App', TRUE, 'https://github.com/example/taskapp', 'https://example.com'),
('Weather Dashboard', 'Real-time weather information with interactive maps', 'https://via.placeholder.com/400x300', '["Vue.js", "OpenWeather API", "Chart.js"]', 'Web App', FALSE, 'https://github.com/example/weather', 'https://example.com');

-- Insert Sample Data for Skills
INSERT INTO skills (name, category, proficiency, icon, years_of_experience) VALUES
('Angular', 'Frontend', 90, 'bi-filetype-js', 5),
('React', 'Frontend', 85, 'bi-filetype-jsx', 4),
('Node.js', 'Backend', 88, 'bi-server', 5),
('Python', 'Backend', 80, 'bi-code-slash', 3),
('MySQL', 'Database', 85, 'bi-database', 4),
('MongoDB', 'Database', 82, 'bi-database-fill', 3),
('Docker', 'DevOps', 75, 'bi-box', 2),
('Git', 'Tools', 90, 'bi-git', 6);

-- Insert Sample Data for Testimonials
INSERT INTO testimonials (name, position, company, content, avatar, rating) VALUES
('John Doe', 'CEO', 'Tech Corp', 'Excellent work! Delivered the project on time and exceeded expectations.', 'https://via.placeholder.com/100', 5),
('Jane Smith', 'Project Manager', 'Digital Agency', 'Great communication and technical skills. Highly recommended!', 'https://via.placeholder.com/100', 5),
('Mike Johnson', 'CTO', 'StartUp Inc', 'Outstanding developer with great problem-solving abilities.', 'https://via.placeholder.com/100', 5);
