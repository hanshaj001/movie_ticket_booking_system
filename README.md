
1. Executive Summary
The Movie Ticket Booking System is a web-based application developed to manage movie scheduling, seat booking, and booking records for a single cinema with multiple movie screens. The system provides separate interfaces for customers and administrators using Role-Based Access Control (RBAC).
Customers can create an account, log in, browse available movies, view show schedules, select available seats, confirm bookings, view booking history, and cancel eligible bookings. A booking session is created when a customer selects the first available seat. The session remains active for five minutes. During the active session, selected seats are temporarily locked so that they cannot be selected by another customer. If the booking is confirmed before the session expires, the seats are marked as sold. If the session expires, the temporary seat locks are removed and the seats become available again.
Administrators manage movies, genres, show schedules, ticket prices, and booking records through the administration panel. When a new show is created, the system automatically creates seat records for that show based on the seat layout of the selected screen.
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
Allow customers to cancel bookings before the cancellation deadline.
Maintain a normalized relational database structure.
3. Technology Stack
ComponentTechnologyFrontendHTML5, CSS3, JavaScriptAsynchronous RequestsAJAXBackendPHPDatabaseMySQLDevelopment EnvironmentXAMPPSource Code EditorVisual Studio Code4. Project Scope
The Movie Ticket Booking System is designed for a single cinema that contains multiple movie screens.
The system provides the following functionality:

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
Cancel eligible bookings.
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
Log out.
System
Generate seat records for every newly created show.
Create booking sessions during seat selection.
Temporarily lock selected seats.
Release locked seats after session expiry.
Prevent multiple bookings for the same seat.
Maintain booking records.
5. Project Limitations
The current implementation includes the following limitations.

The system supports only one cinema location.
Online payment gateways are not implemented.
Email notifications are not implemented.
SMS notifications are not implemented.
QR code ticket generation is not implemented.
Mobile applications are not included.
Third-party login services such as Google or Facebook are not supported.
Movie recommendations are not provided.
Refund processing is not implemented.
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
If a visitor clicks an available seat, the system redirects the user to the login page. No booking session is created before successful authentication.
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
Cancel bookings before the cancellation deadline.
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

Allow cancellation only before the cancellation deadline.
Update booking status after cancellation.
Change cancelled seats back to AVAILABLE.
8. System Modules
The system consists of the following modules.

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
Booking cancellation
Administrator Module
Responsible for:

Movie management
Genre management
Screen management
Show management
Booking monitoring
Booking Module
Responsible for:

Booking session creation
Seat locking
Booking confirmation
Booking cancellation
Session expiry
Seat status updates

Absolutely. For a **project documentation**, each module should explain its **purpose**, **major functions**, and **system behavior** rather than just one or two lines. Below are detailed descriptions written in a consistent academic style.

---

# Manage Screens Module

### System Definition

The **Manage Screens** module enables the administrator to manage the cinema screens available within the system. Since each movie show is assigned to a specific screen, this module provides the necessary functionality to create and maintain screen information used during show scheduling and seat allocation.

The administrator can add new screens by specifying the screen name and status, update existing screen information, and modify the operational status of a screen. The supported screen statuses include **ACTIVE**, **INACTIVE**, and **MAINTENANCE**, allowing the administrator to control whether a screen is available for scheduling movie shows.

The module also provides access to seat management for each screen. Through the **Manage Seats** option, the administrator can view the complete seat layout of a selected screen and perform seat-related operations. Each screen maintains its own seat configuration, which is reused whenever a new movie show is created for that screen.

The screen information maintained by this module is directly used by the **Show Management** module when creating movie schedules, ensuring that every show is assigned to a valid and operational screen.

---

# Manage Seats Module

### System Definition

The **Manage Seats** module allows the administrator to configure and maintain the seat layout for each cinema screen. Every seat stored in this module represents the physical seating arrangement of a particular screen and serves as the template for generating seat availability whenever a new movie show is created.

The administrator can view all seats belonging to a selected screen, add new seats, edit seat information, and remove seats when necessary. Each seat contains attributes such as **Seat Number**, **Seat Type**, and **Row Group**, allowing different seating arrangements to be maintained.

When new seats are added, the system automatically continues the seat numbering sequence from the last existing seat of the selected screen. Existing seat numbers are preserved, preventing duplicate seat identifiers and maintaining a consistent seating arrangement throughout the system.

The seat layout defined in this module is referenced during show creation, where the system automatically generates corresponding records in the **Show_Seats** table with an initial seat status of **AVAILABLE** for every seat in the selected screen.

