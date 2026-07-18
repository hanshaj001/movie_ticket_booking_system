CREATE DATABASE movie_ticket_booking_system;
USE movie_ticket_booking_system;

-- =========================================================
-- ROLES TABLE
-- =========================================================
CREATE TABLE roles (
   role_id INT AUTO_INCREMENT PRIMARY KEY,
   role_name VARCHAR(50) UNIQUE NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- USERS TABLE
-- =========================================================
CREATE TABLE users (
   user_id INT AUTO_INCREMENT PRIMARY KEY,
   full_name VARCHAR(100) NOT NULL,
   email VARCHAR(100) UNIQUE NOT NULL,
   phone VARCHAR(15) UNIQUE NOT NULL,
   password_hash VARCHAR(255) NOT NULL,
   account_status ENUM('ACTIVE', 'BLOCKED') DEFAULT 'ACTIVE',
   last_login DATETIME NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- USER ROLES TABLE (Many-to-many relation)
-- =========================================================
CREATE TABLE user_roles (
   user_role_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   role_id INT NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
   FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
   UNIQUE(user_id, role_id)
);

-- =========================================================
-- MOVIES TABLE
-- =========================================================
CREATE TABLE movies (
   movie_id INT AUTO_INCREMENT PRIMARY KEY,
   title VARCHAR(150) NOT NULL,
   description TEXT,
   duration_minutes INT NOT NULL,
   language VARCHAR(50),
   release_date DATE,
   movie_format ENUM('2D', '3D') NOT NULL,
   poster_url VARCHAR(255),
   banner_url VARCHAR(255) NULL AFTER poster_url,
   status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- GENRES TABLE (Normalized Master Table)
-- =========================================================
CREATE TABLE genres (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- MOVIE_GENRES BRIDGE TABLE
-- =========================================================
CREATE TABLE movie_genres (
    movie_genre_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    genre_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE,
    UNIQUE(movie_id, genre_id)
);

-- =========================================================
-- SCREENS TABLE
-- =========================================================
CREATE TABLE screens (
   screen_id INT AUTO_INCREMENT PRIMARY KEY,
   screen_name VARCHAR(50) UNIQUE NOT NULL,
   total_seats INT NOT NULL,
   screen_status ENUM('ACTIVE', 'INACTIVE', 'MAINTENANCE') DEFAULT 'ACTIVE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- SEATS TABLE (Static layout configuration)
-- =========================================================
CREATE TABLE seats (
   seat_id INT AUTO_INCREMENT PRIMARY KEY,
   screen_id INT NOT NULL,
   seat_number VARCHAR(10) NOT NULL,
   seat_type ENUM('VIP', 'REGULAR') DEFAULT 'REGULAR',
   row_group VARCHAR(20),
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (screen_id) REFERENCES screens(screen_id) ON DELETE CASCADE,
   UNIQUE(screen_id, seat_number)
);

-- =========================================================
-- SHOWS TABLE
-- =========================================================
CREATE TABLE shows (
   show_id INT AUTO_INCREMENT PRIMARY KEY,
   movie_id INT NOT NULL,
   screen_id INT NOT NULL,
   show_date DATE NOT NULL,
   show_time TIME NOT NULL,
   ticket_price DECIMAL(10,2) NOT NULL,
   show_status ENUM('ACTIVE', 'COMPLETED', 'CANCELLED') DEFAULT 'ACTIVE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
   FOREIGN KEY (screen_id) REFERENCES screens(screen_id) ON DELETE CASCADE
);

-- =========================================================
-- SHOW SEATS TABLE (Dynamic seat availability state)
-- =========================================================
CREATE TABLE show_seats (
   show_seat_id INT AUTO_INCREMENT PRIMARY KEY,
   show_id INT NOT NULL,
   seat_id INT NOT NULL,
   seat_status ENUM('AVAILABLE', 'LOCKED', 'SOLD') DEFAULT 'AVAILABLE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (show_id) REFERENCES shows(show_id) ON DELETE CASCADE,
   FOREIGN KEY (seat_id) REFERENCES seats(seat_id) ON DELETE CASCADE,
   UNIQUE(show_id, seat_id)
);

-- =========================================================
-- BOOKING SESSIONS TABLE
-- =========================================================
CREATE TABLE booking_sessions (
   session_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   show_id INT NOT NULL,
   session_start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
   expiry_time DATETIME NOT NULL,
   session_status ENUM('ACTIVE', 'EXPIRED', 'COMPLETED') DEFAULT 'ACTIVE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
   FOREIGN KEY (show_id) REFERENCES shows(show_id) ON DELETE CASCADE
);

-- =========================================================
-- SEAT LOCKS TABLE
-- =========================================================
CREATE TABLE seat_locks (
   lock_id INT AUTO_INCREMENT PRIMARY KEY,
   session_id INT NOT NULL,
   show_seat_id INT NOT NULL,
   locked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
   expiry_time DATETIME NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (session_id) REFERENCES booking_sessions(session_id) ON DELETE CASCADE,
   FOREIGN KEY (show_seat_id) REFERENCES show_seats(show_seat_id) ON DELETE CASCADE,
   UNIQUE(show_seat_id)
);

-- =========================================================
-- BOOKINGS TABLE
-- =========================================================
CREATE TABLE bookings (
   booking_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   show_id INT NOT NULL,
   total_seats INT NOT NULL,
   total_amount DECIMAL(10,2) NOT NULL,
   booking_time DATETIME DEFAULT CURRENT_TIMESTAMP,
   booking_status ENUM('CONFIRMED', 'CANCELLED') DEFAULT 'CONFIRMED',
   cancellation_time DATETIME NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
   FOREIGN KEY (show_id) REFERENCES shows(show_id) ON DELETE CASCADE
);

-- =========================================================
-- BOOKING DETAILS TABLE (Updated for partial cancellation)
-- =========================================================
CREATE TABLE booking_details (
   booking_detail_id INT AUTO_INCREMENT PRIMARY KEY,
   booking_id INT NOT NULL,
   show_seat_id INT NOT NULL,
   ticket_price DECIMAL(10,2) NOT NULL,
   item_status ENUM('CONFIRMED', 'CANCELLED') DEFAULT 'CONFIRMED',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
   FOREIGN KEY (show_seat_id) REFERENCES show_seats(show_seat_id) ON DELETE CASCADE,
   UNIQUE(show_seat_id)
);

-- =========================================================
-- LEDGER TABLE
-- =========================================================
CREATE TABLE ledger (
    ledger_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    movie_id INT NOT NULL,
    show_id INT NOT NULL,
    transaction_type ENUM('BOOKING','CANCELLATION') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarks VARCHAR(255) NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id),
    FOREIGN KEY (show_id) REFERENCES shows(show_id)
);


-- Partial Seat Cancellation Migration
-- Run this script once on the movie_ticket_booking_system database

USE movie_ticket_booking_system;

-- 1. Add PARTIALLY_CANCELLED to bookings.booking_status enum
ALTER TABLE bookings 
  MODIFY COLUMN booking_status ENUM('CONFIRMED','PARTIALLY_CANCELLED','CANCELLED') DEFAULT 'CONFIRMED';

-- 2. Add seat-level cancellation tracking to booking_details
ALTER TABLE booking_details 
  ADD COLUMN seat_status ENUM('CONFIRMED','CANCELLED') DEFAULT 'CONFIRMED';

ALTER TABLE booking_details 
  ADD COLUMN cancellation_time DATETIME NULL;





CREATE TABLE contact_messages (

    message_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    full_name VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL,

    phone VARCHAR(20) NOT NULL,

    subject VARCHAR(200) NOT NULL,

    message TEXT NOT NULL,

    status ENUM(
        'NEW',
        'READ',
        'REPLIED',
        'CLOSED'
    ) DEFAULT 'NEW',

    priority ENUM(
        'LOW',
        'MEDIUM',
        'HIGH'
    ) DEFAULT 'MEDIUM',

    assigned_to INT NULL,

    admin_reply TEXT NULL,

    replied_at DATETIME NULL,

    ip_address VARCHAR(45) NULL,

    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    FOREIGN KEY (assigned_to)
        REFERENCES users(user_id)
        ON DELETE SET NULL

);