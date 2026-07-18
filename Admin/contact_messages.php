<?php
session_start();
require_once "../Includes/db_conn.php";
include "components/sidebar.php";

$adminName = $_SESSION['full_name'] ?? 'Admin';

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base condition for queries
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $where_clauses[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}
if ($status_filter !== '') {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if ($priority_filter !== '') {
    $where_clauses[] = "priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Statistics
$stat_query = "SELECT 
    COUNT(*) as total,
    SUM(IF(status = 'NEW', 1, 0)) as new_msgs,
    SUM(IF(status = 'REPLIED', 1, 0)) as replied_msgs,
    SUM(IF(status = 'CLOSED', 1, 0)) as closed_msgs
FROM contact_messages";
$stat_res = mysqli_query($conn, $stat_query);
$stats = mysqli_fetch_assoc($stat_res);
$total = $stats['total'] ?? 0;
$new = $stats['new_msgs'] ?? 0;
$replied = $stats['replied_msgs'] ?? 0;
$closed = $stats['closed_msgs'] ?? 0;

// Pagination count
$count_query = "SELECT COUNT(*) as cnt FROM contact_messages WHERE $where_sql";
if ($stmt = $conn->prepare($count_query)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $cnt_res = $stmt->get_result();
    $total_records = $cnt_res->fetch_assoc()['cnt'];
    $stmt->close();
}
$total_pages = ceil($total_records / $limit);

// Fetch data
$data_query = "SELECT * FROM contact_messages WHERE $where_sql ORDER BY submitted_at DESC LIMIT ?, ?";
$messages = [];
if ($stmt = $conn->prepare($data_query)) {
    $bind_params = $params;
    $bind_types = $types . "ii";
    $bind_params[] = $offset;
    $bind_params[] = $limit;
    
    if (!empty($bind_params)) {
        $stmt->bind_param($bind_types, ...$bind_params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin</title>
    <link rel="stylesheet" href="../Assets/css/Admin/admin_dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../Assets/css/Admin/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .filter-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-form input, .filter-form select, .filter-form button {
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: inherit;
        }
        .filter-form button {
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }
        .filter-form button:hover {
            background: #e63e1f;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        .pagination a {
            padding: 8px 12px;
            background: #fff;
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--text-dark);
            border-radius: 4px;
        }
        .pagination a.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .btn-view {
            background: #3b82f6;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .badge-new { background: #3b82f6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .badge-read { background: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .badge-replied { background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .badge-closed { background: #6b7280; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        
        .badge-low { color: #10b981; font-weight: bold; }
        .badge-medium { color: #f59e0b; font-weight: bold; }
        .badge-high { color: #ef4444; font-weight: bold; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="page-header">
        <div class="page-title">
            <i class="fa-solid fa-envelope"></i>
            <div>
                <h1>Contact Messages</h1>
                <p>Manage customer inquiries and feedback.</p>
            </div>
        </div>
    </div>

    <!-- DASHBOARD CARDS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #6b7280;">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="stat-content">
                <h2><?= $total ?></h2>
                <p>Total Messages</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f6;">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <h2><?= $new ?></h2>
                <p>New Messages</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #10b981;">
                <i class="fas fa-reply"></i>
            </div>
            <div class="stat-content">
                <h2><?= $replied ?></h2>
                <p>Replied</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #9ca3af;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-content">
                <h2><?= $closed ?></h2>
                <p>Closed</p>
            </div>
        </div>
    </div>

    <form method="GET" class="filter-form">
        <input type="text" name="search" placeholder="Search by name, email or subject..." value="<?= htmlspecialchars($search) ?>" style="flex:1;">
        
        <select name="status">
            <option value="">All Statuses</option>
            <option value="NEW" <?= $status_filter=='NEW' ? 'selected' : '' ?>>New</option>
            <option value="READ" <?= $status_filter=='READ' ? 'selected' : '' ?>>Read</option>
            <option value="REPLIED" <?= $status_filter=='REPLIED' ? 'selected' : '' ?>>Replied</option>
            <option value="CLOSED" <?= $status_filter=='CLOSED' ? 'selected' : '' ?>>Closed</option>
        </select>

        <select name="priority">
            <option value="">All Priorities</option>
            <option value="LOW" <?= $priority_filter=='LOW' ? 'selected' : '' ?>>Low</option>
            <option value="MEDIUM" <?= $priority_filter=='MEDIUM' ? 'selected' : '' ?>>Medium</option>
            <option value="HIGH" <?= $priority_filter=='HIGH' ? 'selected' : '' ?>>High</option>
        </select>

        <button type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="contact_messages.php" style="padding:10px; background:#f3f4f6; color:#374151; text-decoration:none; border-radius:6px; border:1px solid #d1d5db;">Clear</a>
    </form>

    <div class="booking-table-container">
        <table class="booking-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Submitted Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td>#<?= $msg['message_id'] ?></td>
                            <td><?= htmlspecialchars($msg['full_name']) ?></td>
                            <td><?= htmlspecialchars($msg['email']) ?></td>
                            <td><?= htmlspecialchars($msg['subject']) ?></td>
                            <td><span class="badge-<?= strtolower($msg['priority']) ?>"><?= $msg['priority'] ?></span></td>
                            <td><span class="badge-<?= strtolower($msg['status']) ?>"><?= $msg['status'] ?></span></td>
                            <td><?= date('d M Y h:i A', strtotime($msg['submitted_at'])) ?></td>
                            <td>
                                <a href="view_message.php?id=<?= $msg['message_id'] ?>" class="btn-view">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">No messages found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i=1; $i<=$total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&priority=<?= urlencode($priority_filter) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
