1. Executive Summary
The Movie Ticket Booking System is a web-based application developed to manage movie scheduling, seat booking, and booking records for a single cinema with multiple movie screens. The system provides separate interfaces for customers and administrators using Role-Based Access Control (RBAC).
Customers can create an account, log in, browse available movies, view show schedules, select available seats, confirm bookings, view booking history, and cancel eligible bookings (either fully or partially). A booking session is created when a customer selects the first available seat. The session remains active for five minutes. During the active session, selected seats are temporarily locked so that they cannot be selected by another customer. If the booking is confirmed before the session expires, the seats are marked as sold. If the session expires, the temporary seat locks are removed and the seats become available again.
Administrators manage movies, genres, show schedules, ticket prices, and booking records through the administration panel. Administrators also have the elevated privilege to cancel any customer's full or partial booking at any time before a show starts. When a new show is created, the system automatically creates seat records for that show based on the seat layout of the selected screen.
The application is developed using HTML, CSS, JavaScript, AJAX, PHP, and MySQL. The database follows a normalized relational design consisting of fourteen tables, including separate tables for users, roles, movies, genres, shows, bookings, booking sessions, and seat locks.
2. System Objectives
Main Objective
To develop a web-based movie ticket booking system that allows customers to book movie tickets and enables administrators to manage movies, shows, and bookings within a single cinema.
Specific Objectives
Allow customers to register and log in.
Implement Role-Based Access Control (RBAC) for user authorization.
Allow administrators to manage movie information.
Allow administrators to manage movie genres.
Allow administrators to create movie shows.
Prevent overlapping shows on the same screen.
Automatically generate seat availability records for each new show.
Allow customers to view available movies and show schedules.
Allow customers to select available seats.
Create a booking session when seat selection begins.
Temporarily lock selected seats during the booking session.
Release locked seats when the booking session expires.
Store confirmed bookings and booked seats.
Allow customers to view booking history.
Allow customers to cancel full or partial bookings before the cancellation deadline.
Allow administrators to cancel full or partial bookings at any time before the show starts.
Maintain a normalized relational database structure.
3. Technology Stack
Component
Technology
Frontend
HTML5, CSS3, JavaScript
Asynchronous Requests
AJAX
Backend
PHP
Database
MySQL
Development Environment
XAMPP
Source Code Editor
Visual Studio Code