---

# Manage Genres Module

### System Definition

The **Manage Genres** module allows the administrator to maintain the collection of movie genres used throughout the system. Since a movie may belong to multiple genres and a genre may be assigned to multiple movies, this module supports the many-to-many relationship implemented through the **Movie_Genres** table.

The administrator can create new genres, update existing genre names, view all available genres, and remove genres that are no longer required. Before deleting a genre, the system verifies that the selected genre is not associated with any movie. If an association exists, the deletion request is rejected to maintain referential integrity and prevent invalid references within the database.

The genres maintained through this module are used by the **Movie Management** module, allowing administrators to categorize movies accurately and enabling customers to view descriptive information about each movie.

---

# Earnings Module

### System Definition

The **Earnings** module provides a summarized financial report of all movies available in the system. Its primary purpose is to allow administrators to monitor the revenue generated by individual movies without reviewing every booking transaction separately.

The module displays both **ACTIVE** and **INACTIVE** movies and presents summary information including the total number of scheduled shows, confirmed bookings, seats sold, and total earnings for each movie. The movie list is displayed using pagination, with a maximum of ten movies displayed per page to maintain readability and improve navigation when the number of movies increases.

Each movie includes a **View Earnings** option that opens a detailed earnings report for the selected movie. This report presents show-wise financial information, allowing administrators to compare the performance of different movie shows.

The earnings displayed in this module are calculated using the financial transactions recorded in the **Ledger** module. As a result, booking confirmations increase the reported earnings while booking cancellations reduce the earnings accordingly, ensuring that the displayed values accurately represent the current revenue generated by each movie.

---

# Movie Earnings Module

### System Definition

The **Movie Earnings** module provides a detailed financial report for an individual movie selected from the Earnings module. This module enables administrators to analyze the revenue generated by each scheduled show of the selected movie.

The report displays summary information about the movie, including the movie name, movie status, total number of shows, total confirmed bookings, total seats sold, and overall earnings. Below the summary, the system displays detailed information for every scheduled show associated with that movie.

For each show, the module displays the show date, show time, assigned screen, ticket price, number of confirmed bookings, total seats sold, and the earnings generated by that specific show. This allows administrators to compare the performance of different shows and identify which schedules generate higher ticket sales and revenue.

The values displayed in this module are calculated using the corresponding booking and ledger records, ensuring that booking cancellations are reflected correctly in the reported earnings.

---

# Ledger Module

### System Definition

The **Ledger** module maintains the financial transaction history of the Movie Ticket Booking System. Instead of storing only the current earnings, the module records every monetary transaction that affects the cinema's revenue, providing a complete audit trail of booking confirmations and booking cancellations.

Whenever a customer successfully confirms a booking, the system automatically creates a **BOOKING** transaction in the ledger with a positive transaction amount. If the customer later cancels the booking within the allowed cancellation period, the system creates a corresponding **CANCELLATION** transaction with the same amount recorded as a negative value. Existing ledger records are never modified or deleted; instead, new transactions are added to preserve the complete financial history.

The Ledger module displays transactions in chronological order and includes information such as the transaction date, booking identifier, movie name, show details, customer name, transaction type, transaction amount, running balance, and optional remarks. Positive transactions represent revenue generated through confirmed bookings, while negative transactions represent revenue deducted due to booking cancellations.

Because every financial event is stored as an individual transaction, the module provides complete traceability of revenue movement and allows the administrator to verify how bookings and cancellations affect the current earnings of each movie and the overall cinema. This transaction-based approach also serves as the data source for the Earnings module, where total earnings are calculated by summing the recorded ledger transactions rather than storing a separate earnings value.


9. High-Level System Workflow
The system follows the workflow below.

Step 1: User Authentication
A visitor may browse movies and available shows without logging in.
When the visitor selects an available seat, the system checks whether the user is authenticated.

If the user is not authenticated, the system redirects the user to the login page.
If authentication is successful, the user returns to the selected show.
Step 2: Movie and Show Management
The administrator creates movies, genres, and movie shows.
While creating a show, the administrator selects:

Movie
Screen
Show date
Show time
Ticket price
Before storing the show, the system verifies that another show is not already scheduled on the selected screen during the same time period.
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
When a customer requests cancellation, the system compares the current date and time with the scheduled show date and time.

If the remaining time before the show is greater than 30 minutes, the booking status changes from CONFIRMED to CANCELLED, and the booked seats change from SOLD to AVAILABLE.
If the remaining time is 30 minutes or less, the cancellation request is rejected.

