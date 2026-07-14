<?php
require_once '../Includes/db_conn.php';
include 'components/sidebar.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$genre_id = "";
$genre_name = "";
$errors = [];
$success = "";
$is_editing = false;

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    
    // Validate if genre is linked to any movies
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM movie_genres WHERE genre_id = ?");
    $check_stmt->bind_param("i", $del_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result()->fetch_assoc();
    
    if ($check_res['count'] > 0) {
        $errors['general'] = "Cannot delete genre because it is assigned to one or more movies.";
    } else {
        $del_stmt = $conn->prepare("DELETE FROM genres WHERE genre_id = ?");
        $del_stmt->bind_param("i", $del_id);
        if ($del_stmt->execute()) {
            $_SESSION['success_message'] = "Genre deleted successfully.";
            header("Location: manage_genres.php");
            exit();
        } else {
            $errors['general'] = "Failed to delete genre.";
        }
    }
}

// Handle Edit Request Setup
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $fetch_stmt = $conn->prepare("SELECT * FROM genres WHERE genre_id = ?");
    $fetch_stmt->bind_param("i", $edit_id);
    $fetch_stmt->execute();
    $res = $fetch_stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $genre_name = $row['genre_name'];
        $genre_id = $row['genre_id'];
        $is_editing = true;
    }
}

// Form submission validation & handling (Add or Update)
if (isset($_POST['save_genre'])) {
    $genre_name = trim($_POST['genre_name']);
    if (isset($_POST['genre_id']) && !empty($_POST['genre_id'])) {
        $genre_id = (int)$_POST['genre_id'];
        $is_editing = true;
    }

    if (empty($genre_name)) {
        $errors['genre_name'] = "Genre name is required.";
    } elseif (strlen($genre_name) < 2 || strlen($genre_name) > 50) {
        $errors['genre_name'] = "Genre name must be between 2 and 50 characters.";
    }

    // Check for duplicates
    if (empty($errors['genre_name'])) {
        if ($is_editing) {
            $check = $conn->prepare("SELECT genre_id FROM genres WHERE genre_name = ? AND genre_id != ?");
            $check->bind_param("si", $genre_name, $genre_id);
        } else {
            $check = $conn->prepare("SELECT genre_id FROM genres WHERE genre_name = ?");
            $check->bind_param("s", $genre_name);
        }
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors['genre_name'] = "This genre name already exists.";
        }
    }

    if (empty($errors)) {
        if ($is_editing) {
            $stmt = $conn->prepare("UPDATE genres SET genre_name = ? WHERE genre_id = ?");
            $stmt->bind_param("si", $genre_name, $genre_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Genre updated successfully.";
                header("Location: manage_genres.php");
                exit();
            } else {
                $errors['general'] = "Failed to update genre.";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO genres (genre_name) VALUES (?)");
            $stmt->bind_param("s", $genre_name);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Genre added successfully.";
                header("Location: manage_genres.php");
                exit();
            } else {
                $errors['general'] = "Failed to add genre.";
            }
        }
    }
}

// --- Pagination, Search, and Sorting Logic ---
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

