MOVIE TICKET BOOKING SYSTEM
System Documentation
 
Executive Summary
The Movie Ticket Booking System is a web-based software application developed to automate movie scheduling, seat management, and online ticket booking operations for a single cinema location with multiple movie screens.
The system allows visitors to browse available movies, view show schedules, ticket prices, movie details, and seat availability without authentication. Registered customers can select seats and confirm bookings using a session-based booking mechanism with temporary seat locking and concurrency control.
The system ensures accurate seat allocation, prevents double booking, maintains real-time seat availability, and efficiently manages booking session expiry and ticket cancellation operations.
The application uses a normalized relational database structure with Role-Based Access Control (RBAC), where users, roles, and user-role mappings are managed using separate entities.
 
The project focuses mainly on:
•       Movie scheduling
•       Real-time seat management
•       Temporary seat locking
•       Booking session management
•       Concurrency handling
•       Transaction consistency
•       Role-based authorization
•       Booking lifecycle management
•       Real-time seat availability
 
System Objectives
Main Objectives
•       Develop a web-based movie ticket booking platform
•       Allow visitors to browse movies and seat availability without login
•       Allow registered customers to book movie tickets online
•       Implement scalable Role-Based Access Control (RBAC)
•       Support multiple roles for a single user
•       Manage movie schedules and show timings efficiently
•       Prevent double booking using concurrency control
•       Implement temporary seat locking during booking sessions
•       Maintain real-time seat availability  
•       Automate booking and cancellation operations
•       Store and manage booking records properly
•       Generate seats automatically for each movie show
•       Handle booking session expiry automatically
•       Maintain normalized relational database structure
•       Improve overall cinema booking management
 
Admin (Cinema Manager)
Responsible for:
•       Managing movies
•       Creating movie show schedules
•       Managing ticket pricing
•       Monitoring booking records
•       Monitoring seat availability
•       Managing movie status
•       Managing show schedules
 
Customer
Responsible for:
•       Registering and logging into the system
•       Browsing available movies
•       Viewing movie details
•       Viewing show schedules
•       Viewing seat availability
•       Selecting movie shows
•       Choosing available seats
•       Confirming bookings within allowed session duration
•       Viewing booking history
•       Cancelling bookings before the allowed cancellation period
 
Visitor Access
Visitors who are not logged into the system can:
•       Browse available movies
•       View movie details
•       View show schedules
•       View ticket prices
•       View movie formats
•       View seat availability
•       View seat layout
 
When a visitor clicks an available seat:
•       The system checks whether the user is authenticated.
•       If the user is not logged in — the system redirects to the Login/Register page; no booking session or seat lock is created.
•       After successful login — the user returns to the selected show and seat selection continues normally.
 
High-Level System Flow
User Registration and Authentication
•       Visitors can browse available movies, show schedules, ticket prices, movie formats, and seat availability without logging in.
•       Customers register themselves; credentials stored in the Users table.
•       Roles are managed using RBAC tables; user-role mappings stored in the User_Roles table.
•       Users log in using a shared authentication system; the system validates credentials and retrieves assigned roles.
•       The system redirects users based on assigned roles.
 
Admin Movie and Show Management
Admin logs into the dashboard and manages:
•       Movie details
•       Show schedules 
•       Ticket pricing
•       Seat monitoring
•       Booking records
 
Movie details include:
•       Movie title
•       Description
•       Duration
•       Genre
•       Language
•       Poster / Image
•       Movie format
•       Release date
 
Movie formats include:
•       2D
•       3D
Movie formats are stored using ENUM.
 
Screen Management
•       The cinema contains multiple movie screens.
Each screen contains:
•       Screen name
•       Total seats
•       Screen status
•       All screens use the same seat structure.
 
Show Creation
Admin creates movie shows by selecting:
•       Movie
•       Screen
•       Show date
•       Show time
•       Ticket pricing
 
•       Shows can only be created for upcoming dates.
•       Overlapping movie shows on the same screen are prevented.
•       A single screen cannot run multiple movie shows at overlapping times.
•       Each show belongs to one screen.
 
Ticket Pricing
Ticket pricing may vary based on:
•       Movie format (2D / 3D)
•       Seat type (VIP / Regular)
Example: VIP 3D seats cost more than Regular 2D seats.
 
Seat Generation
When a new show is created:
•       System automatically generates seats for that show.
•       Static seat records are reused across multiple shows.
•       Seat availability is managed using the Show_Seats entity.
 
Seat Structure
Each seat contains:
•       Seat number
•       Seat type
•       Row group
 