10. Detailed Technical Workflows
10.1 Movie and Show Creation Workflow
The administrator creates a movie show by selecting a movie, screen, show date, show time, and ticket price.
The system performs the following steps:


Validate the submitted data.

Verify that the selected movie exists and is active.

Verify that the selected screen exists and is active.

Check whether another show is already scheduled on the same screen during the requested time period.

If no scheduling conflict exists, create a new record in the Shows table.

Retrieve all seats assigned to the selected screen.

Create one record in the Show_Seats table for each seat.

Set the initial seat status of every generated seat to AVAILABLE.
10.2 Seat Selection Workflow
Seat selection is available only for authenticated customers.
The workflow is as follows:


The customer opens a movie show.

The system displays all seats for the selected show.

The customer selects an available seat.

If no active booking session exists, the system creates a new booking session.

The booking session expiry time is set to five minutes from the session start time.

The system checks whether the selected seat is still available.

If the seat is available:
Update the seat status from AVAILABLE to LOCKED.

Create a record in the Seat_Locks table.

If the seat is already LOCKED or SOLD, the selection request is rejected.
10.3 Booking Confirmation Workflow
The booking confirmation process begins when the customer submits the booking before the session expires.
The workflow is as follows:


Verify that the booking session is active.

Verify that the selected seats are locked by the current booking session.

Create a record in the Bookings table.

Create one record in the Booking_Details table for each booked seat.

Update every selected seat from LOCKED to SOLD.

Remove the corresponding records from the Seat_Locks table.

Update the booking session status to COMPLETED.
10.4 Session Expiry Workflow
A booking session expires if the customer does not complete the booking within five minutes.
The workflow is as follows:


Identify expired booking sessions.

Update the booking session status to EXPIRED.

Retrieve all seat locks belonging to the expired session.

Update every locked seat from LOCKED to AVAILABLE.

Delete all seat lock records associated with the expired session.
10.5 Booking Cancellation Workflow
Customers may cancel confirmed bookings before the cancellation deadline.
The workflow is as follows:


Retrieve the booking information.

Retrieve the scheduled show date and show time.

Calculate the remaining time before the show starts.

If more than thirty minutes remain:
Update the booking status to CANCELLED.

Store the cancellation time.

Update each booked seat from SOLD to AVAILABLE.

If thirty minutes or less remain before the show starts, the cancellation request is rejected.
11. Seat Booking Lifecycle
The system uses three database seat states.
Seat StatusDescriptionAVAILABLEThe seat can be selected for booking.LOCKEDThe seat is temporarily reserved during an active booking session.SOLDThe booking has been confirmed.
The application also uses one user interface state.
UI StateDescriptionSELECTEDIndicates the seats currently selected by the customer. This state exists only in the browser and is not stored in the database.
Seat State Transitions
Successful Booking

AVAILABLE
      ↓
LOCKED
      ↓
SOLD

Session Expiry

AVAILABLE
      ↓
LOCKED
      ↓
AVAILABLE

Booking Cancellation

SOLD
   ↓
AVAILABLE

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
A booking session manages seat selection before booking confirmation.
A booking session is created automatically when the customer selects the first available seat.
Each booking session contains:


User

Show

Session start time

Expiry time

Session status
The booking session status may be:
StatusDescriptionACTIVESeat selection is in progress.COMPLETEDBooking has been confirmed.EXPIREDThe booking session expired before confirmation.
The booking session duration is five minutes.
If the booking is confirmed before the expiry time:


Booking records are created.

Seat locks are removed.

Session status changes to COMPLETED.
If the booking is not confirmed before the expiry time:


Seat locks are removed.

Locked seats become available.

Session status changes to EXPIRED.
14. Booking Cancellation Rules
The system allows customers to cancel confirmed bookings only before the cancellation deadline.
The following conditions are checked:


Booking status must be CONFIRMED.

The remaining time before the show starts must be greater than thirty minutes.
If both conditions are satisfied:


Booking status changes to CANCELLED.

Cancellation time is stored.

Every booked seat changes from SOLD to AVAILABLE.
If either condition is not satisfied:


The cancellation request is rejected.

No database records are modified.
15. Database Overview
The Movie Ticket Booking System uses a normalized relational database consisting of fourteen tables.
The tables are grouped according to their purpose.

User Management

Roles

Users

User_Roles
Movie Management

Movies

Genres

Movie_Genres
Screen Management

Screens

Seats
Show Management

Shows

Show_Seats
Booking Management

Booking_Sessions

Seat_Locks

Bookings