$where_clause = "";
$params = [];
$types = "";
if (!empty($search)) {
    $where_clause = " WHERE genre_name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$order_clause = " ORDER BY created_at DESC";
if ($sort == 'alpha_asc') $order_clause = " ORDER BY genre_name ASC";
elseif ($sort == 'alpha_desc') $order_clause = " ORDER BY genre_name DESC";
elseif ($sort == 'date_asc') $order_clause = " ORDER BY created_at ASC";

// Get total for pagination
$count_sql = "SELECT COUNT(*) as total FROM genres" . $where_clause;
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch data
$data_sql = "SELECT * FROM genres" . $where_clause . $order_clause . " LIMIT ?, ?";
$data_stmt = $conn->prepare($data_sql);
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";
$data_stmt->bind_param($types, ...$params);
$data_stmt->execute();
$genresQuery = $data_stmt->get_result();
// -------------------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Genres - Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/Admin/manage_genres.css">
</head>
<body>
<div class="main-container">
    <div class="content-area">
        <div class="page-header">
            <div class="page-title">
                <div class="title-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h1>Manage Genres</h1>
                    <p>Create, update, and remove movie genres</p>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="message">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message']; ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($errors['general'])) : ?>
            <div class="message error-msg">
                <i class="fas fa-exclamation-circle"></i> <?= $errors['general']; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="manage_genres.php">
                <?php if($is_editing): ?>
                    <input type="hidden" name="genre_id" value="<?= htmlspecialchars($genre_id); ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Genre Name</label>
                        <input type="text" name="genre_name" value="<?= htmlspecialchars($genre_name); ?>" placeholder="Enter genre name (e.g., Action, Thriller)">
                        <span class="error"><?= $errors['genre_name'] ?? ''; ?></span>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="save_genre" class="submit-btn"><?= $is_editing ? 'Update Genre' : 'Add Genre' ?></button>
                    <?php if($is_editing): ?>
                        <a href="manage_genres.php" class="reset-btn" style="text-decoration: none; display: inline-block; text-align: center;">Cancel</a>
                    <?php else: ?>
                        <button type="reset" class="reset-btn">Reset</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="show-list-header" style="margin-top: 30px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between;">
            <div class="show-list-title">
                <i class="fas fa-list-ul"></i>
                <div>
                    <h2>Genre List</h2>
                    <p>All available genres in the system</p>
                </div>
            </div>
            <div class="filter-controls">
                <form method="GET" action="manage_genres.php" style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search genres..." style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; outline: none; width: 200px;">
                    <select name="sort" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; outline: none;">
                        <option value="date_desc" <?= $sort == 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="date_asc" <?= $sort == 'date_asc' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="alpha_asc" <?= $sort == 'alpha_asc' ? 'selected' : ''; ?>>Alphabetical (A-Z)</option>
                        <option value="alpha_desc" <?= $sort == 'alpha_desc' ? 'selected' : ''; ?>>Alphabetical (Z-A)</option>
                    </select>
                    <button type="submit" class="action-btn" style="background: #333; color: white; padding: 10px 15px; border-radius: 8px;"><i class="fas fa-search"></i></button>
                    <?php if(!empty($search) || $sort != 'date_desc'): ?>
                        <a href="manage_genres.php" class="action-btn" style="background: #f5f5f5; color: #333; padding: 10px 15px; border-radius: 8px; text-decoration: none;"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-container">
            <?php if ($genresQuery->num_rows > 0) : ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Genre Name</th>
                            <th>Created Date</th>
                            <th>Updated Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($genre = $genresQuery->fetch_assoc()) : ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($genre['genre_name']); ?></strong></td>
                                <td><?= date("d M Y H:i", strtotime($genre['created_at'])); ?></td>
                                <td><?= date("d M Y H:i", strtotime($genre['updated_at'])); ?></td>
                                <td class="actions-cell">
                                    <a href="manage_genres.php?action=edit&id=<?= $genre['genre_id']; ?>" class="action-btn edit" title="Edit Genre"><i class="fas fa-edit"></i> Edit</a>
                                    <button type="button" class="action-btn delete" onclick="confirmDelete(<?= $genre['genre_id']; ?>)" title="Delete Genre"><i class="fas fa-trash-alt"></i> Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="no-data">No genres available.</div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container" style="margin-top: 25px; display: flex; justify-content: center; gap: 10px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="manage_genres.php?page=<?= $i; ?>&search=<?= urlencode($search); ?>&sort=<?= urlencode($sort); ?>" 
                   style="padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s;
                   <?= $i == $page ? 'background: #ff4d2d; color: white;' : 'background: white; color: #555; border: 1px solid #ddd;'; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="../Assets/js/Admin/manage_genres.js"></script>
</body>
</html>
