-- Create database then import:
-- CREATE DATABASE ramadhan_book CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE ramadhan_book;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  roles VARCHAR(100) NOT NULL,
  nip VARCHAR(30) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS class_rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  year INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  class_room_id INT NOT NULL,
  nis VARCHAR(30) NOT NULL UNIQUE,
  gender ENUM('L','P') NULL,
  phone VARCHAR(30) NULL,
  address VARCHAR(255) NULL,
  CONSTRAINT fk_sp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_sp_class FOREIGN KEY (class_room_id) REFERENCES class_rooms(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS class_homerooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_room_id INT NOT NULL UNIQUE,
  homeroom_user_id INT NOT NULL,
  CONSTRAINT fk_ch_class FOREIGN KEY (class_room_id) REFERENCES class_rooms(id) ON DELETE CASCADE,
  CONSTRAINT fk_ch_user FOREIGN KEY (homeroom_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS religion_teacher_classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_room_id INT NOT NULL,
  teacher_user_id INT NOT NULL,
  UNIQUE KEY uniq_pair (class_room_id, teacher_user_id),
  CONSTRAINT fk_rtc_class FOREIGN KEY (class_room_id) REFERENCES class_rooms(id) ON DELETE CASCADE,
  CONSTRAINT fk_rtc_user FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journals (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  student_user_id INT NOT NULL,
  date DATE NOT NULL,
  shubuh TINYINT(1) DEFAULT 0,
  dzuhur TINYINT(1) DEFAULT 0,
  ashar TINYINT(1) DEFAULT 0,
  maghrib TINYINT(1) DEFAULT 0,
  isya TINYINT(1) DEFAULT 0,
  tarawih TINYINT(1) DEFAULT 0,
  witir TINYINT(1) DEFAULT 0,
  tadarus_pages INT DEFAULT 0,
  fasting TINYINT(1) DEFAULT 0,
  notes VARCHAR(255) NULL,
  UNIQUE KEY uniq_journal (student_user_id, date),
  CONSTRAINT fk_j_user FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sermon_notes (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  student_user_id INT NOT NULL,
  date DATE NOT NULL,
  title VARCHAR(120) NULL,
  content TEXT NULL,
  UNIQUE KEY uniq_sermon (student_user_id, date),
  CONSTRAINT fk_sn_user FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS good_deeds (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  student_user_id INT NOT NULL,
  date DATE NOT NULL,
  charity_amount INT DEFAULT 0,
  social_activity TEXT NULL,
  reflection TEXT NULL,
  UNIQUE KEY uniq_gooddeed (student_user_id, date),
  CONSTRAINT fk_gd_user FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS extra_activities (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  student_user_id INT NOT NULL,
  date DATE NOT NULL,
  pondok_ramadhan TEXT NULL,
  ziarah TEXT NULL,
  idulfitri_prep TEXT NULL,
  UNIQUE KEY uniq_extra (student_user_id, date),
  CONSTRAINT fk_ea_user FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed admin (password: admin123) - IMPORTANT: password_hash is placeholder, run seed.php after import.
-- INSERT INTO users(name, username, password_hash, role, is_active) VALUES ('Admin', 'admin', 'REPLACE_WITH_BCRYPT', 'admin', 1);