Booking_Details
The database separates static data from transactional data.
Static data includes movie information, genres, screens, and seat layouts.
Transactional data includes shows, booking sessions, seat locks, bookings, and booking details.
16. Database Table Descriptions
Roles
Stores the available system roles.
Examples:


ADMIN

CUSTOMER
Users
Stores user account information.
Contents include:


Full name

Email address

Phone number

Password hash

Account status

Last login time
User_Roles
Associates users with system roles.
A user may have one or more roles.
Movies
Stores movie information.
Contents include:


Title

Description

Duration

Language

Release date

Movie format

Poster image

Banner image

Status
Movie status is managed using ACTIVE and INACTIVE values.
Genres
Stores the available movie genres.
Examples:


Action

Comedy

Horror

Drama
Movie_Genres
Associates movies with genres.
A movie may belong to multiple genres.
A genre may be assigned to multiple movies.
Screens
Stores information about cinema screens.
Contents include:


Screen name

Total seats

Screen status
Seats
Stores the seat layout for each screen.
Contents include:


Seat number

Seat type

Row group
Seat records are reused whenever a new show is created.
Shows
Stores movie show schedules.
Contents include:


Movie

Screen

Show date

Show time

Ticket price

Show status
Show_Seats
Stores the seat availability for every show.
Each record represents one seat for one show.
Possible seat states:


AVAILABLE

LOCKED

SOLD
Booking_Sessions
Stores temporary booking sessions.
Contents include:


User

Show

Session start time

Expiry time

Session status
Seat_Locks
Stores seats that are temporarily reserved during an active booking session.
Each record links one booking session with one seat.
Bookings
Stores confirmed and cancelled bookings.
Contents include:


Customer

Show

Number of seats

Total amount

Booking time

Booking status

Cancellation time
Booking_Details
Stores individual seats associated with each booking.
Each record links one booking with one seat and stores the ticket price.
17. Database Relationships
The system maintains the following relationships:


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
The application separates static seat layouts from show-specific seat availability, allowing the same screen layout to be reused for multiple shows. Temporary seat locking during booking sessions prevents multiple customers from confirming the same seat at the same time. Booking sessions automatically expire after five minutes if the booking is not completed, allowing temporarily reserved seats to become available again. Customers can cancel confirmed bookings before the defined cancellation deadline, after which the seats become available for future bookings.
The system uses a normalized relational database with Role-Based Access Control (RBAC) to organize user authorization, movie management, show scheduling, and booking records. This design improves data consistency, reduces redundancy, and supports the functional requirements defined for the project.
tables
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
-- MOVIES TABLE (Updated: Genre removed, banner_url added)
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
-- BOOKING DETAILS TABLE
-- =========================================================
CREATE TABLE booking_details (
   booking_detail_id INT AUTO_INCREMENT PRIMARY KEY,
   booking_id INT NOT NULL,
   show_seat_id INT NOT NULL,
   ticket_price DECIMAL(10,2) NOT NULL,
   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
   FOREIGN KEY (show_seat_id) REFERENCES show_seats(show_seat_id) ON DELETE CASCADE,
   UNIQUE(show_seat_id)
);
CREATE TABLE ledger (
    ledger_id INT AUTO_INCREMENT PRIMARY KEY,

    booking_id INT NOT NULL,
    movie_id INT NOT NULL,
    show_id INT NOT NULL,

    transaction_type ENUM('BOOKING','CANCELLATION') NOT NULL,

    amount DECIMAL(10,2) NOT NULL,

    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    remarks VARCHAR(255) NULL,

    FOREIGN KEY (booking_id)
        REFERENCES bookings(booking_id),

    FOREIGN KEY (movie_id)
        REFERENCES movies(movie_id),

    FOREIGN KEY (show_id)
        REFERENCES shows(show_id)
);

10. Business Rules

The Movie Ticket Booking System follows the business rules below.

BR-01 User Authentication
A visitor may browse available movies and show schedules without logging in.
User authentication is required before seat selection and booking confirmation.
BR-02 Role Authorization
Users are granted access according to their assigned role.
Customers can perform booking-related operations.
Administrators can manage movies, genres, screens, shows, and booking records.
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
BR-10 Booking Cancellation
A booking may be cancelled only if more than thirty minutes remain before the scheduled show time.
When a booking is cancelled:
The booking status changes to CANCELLED.
Previously booked seats change from SOLD to AVAILABLE.
BR-11 Seat Availability
A seat can belong to only one active booking session at a time.
Seats with the status LOCKED or SOLD cannot be selected by another customer.