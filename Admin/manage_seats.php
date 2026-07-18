<?php
require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['screen_id'])) {
    header("Location: manage_screens.php");
    exit();
}

$screen_id = (int)$_GET['screen_id'];
$errors = [];
$success = "";

// Fetch screen info
$screen_stmt = $conn->prepare("SELECT * FROM screens WHERE screen_id = ?");
$screen_stmt->bind_param("i", $screen_id);
$screen_stmt->execute();
$screen_res = $screen_stmt->get_result();
if ($screen_res->num_rows == 0) {
    header("Location: manage_screens.php");
    exit();
}
$screen_data = $screen_res->fetch_assoc();

// Function to update total seats count in screens table
function updateTotalSeats($conn, $screen_id) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM seats WHERE screen_id = ?");
    $count_stmt->bind_param("i", $screen_id);
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['count'];

    $upd_stmt = $conn->prepare("UPDATE screens SET total_seats = ? WHERE screen_id = ?");
    $upd_stmt->bind_param("ii", $total, $screen_id);
    $upd_stmt->execute();
}

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    
    // Validate if seat is booked on any active show
    $check_stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM show_seats ss 
        JOIN shows sh ON ss.show_id = sh.show_id 
        WHERE ss.seat_id = ? AND sh.show_status = 'ACTIVE' AND ss.seat_status IN ('LOCKED', 'SOLD')
    ");
    $check_stmt->bind_param("i", $del_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result()->fetch_assoc();
    
    if ($check_res['count'] > 0) {
        $_SESSION['error_message'] = "The seat is booked by a user so you cannot delete this seat.";
        header("Location: manage_seats.php?screen_id=" . $screen_id);
        exit();
    } else {
        $del_stmt = $conn->prepare("DELETE FROM seats WHERE seat_id = ? AND screen_id = ?");
        $del_stmt->bind_param("ii", $del_id, $screen_id);
        if ($del_stmt->execute()) {
            updateTotalSeats($conn, $screen_id);
            $_SESSION['success_message'] = "Seat deleted successfully.";
            header("Location: manage_seats.php?screen_id=" . $screen_id);
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to delete seat.";
            header("Location: manage_seats.php?screen_id=" . $screen_id);
            exit();
        }
    }
}

// Handle Edit Request
if (isset($_POST['update_seat'])) {
    $edit_seat_id = (int)$_POST['seat_id'];
    $seat_type = trim($_POST['seat_type']);
    
    if (in_array($seat_type, ['REGULAR', 'VIP'])) {
        $upd_stmt = $conn->prepare("UPDATE seats SET seat_type = ? WHERE seat_id = ? AND screen_id = ?");
        $upd_stmt->bind_param("sii", $seat_type, $edit_seat_id, $screen_id);
        if ($upd_stmt->execute()) {
            $_SESSION['success_message'] = "Seat type updated successfully.";
            header("Location: manage_seats.php?screen_id=" . $screen_id);
            exit();
        } else {
            $errors['general'] = "Failed to update seat.";
        }
    }
}

// Handle Add Seats Request
if (isset($_POST['add_seats'])) {
    $row_group = strtoupper(trim($_POST['row_group']));
    $seat_type = trim($_POST['seat_type']);
    $number_of_seats = (int)$_POST['number_of_seats'];

    if (empty($row_group) || !preg_match('/^[A-Z]+$/', $row_group)) {
        $errors['add'] = "Row group must be alphabetic characters (e.g., A, B, AA).";
    } elseif (!in_array($seat_type, ['REGULAR', 'VIP'])) {
        $errors['add'] = "Invalid seat type.";
    } elseif ($number_of_seats < 1 || $number_of_seats > 10) {
        $errors['add'] = "You can add between 1 and 10 seats at a time.";
    }

    if (empty($errors['add'])) {
        // Find highest seat number for this row group
        // E.g. seat_number is 'A1', 'A2'. We extract the number part.
        $rg_len = strlen($row_group) + 1;
        $max_stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(seat_number, ?) AS UNSIGNED)) as max_num FROM seats WHERE screen_id = ? AND row_group = ?");
        $max_stmt->bind_param("iis", $rg_len, $screen_id, $row_group);
        $max_stmt->execute();
        $max_res = $max_stmt->get_result()->fetch_assoc();
        
        $current_max = $max_res['max_num'] ? (int)$max_res['max_num'] : 0;
        
        if ($current_max + $number_of_seats > 10) {
            $errors['add'] = "Cannot add $number_of_seats seats. Row '$row_group' has a maximum capacity of 10 seats (Currently has $current_max).";
        } else {
            $conn->begin_transaction();
            try {
                $ins_stmt = $conn->prepare("INSERT INTO seats (screen_id, seat_number, seat_type, row_group) VALUES (?, ?, ?, ?)");
                for ($i = 1; $i <= $number_of_seats; $i++) {
                    $new_num = $current_max + $i;
                    $seat_number = $row_group . $new_num;
                    $ins_stmt->bind_param("isss", $screen_id, $seat_number, $seat_type, $row_group);
                    $ins_stmt->execute();
                }
                $conn->commit();
                updateTotalSeats($conn, $screen_id);
                $_SESSION['success_message'] = "$number_of_seats seats added successfully to Row $row_group.";
                header("Location: manage_seats.php?screen_id=" . $screen_id);
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $errors['add'] = "Database error occurred while adding seats.";
            }
        }
    }
}

