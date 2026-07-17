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