4. Project Scope
The Movie Ticket Booking System is designed for a single cinema that contains multiple movie screens. The system provides the following functionality:
Customer
Register an account.
Log in.
Browse available movies.
View movie details.
View available show dates and show times.
View seat availability.
Select available seats.
Confirm bookings.
View booking history.
Cancel eligible bookings (full or partial seat cancellations).
Log out.
Administrator
Log in.
Manage movies.
Manage genres.
Assign genres to movies.
Manage movie status.
Manage screens.
Manage movie shows.
Set ticket prices for shows.
View booking records.
Cancel customer bookings or individual seats before a show starts.
Log out.
System
Generate seat records for every newly created show.
Create booking sessions during seat selection.
Temporarily lock selected seats.
Release locked seats after session expiry.
Prevent multiple bookings for the same seat.
Dynamically recalculate totals during partial cancellations.
Maintain booking and transaction records.
5. Project Limitations
The current implementation includes the following limitations:
The system supports only one cinema location.
Online payment gateways are not implemented.
Email notifications are not implemented.
SMS notifications are not implemented.
QR code ticket generation is not implemented.
Mobile applications are not included.
Third-party login services such as Google or Facebook are not supported.
Movie recommendations are not provided.
Refund processing to external payment methods is not implemented (only internal system accounting).
Gift cards and wallet functionality are not included.
6. User Roles and Responsibilities
The system contains three types of users.
Visitor
A visitor is a user who has not logged in.
A visitor can:
Browse available movies.
View movie details.
View show schedules.
View seat availability.
A visitor cannot:
Select seats.
Create a booking session.
Confirm bookings.
View booking history.
Cancel bookings.
(If a visitor clicks an available seat, the system redirects the user to the login page. No booking session is created before successful authentication.)
Customer
A customer is an authenticated user assigned the CUSTOMER role.
A customer can:
Log in.
Browse available movies.
View movie details.
View available shows.
View seat availability.
Select available seats.
Confirm bookings.
View booking history.
Cancel bookings (full or partial) before the standard cancellation deadline.
Log out.
Administrator
An administrator is an authenticated user assigned the ADMIN role.
An administrator can:
Manage movies.
Manage genres.
Assign genres to movies.
Manage screens.
Create movie shows.
Set ticket prices.
View booking records.
View seat availability.
Cancel full or partial bookings at any point before the show starts.
Log out.
7. Functional Requirements
User Management
The system shall:
Allow user registration.
Allow user login.
Validate user credentials.
Assign system roles using Role-Based Access Control.
Prevent blocked users from logging in.
Movie Management
The system shall:
Add movies.
Update movie information.
Activate or deactivate movies.
Upload movie posters.
Upload movie banners.
Assign one or more genres to a movie.
Genre Management
The system shall:
Add genres.
Update genres.
Assign genres to movies.
Screen Management
The system shall:
Store screen information.
Store seat layouts for each screen.
Store screen status.
Show Management
The system shall:
Create movie shows.
Assign movies to screens.
Store ticket prices.
Prevent overlapping shows on the same screen.
Generate seat availability records for every newly created show.
Booking Management
The system shall:
Allow seat selection.
Create booking sessions.
Temporarily lock selected seats.
Confirm bookings.
Store booking information.
Store booked seat information.
Remove temporary seat locks after session expiry.
Prevent booking of locked seats.
Prevent booking of sold seats.
Booking History
The system shall:
Display booking history for each customer.
Display booking status.
Display booked seats.
Booking Cancellation
The system shall:
Allow cancellation of an entire booking or specific selected seats (partial cancellation).
Allow customers to cancel only before the standard cancellation deadline.
Allow administrators to cancel bookings or specific seats at any time before the show starts.
Update booking status and recalculate ticket totals after a partial cancellation.
Change cancelled seats back to AVAILABLE.
8. System Modules
User Management Module
Responsible for:
Registration
Login
User authentication
Role assignment
Customer Module
Responsible for:
Movie browsing
Viewing movie details
Viewing shows
Seat selection
Booking confirmation
Booking history
Booking cancellation (Full and Partial)
Administrator Module
Responsible for:
Movie management
Genre management
Screen management
Show management
Booking monitoring
Administrative booking cancellations
Booking Module
Responsible for:
Booking session creation
Seat locking
Booking confirmation
Booking cancellation (Handling both Full and Partial ticket voids)
Session expiry
Seat status updates
Manage Screens Module
System Definition
The Manage Screens module enables the administrator to manage the cinema screens available within the system. Since each movie show is assigned to a specific screen, this module provides the necessary functionality to create and maintain screen information used during show scheduling and seat allocation. The administrator can add new screens by specifying the screen name and status, update existing screen information, and modify the operational status of a screen. The supported screen statuses include ACTIVE, INACTIVE, and MAINTENANCE. The module also provides access to seat management for each screen.
Manage Seats Module
System Definition
The Manage Seats module allows the administrator to configure and maintain the seat layout for each cinema screen. Every seat stored in this module represents the physical seating arrangement of a particular screen and serves as the template for generating seat availability whenever a new movie show is created. The administrator can view all seats belonging to a selected screen, add new seats, edit seat information, and remove seats when necessary. When new seats are added, the system automatically continues the seat numbering sequence.
Manage Genres Module
System Definition
The Manage Genres module allows the administrator to maintain the collection of movie genres used throughout the system. Since a movie may belong to multiple genres and a genre may be assigned to multiple movies, this module supports the many-to-many relationship implemented through the Movie_Genres table. The administrator can create new genres, update existing genre names, view all available genres, and remove genres that are no longer required.
Earnings Module
System Definition
The Earnings module provides a summarized financial report of all movies available in the system. Its primary purpose is to allow administrators to monitor the revenue generated by individual movies without reviewing every booking transaction separately. The module displays summary information including the total number of scheduled shows, confirmed bookings, seats sold, and total earnings for each movie. It takes into account both full and partial cancellations automatically.
Movie Earnings Module
System Definition
The Movie Earnings module provides a detailed financial report for an individual movie selected from the Earnings module. This module enables administrators to analyze the revenue generated by each scheduled show of the selected movie. For each show, the module displays the show date, show time, assigned screen, ticket price, number of confirmed bookings, total seats sold, and the earnings generated by that specific show.
Ledger Module
System Definition
The Ledger module maintains the financial transaction history of the Movie Ticket Booking System. Instead of storing only the current earnings, the module records every monetary transaction that affects the cinema's revenue, providing a complete audit trail of booking confirmations and booking cancellations (both full and partial). Whenever a customer successfully confirms a booking, the system automatically creates a BOOKING transaction in the ledger with a positive transaction amount. If a booking (or part of a booking) is cancelled, the system creates a corresponding CANCELLATION transaction with a negative value.
9. High-Level System Workflow
The system follows the workflow below:
Step 1: User Authentication
A visitor may browse movies and available shows without logging in.
When the visitor selects an available seat, the system checks whether the user is authenticated.
If the user is not authenticated, the system redirects the user to the login page.
If authentication is successful, the user returns to the selected show.
Step 2: Movie and Show Management
The administrator creates movies, genres, and movie shows.
While creating a show, the administrator selects: Movie, Screen, Show date, Show time, and Ticket price.
Before storing the show, the system verifies that another show is not already scheduled on the same screen during the same time period.
If no scheduling conflict exists, the show is created.
Step 3: Seat Generation
After a show is created, the system retrieves all seats assigned to the selected screen.
For each seat, a corresponding record is created in the Show_Seats table with the initial seat status set to AVAILABLE.
Step 4: Seat Selection
The customer selects one or more available seats.
When the first seat is selected:
A booking session is created.
The session expiry time is set to five minutes from the session start time.
For each selected seat:
The system verifies that the seat status is AVAILABLE.
The seat status is changed to LOCKED.
A corresponding record is created in the Seat_Locks table.
Step 5: Booking Confirmation
If the customer confirms the booking before the booking session expires:
A record is created in the Bookings table.
A record is created in the Booking_Details table for each booked seat.
The seat status changes from LOCKED to SOLD.
The booking session status changes to COMPLETED.
Temporary seat lock records are removed.
Step 6: Session Expiry
If the booking session expires before confirmation:
The booking session status changes to EXPIRED.
All seat lock records for that session are removed.
The corresponding seat status changes from LOCKED to AVAILABLE.
Step 7: Booking Cancellation
Cancellations can be requested for all seats or a partial selection of seats within a booking.
When a Customer requests cancellation, the system compares the current date and time with the scheduled show date and time.
If the remaining time is > 30 minutes, the cancellation is processed.
If the remaining time is ≤ 30 minutes, the customer request is rejected.
When an Administrator requests cancellation, they may bypass the 30-minute rule and cancel seats at any point before the show starts.
During processing, the selected seats change from SOLD to AVAILABLE. If it is a partial cancellation, the master booking totals are dynamically adjusted. If all seats are cancelled, the master booking status becomes CANCELLED.
10. Detailed Technical Workflows
10.1 Movie and Show Creation Workflow
Validate the submitted data.
Verify that the selected movie exists and is active.
Verify that the selected screen exists and is active.
Check whether another show is already scheduled on the same screen during the requested time period.
If no scheduling conflict exists, create a new record in the Shows table.
Retrieve all seats assigned to the selected screen.
Create one record in the Show_Seats table for each seat.
Set the initial seat status of every generated seat to AVAILABLE.
10.2 Seat Selection Workflow
The customer opens a movie show.
The system displays all seats for the selected show.
The customer selects an available seat.
If no active booking session exists, the system creates a new booking session.
The booking session expiry time is set to five minutes from the session start time.
The system checks whether the selected seat is still available.
If the seat is available: Update the seat status from AVAILABLE to LOCKED.
Create a record in the Seat_Locks table.
If the seat is already LOCKED or SOLD, the selection request is rejected.
10.3 Booking Confirmation Workflow
Verify that the booking session is active.
Verify that the selected seats are locked by the current booking session.
Create a record in the Bookings table.
Create one record in the Booking_Details table for each booked seat.
Update every selected seat from LOCKED to SOLD.
Remove the corresponding records from the Seat_Locks table.
Update the booking session status to COMPLETED.
10.4 Session Expiry Workflow
Identify expired booking sessions.
Update the booking session status to EXPIRED.
Retrieve all seat locks belonging to the expired session.
Update every locked seat from LOCKED to AVAILABLE.
Delete all seat lock records associated with the expired session.
10.5 Booking Cancellation Workflow (Full & Partial)
Retrieve the booking information and specifically selected seats to be cancelled.
Retrieve the scheduled show date and show time.
Calculate the remaining time before the show starts.
Validate privileges:
If Customer: ensure > 30 minutes remain.
If Administrator: ensure show has not yet started.
Process the voided seats:
Update the item_status in Booking_Details to CANCELLED for the selected seats.
Update each associated seat in Show_Seats from SOLD to AVAILABLE.
If all seats in the booking are cancelled:
Update the master Bookings status to CANCELLED.
Store the cancellation time.
If only partial seats are cancelled (Partial Cancellation):
Keep the master Bookings status as CONFIRMED.
Recalculate and update total_seats and total_amount in the Bookings table.
Generate a negative transaction record in the Ledger for the refunded amount.
11. Seat Booking Lifecycle
The system uses three database seat states.
Seat Status
Description
AVAILABLE
The seat can be selected for booking.
LOCKED
The seat is temporarily reserved during an active booking session.
SOLD
The booking has been confirmed.