Seat types:  VIP  |  Regular
Row groups:  Group 1  |  Group 2  |  Group 3
 
Example:
•       A1 — VIP — Group 1
•       A2 — VIP — Group 1
•       B1 — Regular — Group 2
•       C1 — Regular — Group 3
Initial seat state: AVAILABLE
 
Movie Browsing
Visitors and customers can:
•       Browse movies
•       View movie details
•       View show dates
•       View show times
•       View movie formats
•       View ticket prices
•       View real-time seat availability
•       View seat layout
 
Only authenticated customers can:
•       Select seats
•       Start booking sessions
•       Lock seats
•       Confirm bookings
 
Customer selects: Movie → Showtime → Seats
 
Booking Session Management
When an authenticated customer selects an available seat:
•       A temporary booking session is created.
•       The selected seat is locked temporarily.
•       The session timer starts automatically.
•       Customers can continue selecting seats during the active session.
 
Session Duration
Booking session duration is limited to 5 minutes.
 
Session Expiry Rules
If booking is not completed within the session duration:
•       Booking session expires automatically.
•       Related seat locks are removed.
•       Locked seats become AVAILABLE again.
•       Customer must restart the booking process.
 
Seat Selection and Booking Process
Seat States
•       AVAILABLE — Seat is free for booking.
•       LOCKED — Seat is temporarily reserved.
•       SOLD — Booking confirmed successfully.
SELECTED is a frontend-only UI state and is not permanently stored in the database.
 
Booking Status
•       CONFIRMED — Booking completed successfully.
•       CANCELLED — Booking cancelled by customer.
•       EXPIRED — Booking session expired before confirmation.
 
Booking Flow
Step 1: Authentication Validation
•       System verifies whether the user is logged in.
•       Unauthenticated users are redirected to the Login/Register page.
•       Seat selection is allowed only for authenticated customers.
Step 2: Seat Selection
•       Customer selects seats.
•       Selected seats are highlighted in the user interface.
Step 3: Temporary Seat Locking
•       System temporarily locks selected seats.
Seat state changes: AVAILABLE → LOCKED
•       Seat locks are stored in the Seat_Locks table.
Step 4: Session Validation
System validates:
•       Booking session validity
•       Seat availability
•       Existing booking conflicts
•       Active seat locks
Step 5: Booking Confirmation
•       Customer confirms booking.
•       Booking record is created.
•       Booked seats are linked using Booking_Details.
Step 6: Booking Completion
•       Booking completes successfully.
Seat state changes: LOCKED → SOLD
 
Concurrency Control
The system prevents multiple users from booking the same seat simultaneously.
 
System Behavior
If multiple users attempt to select the same seat:
•       The seat is temporarily locked for the first active booking session.
•       Other users cannot reserve the same seat while it is locked.
•       Only the first confirmed booking succeeds.
 
Concurrency Features
The system ensures:
•       No double booking
•       Consistent seat allocation
•       Accurate seat status updates
•       Safe concurrent booking operations
•       Proper transaction handling
The system uses temporary seat locking and transaction validation to maintain booking consistency during concurrent operations.
 
Time-Based Booking Rules
Booking Availability
Customers can book tickets only before the movie show starts.
 
Session Timeout
If booking is not completed within the session duration:
•       Booking session expires automatically.
•       No seats are sold.
•       Temporary seat locks are removed.
•       Seats return to AVAILABLE state.
 
Booking Cancellation Rules
Cancellation Conditions
•       Customers can cancel bookings only before 30 minutes of the movie show start time.
•       Bookings cannot be cancelled within 30 minutes of showtime.
 
Cancellation Validation — the system validates:
•       Current date and time
•       Movie show start time
•       Booking status
•       Cancellation eligibility
 
Cancellation Flow
Seat: SOLD → AVAILABLE   |   Booking Status: CONFIRMED → CANCELLED
 
Seat Lifecycle
Booking Lifecycle:  AVAILABLE → LOCKED → SOLD
Cancellation Lifecycle:  SOLD → AVAILABLE
 
