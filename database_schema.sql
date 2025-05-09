-- Create database
CREATE DATABASE IF NOT EXISTS designhive_db;
USE designhive_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    nis VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lessons table
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bab INT NOT NULL,
    subbab INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    media_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY bab_subbab (bab, subbab)
);

-- Progress tracking table
CREATE TABLE progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    phase_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY user_lesson (user_id, lesson_id)
);

-- Quizzes table
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bab INT NOT NULL,
    quiz_order INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration INT NOT NULL, -- in minutes
    is_final_exam BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY bab_order (bab, quiz_order)
);

-- Quiz questions table
CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_type ENUM('multiple_choice', 'drag_drop', 'matching') NOT NULL,
    question_text TEXT NOT NULL,
    options JSON NOT NULL, -- Store options/choices
    correct_answer JSON NOT NULL, -- Store correct answer(s)
    points INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz results table
CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    user_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    answers JSON NOT NULL, -- Store user's answers
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Assignments table
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    user_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    grade INT NULL,
    feedback TEXT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_at TIMESTAMP NULL,
    graded_by INT NULL,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Forum threads table
CREATE TABLE forum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bab INT NOT NULL,
    thread_title VARCHAR(255) NOT NULL,
    thread_content TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Forum comments table
CREATE TABLE forum_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    forum_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (forum_id) REFERENCES forum(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Certificates table
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL, -- Reference to the final exam
    score DECIMAL(5,2) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    UNIQUE KEY user_quiz (user_id, quiz_id)
);

-- Insert sample teacher account
INSERT INTO users (name, nis, password, role) VALUES 
('Admin Guru', 'TEACHER001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

-- Insert sample lessons
INSERT INTO lessons (bab, subbab, title, content) VALUES
(1, 1, 'Pengenalan Desain Grafis', 'Desain grafis adalah seni komunikasi visual yang menggunakan gambar untuk menyampaikan pesan atau informasi kepada audiens.'),
(1, 2, 'Elemen Dasar Desain', 'Elemen dasar desain meliputi garis, bentuk, warna, tekstur, dan ruang.'),
(2, 1, 'Teori Warna', 'Pemahaman tentang warna adalah fundamental dalam desain grafis. Ini mencakup color wheel, skema warna, dan psikologi warna.'),
(3, 1, 'Tipografi', 'Tipografi adalah seni dan teknik mengatur huruf. Ini mencakup pemilihan font, spacing, dan hierarchy.');

-- Insert sample quizzes
INSERT INTO quizzes (bab, quiz_order, title, duration) VALUES
(1, 1, 'Kuis Pengenalan Desain Grafis', 30),
(2, 1, 'Kuis Teori Warna', 30),
(3, 1, 'Kuis Tipografi', 30);
