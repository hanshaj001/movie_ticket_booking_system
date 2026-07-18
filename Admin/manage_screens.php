<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$screen_id = "";
$screen_name = "";
$total_seats = "";
$screen_status = "ACTIVE";
$errors = [];
$success = "";
$is_editing = false;

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    
    // Validate if screen has future shows
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM shows WHERE screen_id = ? AND CONCAT(show_date, ' ', show_time) >= NOW() AND show_status != 'CANCELLED'");
    $check_stmt->bind_param("i", $del_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result()->fetch_assoc();
    
    if ($check_res['count'] > 0) {
        $_SESSION['error_message'] = "Cannot delete screen because there are future shows scheduled on it.";
        header("Location: manage_screens.php");
        exit();
    } else {
        $del_stmt = $conn->prepare("DELETE FROM screens WHERE screen_id = ?");
        $del_stmt->bind_param("i", $del_id);
        if ($del_stmt->execute()) {
            $_SESSION['success_message'] = "Screen deleted successfully.";
            header("Location: manage_screens.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to delete screen.";
            header("Location: manage_screens.php");
            exit();
        }
    }
}

// Handle Update Status Request from Dropdown Action
if (isset($_GET['action']) && $_GET['action'] == 'change_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $status_id = (int)$_GET['id'];
    $new_status = $_GET['status'];
    if (in_array($new_status, ['ACTIVE', 'INACTIVE', 'MAINTENANCE'])) {
        $upd_stmt = $conn->prepare("UPDATE screens SET screen_status = ? WHERE screen_id = ?");
        $upd_stmt->bind_param("si", $new_status, $status_id);
        if ($upd_stmt->execute()) {
            $_SESSION['success_message'] = "Screen status updated successfully.";
            header("Location: manage_screens.php");
            exit();
        }
    }
}

// Handle Edit Request Setup
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $fetch_stmt = $conn->prepare("SELECT * FROM screens WHERE screen_id = ?");
    $fetch_stmt->bind_param("i", $edit_id);
    $fetch_stmt->execute();
    $res = $fetch_stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $screen_name = $row['screen_name'];
        $total_seats = $row['total_seats'];
        $screen_status = $row['screen_status'];
        $screen_id = $row['screen_id'];
        $is_editing = true;
    }
}

// Form submission validation & handling (Add or Update)
if (isset($_POST['save_screen'])) {
    $screen_name = trim($_POST['screen_name']);
    $total_seats = trim($_POST['total_seats']);
    $screen_status = trim($_POST['screen_status']);
    
    if (isset($_POST['screen_id']) && !empty($_POST['screen_id'])) {
        $screen_id = (int)$_POST['screen_id'];
        $is_editing = true;
    }

    if (empty($screen_name)) {
        $errors['screen_name'] = "Screen name is required.";
    } elseif (strlen($screen_name) < 2 || strlen($screen_name) > 50) {
        $errors['screen_name'] = "Screen name must be between 2 and 50 characters.";
    }
    
    if ($total_seats !== '' && !filter_var($total_seats, FILTER_VALIDATE_INT)) {
        $errors['total_seats'] = "Total seats must be a valid number.";
    } elseif ($total_seats < 0) {
        $errors['total_seats'] = "Total seats cannot be negative.";
    }
    if ($total_seats === '') {
        $total_seats = 0; // default value
    }
    
    if (!in_array($screen_status, ['ACTIVE', 'INACTIVE', 'MAINTENANCE'])) {
        $errors['screen_status'] = "Invalid screen status selected.";
    }

    // Check for duplicates
    if (empty($errors['screen_name'])) {
        if ($is_editing) {
            $check = $conn->prepare("SELECT screen_id FROM screens WHERE screen_name = ? AND screen_id != ?");
            $check->bind_param("si", $screen_name, $screen_id);
        } else {
            $check = $conn->prepare("SELECT screen_id FROM screens WHERE screen_name = ?");
            $check->bind_param("s", $screen_name);
        }
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors['screen_name'] = "This screen name already exists.";
        }
    }

    if (empty($errors)) {
        if ($is_editing) {
            $stmt = $conn->prepare("UPDATE screens SET screen_name = ?, total_seats = ?, screen_status = ? WHERE screen_id = ?");
            $stmt->bind_param("sisi", $screen_name, $total_seats, $screen_status, $screen_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Screen updated successfully.";
                header("Location: manage_screens.php");
                exit();
            } else {
                $errors['general'] = "Failed to update screen.";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO screens (screen_name, total_seats, screen_status) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $screen_name, $total_seats, $screen_status);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Screen added successfully.";
                header("Location: manage_screens.php");
                exit();
            } else {
                $errors['general'] = "Failed to add screen.";
            }
        }
    }
}

// --- Pagination Logic ---
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