// Fetch all seats for this screen
$seats_query = $conn->prepare("SELECT * FROM seats WHERE screen_id = ? ORDER BY row_group ASC, CAST(SUBSTRING(seat_number, LENGTH(row_group) + 1) AS UNSIGNED) ASC");
$seats_query->bind_param("i", $screen_id);
$seats_query->execute();
$seats_result = $seats_query->get_result();
$seats_grouped = [];
while ($row = $seats_result->fetch_assoc()) {
    $seats_grouped[$row['row_group']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Seats - Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/Admin/manage_seats.css">
</head>
<body>
<div class="main-container">
    <div class="content-area">
        <div class="page-header">
            <div class="page-title">
                <div class="title-icon">
                    <i class="fas fa-chair"></i>
                </div>
                <div>
                    <h1>Manage Seats for <?= htmlspecialchars($screen_data['screen_name']); ?></h1>
                    <p>Total Capacity: <?= htmlspecialchars($screen_data['total_seats']); ?> Seats</p>
                </div>
            </div>
            <a href="manage_screens.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Screens</a>
        </div>

        <?php if (isset($_SESSION['success_message'])) : ?>
            <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['success_message']) ?>, 'success'));</script>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])) : ?>
            <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error_message']) ?>, 'error'));</script>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (isset($errors['general'])) : ?>
            <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($errors['general']) ?>, 'error'));</script>
        <?php endif; ?>

        <div class="form-card">
            <h3>Add New Seats</h3>
            <p style="margin-bottom: 20px; color: #777; font-size: 14px;">Maximum 10 seats allowed per row group.</p>
            
            <?php if (isset($errors['add'])) : ?>
                <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($errors['add']) ?>, 'error'));</script>
            <?php endif; ?>

            <form method="POST" action="manage_seats.php?screen_id=<?= $screen_id; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Row Group (e.g., A, B, C)</label>
                        <input type="text" name="row_group" placeholder="Row Letter" required pattern="[A-Za-z]+" title="Only alphabetical characters">
                    </div>
                    <div class="form-group">
                        <label>Number of Seats</label>
                        <input type="number" name="number_of_seats" min="1" max="10" placeholder="e.g., 5" required>
                    </div>
                    <div class="form-group">
                        <label>Seat Type</label>
                        <select name="seat_type" required>
                            <option value="REGULAR">REGULAR</option>
                            <option value="VIP">VIP</option>
                        </select>
                    </div>
                    <div class="form-group form-actions" style="margin-top: 25px;">
                        <button type="submit" name="add_seats" class="submit-btn">Add Seats</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="show-list-header" style="margin-top: 30px;">
            <div class="show-list-title">
                <i class="fas fa-th"></i>
                <div>
                    <h2>Seat Layout</h2>
                    <p>Current configuration of seats</p>
                </div>
            </div>
        </div>

        <div class="seat-layout">
            <?php if (empty($seats_grouped)) : ?>
                <div class="no-data" style="grid-column: 1 / -1;">No seats added to this screen yet.</div>
            <?php else : ?>
                <?php foreach ($seats_grouped as $rg => $seats) : ?>
                    <div class="row-group-container">
                        <div class="row-label">Row <?= htmlspecialchars($rg); ?></div>
                        <div class="row-seats">
                            <?php foreach ($seats as $seat) : ?>
                                <div class="seat-box <?= strtolower($seat['seat_type']); ?>">
                                    <span class="seat-num"><?= htmlspecialchars($seat['seat_number']); ?></span>
                                    <span class="seat-typ"><?= htmlspecialchars($seat['seat_type']); ?></span>
                                    
                                    <div class="seat-actions">
                                        <button type="button" class="action-icon edit-icon" onclick="openEditModal(<?= $seat['seat_id']; ?>, '<?= $seat['seat_type']; ?>', '<?= $seat['seat_number']; ?>')" title="Edit Type">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="action-icon del-icon" onclick="confirmDelete(<?= $seat['seat_id']; ?>)" title="Delete Seat">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal Overlay -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <h3>Edit Seat <span id="modalSeatNum"></span></h3>
        <form method="POST" action="manage_seats.php?screen_id=<?= $screen_id; ?>">
            <input type="hidden" name="seat_id" id="modalSeatId">
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Seat Type</label>
                <select name="seat_type" id="modalSeatType">
                    <option value="REGULAR">REGULAR</option>
                    <option value="VIP">VIP</option>
                </select>
            </div>
            <div class="form-actions" style="margin-top: 0;">
                <button type="submit" name="update_seat" class="submit-btn">Save Changes</button>
                <button type="button" class="reset-btn" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../Assets/js/Admin/manage_seats.js"></script>
</body>
</html>