Database Table Descriptions
- =========================================================
-- CREATE DATABASE
-- =========================================================
create DATABASE movie_ticket_booking_system;
USE movie_ticket_booking_system;
-- =========================================================
-- ROLES TABLE
-- Stores system roles
-- Example: ADMIN, CUSTOMER
-- =========================================================
CREATE TABLE roles (
   role_id INT AUTO_INCREMENT PRIMARY KEY,
   role_name VARCHAR(50) UNIQUE NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ON UPDATE CURRENT_TIMESTAMP
);
-- =========================================================
-- USERS TABLE
-- Stores user account information
-- =========================================================
CREATE TABLE users (
   user_id INT AUTO_INCREMENT PRIMARY KEY,
   full_name VARCHAR(100) NOT NULL,
   email VARCHAR(100) UNIQUE NOT NULL,
   phone VARCHAR(15) UNIQUE NOT NULL,
   password_hash VARCHAR(255) NOT NULL,
   account_status ENUM(
       'ACTIVE',
       'BLOCKED'
   ) DEFAULT 'ACTIVE',
   last_login DATETIME NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ON UPDATE CURRENT_TIMESTAMP
);
-- =========================================================
-- USER ROLES TABLE
-- Many-to-many relation between users and roles
-- =========================================================
CREATE TABLE user_roles (
   user_role_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   role_id INT NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id)
   REFERENCES users(user_id)
   ON DELETE CASCADE,
   FOREIGN KEY (role_id)
   REFERENCES roles(role_id)
   ON DELETE CASCADE,
   UNIQUE(user_id, role_id)
);
-- =========================================================
-- MOVIES TABLE
-- Stores movie details
-- =========================================================
CREATE TABLE movies (
   movie_id INT AUTO_INCREMENT PRIMARY KEY,
   title VARCHAR(150) NOT NULL,
   description TEXT,
   duration_minutes INT NOT NULL,
   genre VARCHAR(100),
   language VARCHAR(50),
   release_date DATE,
   movie_format ENUM(
       '2D',
       '3D'
   ) NOT NULL,
   poster_url VARCHAR(255),
   status ENUM(
       'ACTIVE',
       'INACTIVE'
   ) DEFAULT 'ACTIVE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ON UPDATE CURRENT_TIMESTAMP
);
-- =========================================================
-- SCREENS TABLE
-- Stores cinema screens
-- =========================================================
CREATE TABLE screens (
   screen_id INT AUTO_INCREMENT PRIMARY KEY,
   screen_name VARCHAR(50) UNIQUE NOT NULL,
   total_seats INT NOT NULL,
   screen_status ENUM(
       'ACTIVE',
       'INACTIVE',
       'MAINTENANCE'
   ) DEFAULT 'ACTIVE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ON UPDATE CURRENT_TIMESTAMP
);
-- =========================================================
-- SEATS TABLE
-- Static seat structure
-- =========================================================
CREATE TABLE seats (
   seat_id INT AUTO_INCREMENT PRIMARY KEY,
   screen_id INT NOT NULL,
   seat_number VARCHAR(10) NOT NULL,
   seat_type ENUM(
       'VIP',
       'REGULAR'
   ) DEFAULT 'REGULAR',
   row_group VARCHAR(20),
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (screen_id)
   REFERENCES screens(screen_id)
   ON DELETE CASCADE,
   UNIQUE(screen_id, seat_number)
);
-- =========================================================
-- SHOWS TABLE
-- Stores movie show schedules
-- =========================================================
CREATE TABLE shows (
   show_id INT AUTO_INCREMENT PRIMARY KEY,
   movie_id INT NOT NULL,
   screen_id INT NOT NULL,
   show_date DATE NOT NULL,
   show_time TIME NOT NULL,
   ticket_price DECIMAL(10,2) NOT NULL,
   show_status ENUM(
       'ACTIVE',
       'COMPLETED',
       'CANCELLED'
   ) DEFAULT 'ACTIVE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (movie_id)
   REFERENCES movies(movie_id)
   ON DELETE CASCADE,
   FOREIGN KEY (screen_id)
   REFERENCES screens(screen_id)
   ON DELETE CASCADE
);
-- =========================================================
-- SHOW SEATS TABLE
-- Stores seat availability for each show
-- =========================================================
CREATE TABLE show_seats (
   show_seat_id INT AUTO_INCREMENT PRIMARY KEY,
   show_id INT NOT NULL,
   seat_id INT NOT NULL,
   seat_status ENUM(
       'AVAILABLE',
       'LOCKED',
       'SOLD'
   ) DEFAULT 'AVAILABLE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (show_id)
   REFERENCES shows(show_id)
   ON DELETE CASCADE,
   FOREIGN KEY (seat_id)
   REFERENCES seats(seat_id)
   ON DELETE CASCADE,
   UNIQUE(show_id, seat_id)
);
-- =========================================================
-- BOOKING SESSIONS TABLE
-- Temporary booking sessions
-- =========================================================
CREATE TABLE booking_sessions (
   session_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   show_id INT NOT NULL,
   session_start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
   expiry_time DATETIME NOT NULL,
   session_status ENUM(
       'ACTIVE',
       'EXPIRED',
       'COMPLETED'
   ) DEFAULT 'ACTIVE',
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id)
   REFERENCES users(user_id)
   ON DELETE CASCADE,
   FOREIGN KEY (show_id)
   REFERENCES shows(show_id)
   ON DELETE CASCADE
);
-- =========================================================
-- SEAT LOCKS TABLE
-- Temporarily locked seats
-- =========================================================
CREATE TABLE seat_locks (
   lock_id INT AUTO_INCREMENT PRIMARY KEY,
   session_id INT NOT NULL,
   show_seat_id INT NOT NULL,
   locked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
   expiry_time DATETIME NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (session_id)
   REFERENCES booking_sessions(session_id)
   ON DELETE CASCADE,
   FOREIGN KEY (show_seat_id)
   REFERENCES show_seats(show_seat_id)
   ON DELETE CASCADE,
   UNIQUE(show_seat_id)
);
-- =========================================================
-- BOOKINGS TABLE
-- Stores booking transactions
-- =========================================================
CREATE TABLE bookings (
   booking_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   show_id INT NOT NULL,
   total_seats INT NOT NULL,
   total_amount DECIMAL(10,2) NOT NULL,
   booking_time DATETIME DEFAULT CURRENT_TIMESTAMP,
   booking_status ENUM(
       'CONFIRMED',
       'CANCELLED'
   ) DEFAULT 'CONFIRMED',
   cancellation_time DATETIME NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ON UPDATE CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id)
   REFERENCES users(user_id)
   ON DELETE CASCADE,
   FOREIGN KEY (show_id)
   REFERENCES shows(show_id)
   ON DELETE CASCADE
);
-- =========================================================
-- BOOKING DETAILS TABLE
-- Stores booked seat details
-- =========================================================
CREATE TABLE booking_details (
   booking_detail_id INT AUTO_INCREMENT PRIMARY KEY,
   booking_id INT NOT NULL,
   show_seat_id INT NOT NULL,
   ticket_price DECIMAL(10,2) NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (booking_id)
   REFERENCES bookings(booking_id)
   ON DELETE CASCADE,
   FOREIGN KEY (show_seat_id)
   REFERENCES show_seats(show_seat_id)
   ON DELETE CASCADE,
   UNIQUE(show_seat_id)
 
);
-- for initial users and seat format
USE movie_ticket_booking_system;
INSERT INTO roles (role_name)
VALUES
('ADMIN'),
('CUSTOMER');
INSERT INTO users
(
   full_name,
   email,
   phone,
   password_hash,
   account_status
)
VALUES
(
   ' Admin',
   'admin@gmail.com',
   '9800000001',
   'admin123',
   'ACTIVE'
),
(
   'Customer',
   'customer@gmail.com',
   '9800000002',
   'customer123',
   'ACTIVE'
);
select * from users;
INSERT INTO user_roles(user_id,role_id)
VALUES
(1,1),
(2,2);
INSERT INTO screens
(
   screen_name,
   total_seats,
   screen_status
)
VALUES
(
   'Screen 1',
   12,
   'ACTIVE'
),
(
   'Screen 2',
   12,
   'ACTIVE'
);
INSERT INTO seats
(
   screen_id,
   seat_number,
   seat_type,
   row_group
)
VALUES
(1,'A1','VIP','Group 1'),
(1,'A2','VIP','Group 1'),
(1,'A3','VIP','Group 1'),
(1,'A4','VIP','Group 1'),
(1,'B1','REGULAR','Group 2'),
(1,'B2','REGULAR','Group 2'),
(1,'B3','REGULAR','Group 2'),
(1,'B4','REGULAR','Group 2'),
(1,'C1','REGULAR','Group 3'),
(1,'C2','REGULAR','Group 3'),
(1,'C3','REGULAR','Group 3'),
(1,'C4','REGULAR','Group 3');
INSERT INTO seats
(
   screen_id,
   seat_number,
   seat_type,
   row_group
)
VALUES
(2,'A1','VIP','Group 1'),
(2,'A2','VIP','Group 1'),
(2,'A3','VIP','Group 1'),
(2,'A4','VIP','Group 1'),
(2,'B1','REGULAR','Group 2'),
(2,'B2','REGULAR','Group 2'),
(2,'B3','REGULAR','Group 2'),
(2,'B4','REGULAR','Group 2'),
(2,'C1','REGULAR','Group 3'),
(2,'C2','REGULAR','Group 3'),
(2,'C3','REGULAR','Group 3'),
(2,'C4','REGULAR','Group 3');