The application also uses one user interface state.
UI State
Description
SELECTED
Indicates the seats currently selected by the customer. This state exists only in the browser and is not stored in the database.

Seat State Transitions
Successful Booking: AVAILABLE → LOCKED → SOLD
Session Expiry: AVAILABLE → LOCKED → AVAILABLE
Booking Cancellation (Full or Partial): SOLD → AVAILABLE
12. Concurrency Control
The system prevents multiple customers from confirming the same seat.
The system applies the following rules:
Only seats with the status AVAILABLE can be selected.
When a customer selects a seat, the system changes its status to LOCKED.
A corresponding record is created in the Seat_Locks table.
A locked seat cannot be selected by another customer.
A sold seat cannot be selected again.
If a booking session expires, the associated locked seats become available again.
When the booking is confirmed, locked seats become sold.
This process ensures that a seat can belong to only one active booking session at a time.
13. Booking Session Management
A booking session manages seat selection before booking confirmation. A booking session is created automatically when the customer selects the first available seat.
Each booking session contains: User, Show, Session start time, Expiry time, Session status.
The booking session status may be:
ACTIVE: Seat selection is in progress.
COMPLETED: Booking has been confirmed.
EXPIRED: The booking session expired before confirmation.
The booking session duration is five minutes.
If confirmed before expiry: Booking records are created, seat locks are removed, session status changes to COMPLETED.
If not confirmed before expiry: Seat locks are removed, locked seats become available, session status changes to EXPIRED.
14. Booking Cancellation Rules
The system allows customers and administrators to cancel confirmed bookings (fully or partially).
The following conditions are checked:
For Customers:
Booking status must be CONFIRMED.
The remaining time before the show starts must be greater than thirty minutes.
For Administrators:
Booking status must be CONFIRMED.
The show must not have started yet (bypasses the 30-minute restriction).
If conditions are satisfied:
Specific cancelled seats in Show_Seats are reverted from SOLD to AVAILABLE.
Specific cancelled items in Booking_Details are marked as CANCELLED.
If all seats are cancelled, master booking status changes to CANCELLED and cancellation time is stored.
If partial seats are cancelled, the total_amount and total_seats are dynamically reduced on the master booking record.
A ledger entry (CANCELLATION) is logged.
If conditions are not satisfied:
The cancellation request is rejected.
No database records are modified.
15. Database Overview
The Movie Ticket Booking System uses a normalized relational database consisting of fourteen tables.
The tables are grouped according to their purpose.
User Management: Roles, Users, User_Roles
Movie Management: Movies, Genres, Movie_Genres
Screen Management: Screens, Seats
Show Management: Shows, Show_Seats
Booking Management: Booking_Sessions, Seat_Locks, Bookings, Booking_Details
The database separates static data from transactional data.
Static data includes movie information, genres, screens, and seat layouts.
Transactional data includes shows, booking sessions, seat locks, bookings, and booking details.
16. Database Table Descriptions
Roles: Stores the available system roles (ADMIN, CUSTOMER).
Users: Stores user account information (Full name, Email, Phone, Password hash, Account status, Last login).
User_Roles: Associates users with system roles. A user may have one or more roles.
Movies: Stores movie information (Title, Description, Duration, Language, Release date, Format, Poster, Banner, Status).
Genres: Stores the available movie genres (Action, Comedy, Horror, Drama).
Movie_Genres: Associates movies with genres (many-to-many).
Screens: Stores information about cinema screens (Screen name, Total seats, Status).
Seats: Stores the static seat layout for each screen (Seat number, Type, Row group). Reused when shows are created.
Shows: Stores movie show schedules (Movie, Screen, Date, Time, Ticket price, Status).
Show_Seats: Stores the dynamic seat availability state for every show (AVAILABLE, LOCKED, SOLD).
Booking_Sessions: Stores temporary booking sessions (User, Show, Start, Expiry, Status).
Seat_Locks: Stores seats temporarily reserved during an active session. Links one session with one seat.
Bookings: Stores confirmed and fully cancelled master bookings (Customer, Show, Total seats, Total amount, Booking time, Status, Cancellation time).
Booking_Details: Stores individual seats associated with each booking. Includes item_status to track partial cancellations.
Ledger: Tracks individual financial changes (BOOKING, CANCELLATION) to provide a complete audit trail.
17. Database Relationships
One role can be assigned to many users.
One user can have multiple roles.
One movie can belong to multiple genres.
One genre can be assigned to multiple movies.
One screen contains multiple seats.
One movie can have multiple shows.
One screen can have multiple shows.
One show contains multiple show seats.
One booking session belongs to one user.
One booking session belongs to one show.
One booking session can contain multiple seat locks.
One booking contains multiple booking details.
One show can have multiple bookings.
18. Conclusion
The Movie Ticket Booking System provides a structured process for managing movies, show schedules, seat availability, and customer bookings for a single cinema.
The application separates static seat layouts from show-specific seat availability, allowing the same screen layout to be reused for multiple shows. Temporary seat locking during booking sessions prevents multiple customers from confirming the same seat at the same time. Booking sessions automatically expire after five minutes if the booking is not completed, allowing temporarily reserved seats to become available again.
Customers can cancel confirmed bookings before the defined cancellation deadline, while administrators maintain full oversight and can cancel partial or full bookings at any time before a show starts. Cancelled seats seamlessly return to the available pool for future bookings. The system uses a normalized relational database with Role-Based Access Control (RBAC) to organize user authorization, movie management, show scheduling, and booking records. This design improves data consistency, reduces redundancy, and supports the functional requirements defined for the project.
Database Tables (SQL)
SQL
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