$count_res = $conn->query("SELECT COUNT(*) as total FROM screens");
$total_records = $count_res->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$screensStmt = $conn->prepare("SELECT * FROM screens ORDER BY created_at DESC LIMIT ?, ?");
$screensStmt->bind_param("ii", $offset, $records_per_page);
$screensStmt->execute();
$screensQuery = $screensStmt->get_result();
// ------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Screens - Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/Admin/manage_screens.css">
</head>
<body>
<div class="main-container">
    <div class="content-area">
        <div class="page-header">
            <div class="page-title">
                <div class="title-icon">
                    <i class="fas fa-tv"></i>
                </div>
                <div>
                    <h1>Manage Screens</h1>
                    <p>Create, update, and manage cinema screens</p>
                </div>
            </div>
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
            <form method="POST" action="manage_screens.php">
                <?php if($is_editing): ?>
                    <input type="hidden" name="screen_id" value="<?= htmlspecialchars($screen_id); ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Screen Name</label>
                        <input type="text" name="screen_name" value="<?= htmlspecialchars($screen_name); ?>" placeholder="Enter screen name (e.g., Screen 1)">
                        <span class="error"><?= $errors['screen_name'] ?? ''; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Total Seats (Optional - calculated automatically via Manage Seats)</label>
                        <input type="number" name="total_seats" value="<?= htmlspecialchars($total_seats); ?>" placeholder="e.g., 150">
                        <span class="error"><?= $errors['total_seats'] ?? ''; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Screen Status</label>
                        <select name="screen_status">
                            <option value="ACTIVE" <?= $screen_status == 'ACTIVE' ? 'selected' : ''; ?>>ACTIVE</option>
                            <option value="INACTIVE" <?= $screen_status == 'INACTIVE' ? 'selected' : ''; ?>>INACTIVE</option>
                            <option value="MAINTENANCE" <?= $screen_status == 'MAINTENANCE' ? 'selected' : ''; ?>>MAINTENANCE</option>
                        </select>
                        <span class="error"><?= $errors['screen_status'] ?? ''; ?></span>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="save_screen" class="submit-btn"><?= $is_editing ? 'Update Screen' : 'Add New Screen' ?></button>
                    <?php if($is_editing): ?>
                        <a href="manage_screens.php" class="reset-btn" style="text-decoration: none; display: inline-block; text-align: center;">Cancel</a>
                    <?php else: ?>
                        <button type="reset" class="reset-btn">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="show-list-header" style="margin-top: 30px;">
            <div class="show-list-title">
                <i class="fas fa-list-ul"></i>
                <div>
                    <h2>Screen List</h2>
                    <p>All available screens in the system</p>
                </div>
            </div>
        </div>

        <div class="screen-grid">
            <?php if ($screensQuery->num_rows > 0) : ?>
                <?php while ($screen = $screensQuery->fetch_assoc()) : 
                    // Update total_seats dynamically based on seats table if we wanted to, but we'll stick to displaying the database column
                ?>
                    <div class="screen-card">
                        <div class="screen-content">
                            <div class="screen-top">
                                <h3><?= htmlspecialchars($screen['screen_name']); ?></h3>
                                <span class="status <?= strtolower($screen['screen_status']); ?>">
                                    <?= htmlspecialchars($screen['screen_status']); ?>
                                </span>
                            </div>
                            
                            <div class="screen-meta">
                                <span><i class="fas fa-chair"></i> &nbsp; <?= htmlspecialchars($screen['total_seats']); ?> Seats Total</span>
                            </div>
                            
                            <div class="screen-meta">
                                <span><i class="fas fa-calendar-alt"></i> &nbsp; Created: <?= date("d M Y", strtotime($screen['created_at'])); ?></span>
                            </div>

                            <div class="action-dropdown-container">
                                <button class="dropdown-btn" onclick="toggleDropdown(<?= $screen['screen_id']; ?>)">
                                    Actions <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-content" id="dropdown-<?= $screen['screen_id']; ?>">
                                    <a href="manage_seats.php?screen_id=<?= $screen['screen_id']; ?>"><i class="fas fa-chair"></i> Manage Seats</a>
                                    <a href="manage_screens.php?action=edit&id=<?= $screen['screen_id']; ?>"><i class="fas fa-edit"></i> Edit Screen</a>
                                    <div class="dropdown-submenu-container">
                                        <a href="javascript:void(0)" class="submenu-toggle"><i class="fas fa-exchange-alt"></i> Change Status</a>
                                        <div class="dropdown-submenu">
                                            <a href="manage_screens.php?action=change_status&id=<?= $screen['screen_id']; ?>&status=ACTIVE">ACTIVE</a>
                                            <a href="manage_screens.php?action=change_status&id=<?= $screen['screen_id']; ?>&status=INACTIVE">INACTIVE</a>
                                            <a href="manage_screens.php?action=change_status&id=<?= $screen['screen_id']; ?>&status=MAINTENANCE">MAINTENANCE</a>
                                        </div>
                                    </div>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?= $screen['screen_id']; ?>)" class="delete-link"><i class="fas fa-trash-alt"></i> Delete Screen</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="no-data" style="grid-column: 1 / -1;">No screens available.</div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container" style="margin-top: 25px; display: flex; justify-content: center; gap: 10px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="manage_screens.php?page=<?= $i; ?>" 
                   style="padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s;
                   <?= $i == $page ? 'background: #ff4d2d; color: white;' : 'background: white; color: #555; border: 1px solid #ddd;'; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="../Assets/js/Admin/manage_screens.js"></script>
</body>
</html>