•       Users — Stores user account details, authentication information, and profile data.
•       Roles — Stores system role definitions (ADMIN, CUSTOMER).
•       User_Roles — Bridge table between Users and Roles; supports multiple roles per user.
•       Movies — Stores movie information, descriptions, formats, duration, and status. Managed using active/inactive status instead of permanent deletion.
•       Screens — Stores screen information: screen names, total seats, and screen status.
•       Shows — Stores movie show schedules, screen assignment, pricing, and show timings. Managed using active/inactive status.
•       Seats — Stores static seat structure: seat number, seat type, and row group.
•       Show_Seats — Stores seat availability per movie show. Tracks AVAILABLE, LOCKED, and SOLD seats; separates static seat structure from dynamic booking states.
•       Booking_Sessions — Stores temporary booking activity during seat selection. Tracks session start/expiry time, status, and associated user.
•       Seat_Locks — Stores temporarily locked seats; prevents concurrent booking conflicts.
•       Bookings — Stores confirmed and cancelled booking transactions, including user info, status, and timestamps.
•       Booking_Details — Stores booked seat details and links bookings with booked seats.
 
Functional Requirements
Visitor Functional Requirements
•       Browse movies
•       View movie details
•       View available showtimes
•       View movie formats
•       View ticket prices
•       View seat availability
•       View seat layout
 