19. Business Rules
The Movie Ticket Booking System follows the business rules below.
BR-01 User Authentication
A visitor may browse available movies and show schedules without logging in.
User authentication is required before seat selection and booking confirmation.
BR-02 Role Authorization
Users are granted access according to their assigned role.
Customers can perform booking-related operations.
Administrators can manage movies, genres, screens, shows, and booking records, and possess override cancellation privileges.
BR-03 Movie Availability
Only movies with the status ACTIVE are available for customers.
Movies with the status INACTIVE are not displayed for booking.
BR-04 Show Scheduling
A show must be associated with one movie and one screen.
A new show cannot be created if its scheduled time overlaps with another show on the same screen.
BR-05 Seat Generation
When a show is created, the system generates seat availability records for all seats assigned to the selected screen.
Every generated seat is initially assigned the AVAILABLE status.
BR-06 Seat Selection
Only seats with the AVAILABLE status can be selected.
A customer can select a maximum of five seats for a single booking.
Attempting to select more than five seats is not permitted.
BR-07 Booking Session
A booking session is created automatically when the customer selects the first available seat.
The booking session remains active for five minutes.
Seats selected during the session are assigned the LOCKED status.
BR-08 Booking Confirmation
A booking can be confirmed only while the booking session is active.
After successful confirmation:
The booking status becomes CONFIRMED.
Selected seats change from LOCKED to SOLD.
Temporary seat lock records are removed.
BR-09 Session Expiry
If the booking session expires before confirmation:
The session status changes to EXPIRED.
Locked seats become AVAILABLE.
Temporary seat lock records are removed.
BR-10 Booking Cancellation (Full & Partial)
A booking (or specific seats within a booking) may be cancelled by a Customer only if more than thirty minutes remain before the scheduled show time.
A booking (or specific seats within a booking) may be cancelled by an Administrator at any time before the scheduled show time.
When a booking/seat is cancelled:
If a full cancellation occurs, the master booking status changes to CANCELLED.
If a partial cancellation occurs, the master booking status remains CONFIRMED, but the total_amount and total_seats are recalculated, and the specific cancelled line items in Booking_Details are marked as CANCELLED.
Previously booked/cancelled seats in the show layout change from SOLD to AVAILABLE.
A corresponding entry is posted to the Ledger.
BR-11 Seat Availability
A seat can belong to only one active booking session at a time.
Seats with the status LOCKED or SOLD cannot be selected by another customer.