Customer Functional Requirements
•       User registration
•       User login
•       Role-based authentication
•       Browse movies
•       View movie details
•       View available showtimes
•       Select and unselect seats
•       Start booking session automatically
•       Confirm bookings
•       Cancel bookings
•       View booking history
 
Admin Functional Requirements
•       Admin login
•       Add movies
•       Update movie details
•       Manage movie status
•       Create movie shows
•       Manage ticket pricing
•       Monitor bookings
•       Monitor seat availability
•       Manage screens
 
System Functional Requirements
•       Automatic seat generation
•       Seat locking mechanism
•       Session timer management
•       Real-time seat updates
•       Booking validation
•       Concurrency control
•       Booking expiry handling
•       Seat state management
•       Booking history management
•       Cancellation handling
•       Prevent duplicate bookings
•       Prevent booking of locked or sold seats
•       Role-based access control
•       Transaction consistency management
 
Main System Modules
•       User Management Module — User registration, user authentication, RBAC role management, login management.
•       Movie Management Module — Movie creation, movie updates, movie status management, movie listing.
•       Customer Module — Movie browsing, seat booking, booking history, booking cancellation.
•       Admin Module — Movie management, show management, booking monitoring, seat monitoring, screen management.
•       Booking Module — Booking session management, seat locking, booking confirmation, booking cancellation, concurrency handling, session expiry handling.
 
Project Scope and Limitations
Technology Stack
•       HTML
•       CSS
•       JavaScript
•       AJAX
•       PHP
•       MySQL
 
Primary Focus Areas
•       Movie management
•       Movie scheduling
•       Seat booking
•       Seat locking
•       Concurrency handling
•       Booking session management
•       Real-time seat availability
•       Role-based authorization
 
Features Not Included
•       Online payment gateway
•       Email notifications
•       SMS notifications
•       QR code ticket generation
•       Multi-cinema support
•       Mobile application
•       AI-based movie recommendations
•       Third-party login integration
•       Advanced analytics dashboard
•       Wallet / refund management
•       Cloud distributed deployment

The project is limited to a single cinema location with multiple movie screens.
 
Conclusion
The Movie Ticket Booking System provides an organized and efficient platform for cinema management and online ticket booking.
The system allows visitors to browse movies, show schedules, ticket prices, and seat availability without authentication, while registered customers can securely select seats and complete bookings after login.
 
The system combines:
•       Role-Based Access Control (RBAC)
•       Seat locking mechanisms
•       Session-based booking control
•       Concurrency handling
•       Real-time seat availability
...to ensure accurate and reliable booking operations.
 
The system prevents double booking through temporary seat locking and transaction validation while maintaining consistent seat allocation during concurrent operations.
By implementing booking lifecycle management, session expiry handling, cancellation control, database normalization, and scalable authorization management, the system provides reliable and efficient movie booking functionality suitable for real-world cinema operations.
The solution offers a practical and maintainable architecture for managing movie schedules, seat allocation, and customer bookings within a single cinema environment while ensuring transaction consistency and accurate seat availability at all times.


