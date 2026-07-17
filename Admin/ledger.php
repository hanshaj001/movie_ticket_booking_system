<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../Includes/db_conn.php';

// Check for JSON export action
if (isset($_GET['action']) && $_GET['action'] == 'get_export_data') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    $m_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : '';
    $s_id = isset($_GET['show_id']) ? (int)$_GET['show_id'] : '';
    $t_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : '';
    $d_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $d_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    $srt = isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'asc' : 'desc';
    $lmt = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 0;
    
    $w_clauses = ["1=1"];
    $p_list = [];
    $p_types = "";
    
    if ($m_id) {
        $w_clauses[] = "l.movie_id = ?";
        $p_list[] = $m_id;
        $p_types .= "i";
    }
    if ($s_id) {
        $w_clauses[] = "l.show_id = ?";
        $p_list[] = $s_id;
        $p_types .= "i";
    }
    if ($t_type == 'BOOKING' || $t_type == 'CANCELLATION') {
        $w_clauses[] = "l.transaction_type = ?";
        $p_list[] = $t_type;
        $p_types .= "s";
    }
    if ($d_from) {
        $w_clauses[] = "DATE(l.transaction_date) >= ?";
        $p_list[] = $d_from;
        $p_types .= "s";
    }
    if ($d_to) {
        $w_clauses[] = "DATE(l.transaction_date) <= ?";
        $p_list[] = $d_to;
        $p_types .= "s";
    }
    
    $w_sql = implode(" AND ", $w_clauses);
    $o_sql = $srt == 'asc' ? 'l.transaction_date ASC, l.ledger_id ASC' : 'l.transaction_date DESC, l.ledger_id DESC';
    
    $q_str = "
        SELECT l.*, 
               b.booking_id as bk_display_id,
               m.title as movie_name,
               s.show_date, s.show_time, 
               sc.screen_name,
               u.full_name as customer_name
        FROM ledger l
        JOIN bookings b ON l.booking_id = b.booking_id
        JOIN movies m ON l.movie_id = m.movie_id
        JOIN shows s ON l.show_id = s.show_id
        JOIN screens sc ON s.screen_id = sc.screen_id
        JOIN users u ON b.user_id = u.user_id
        WHERE $w_sql
        ORDER BY $o_sql
    ";
    if ($lmt > 0) {
        $q_str .= " LIMIT ?";
        $p_list[] = $lmt;
        $p_types .= "i";
    }
    
    $exp_stmt = $conn->prepare($q_str);
    if (!empty($p_list)) {
        $exp_stmt->bind_param($p_types, ...$p_list);
    }
    $exp_stmt->execute();
    $exp_res = $exp_stmt->get_result();
    
    $records_list = [];
    while ($row_item = $exp_res->fetch_assoc()) {
        $records_list[] = $row_item;
    }
    
    if (count($records_list) > 0) {
        $first_row_item = $records_list[0];
        $exp_stmt_bal = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM ledger WHERE transaction_date < ? OR (transaction_date = ? AND ledger_id <= ?)");
        $exp_stmt_bal->bind_param("ssi", $first_row_item['transaction_date'], $first_row_item['transaction_date'], $first_row_item['ledger_id']);
        $exp_stmt_bal->execute();
        $curr_bal = $exp_stmt_bal->get_result()->fetch_assoc()['total'];
        
        foreach ($records_list as $idx_val => &$row_item) {
            if ($srt == 'asc' && $idx_val > 0) {
                $curr_bal += $row_item['amount'];
            }
            $row_item['running_balance'] = $curr_bal;
            if ($srt == 'desc') {
                $curr_bal -= $row_item['amount'];
            }
        }
    }
    
    echo json_encode($records_list);
    exit();
}

include 'components/sidebar.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: ../login.php");
    exit();
}

// Filters
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : '';
$show_id = isset($_GET['show_id']) ? (int)$_GET['show_id'] : '';
$transaction_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$sort = isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'asc' : 'desc';

// Pagination
$records_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Build WHERE clause
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($movie_id) {
    $where_clauses[] = "l.movie_id = ?";
    $params[] = $movie_id;
    $types .= "i";
}
if ($show_id) {
    $where_clauses[] = "l.show_id = ?";
    $params[] = $show_id;
    $types .= "i";
}
if ($transaction_type == 'BOOKING' || $transaction_type == 'CANCELLATION') {
    $where_clauses[] = "l.transaction_type = ?";
    $params[] = $transaction_type;
    $types .= "s";
}
if ($date_from) {
    $where_clauses[] = "DATE(l.transaction_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if ($date_to) {
    $where_clauses[] = "DATE(l.transaction_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Count Query
$count_query = "SELECT COUNT(*) as total FROM ledger l WHERE $where_sql";
$stmt_count = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main Query without subquery
$order_sql = $sort == 'asc' ? 'l.transaction_date ASC, l.ledger_id ASC' : 'l.transaction_date DESC, l.ledger_id DESC';

$query = "
    SELECT l.*, 
           b.booking_id as bk_display_id,
           m.title as movie_name,
           s.show_date, s.show_time, 
           sc.screen_name,
           u.full_name as customer_name
    FROM ledger l
    JOIN bookings b ON l.booking_id = b.booking_id
    JOIN movies m ON l.movie_id = m.movie_id
    JOIN shows s ON l.show_id = s.show_id
    JOIN screens sc ON s.screen_id = sc.screen_id
    JOIN users u ON b.user_id = u.user_id
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT ?, ?
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $bind_params = $params;
    $bind_params[] = $offset;
    $bind_params[] = $records_per_page;
    $bind_types = $types . "ii";
    $stmt->bind_param($bind_types, ...$bind_params);
} else {
    $stmt->bind_param("ii", $offset, $records_per_page);
}
$stmt->execute();
$ledger_records = $stmt->get_result();

$records = [];
while ($row = $ledger_records->fetch_assoc()) {
    $records[] = $row;
}

// Calculate running balance precisely in PHP
if (count($records) > 0) {
    $first_row = $records[0];
    
    // Get the absolute balance up to the first displayed row
    $stmt_bal = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM ledger WHERE transaction_date < ? OR (transaction_date = ? AND ledger_id <= ?)");
    $stmt_bal->bind_param("ssi", $first_row['transaction_date'], $first_row['transaction_date'], $first_row['ledger_id']);
    $stmt_bal->execute();
    $current_balance = $stmt_bal->get_result()->fetch_assoc()['total'];

    foreach ($records as $index => &$row) {
        if ($sort == 'asc' && $index > 0) {
            $current_balance += $row['amount'];
        }
        
        $row['running_balance'] = $current_balance;
        
        if ($sort == 'desc') {
            $current_balance -= $row['amount'];
        }
    }
}

// Fetch movies for filter dropdown
$movies = $conn->query("SELECT movie_id, title FROM movies ORDER BY title ASC");
// Fetch shows for filter dropdown (can be refined via JS based on selected movie)
$shows = $conn->query("
    SELECT s.show_id, m.title, s.show_date, s.show_time 
    FROM shows s 
    JOIN movies m ON s.movie_id = m.movie_id 
    ORDER BY s.show_date DESC, s.show_time DESC
");

// Build query string for pagination links to preserve filters
$qs_array = $_GET;
unset($qs_array['page']);
$qs = http_build_query($qs_array);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ledger - Admin Panel</title>
    <link rel="stylesheet" href="../Assets/css/Admin/ledger.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>

    </style>
</head>
<body>
<div class="main-container">
    <div class="content-area">
        <div class="page-header">
            <div class="page-title">
                <div class="title-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <h1>Financial Ledger</h1>
                    <p>Track all financial transactions, bookings, and cancellations.</p>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="filter-card">
            <form method="GET" action="ledger.php" class="filter-form">
                <div class="form-group">
                    <label>Movie</label>
                    <select name="movie_id" id="filter_movie">
                        <option value="">All Movies</option>
                        <?php while($m = $movies->fetch_assoc()): ?>
                            <option value="<?= $m['movie_id'] ?>" <?= $movie_id == $m['movie_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Show</label>
                    <select name="show_id" id="filter_show">
                        <option value="">All Shows</option>
                        <?php while($sh = $shows->fetch_assoc()): ?>
                            <option value="<?= $sh['show_id'] ?>" <?= $show_id == $sh['show_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sh['title']) ?> - <?= date('d M', strtotime($sh['show_date'])) ?> <?= date('h:i A', strtotime($sh['show_time'])) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="transaction_type">
                        <option value="">All Types</option>
                        <option value="BOOKING" <?= $transaction_type == 'BOOKING' ? 'selected' : '' ?>>Booking</option>
                        <option value="CANCELLATION" <?= $transaction_type == 'CANCELLATION' ? 'selected' : '' ?>>Cancellation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="form-group">
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="desc" <?= $sort == 'desc' ? 'selected' : '' ?>>Latest First</option>
                        <option value="asc" <?= $sort == 'asc' ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="submit-btn"><i class="fas fa-filter"></i> Filter</button>
                    <a href="ledger.php" class="reset-btn">Clear</a>
                    <button type="button" class="export-btn" id="btnOpenExport"><i class="fas fa-file-excel"></i> Export to Excel</button>
                </div>
            </form>
        </div>

        <!-- Ledger Table -->
        <div class="table-container" style="margin-top: 20px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>ID</th>
                        <th>Movie</th>
                        <th>Show Info</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td class="nowrap"><?= date("Y-m-d H:i", strtotime($row['transaction_date'])) ?></td>
                                <td>BK<?= str_pad($row['bk_display_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><strong><?= htmlspecialchars($row['movie_name']) ?></strong></td>
                                <td class="meta-info">
                                    <?= date("d M Y", strtotime($row['show_date'])) ?> <?= date("H:i", strtotime($row['show_time'])) ?> (<?= htmlspecialchars($row['screen_name']) ?>)
                                </td>
                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td>
                                    <span class="type-badge <?= strtolower($row['transaction_type']) ?>">
                                        <?= htmlspecialchars($row['transaction_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['amount'] >= 0): ?>
                                        <span class="amt-positive">+ Rs. <?= number_format($row['amount'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="amt-negative">- Rs. <?= number_format(abs($row['amount']), 2) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="balance-cell">
                                    Rs. <?= number_format($row['running_balance'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">No ledger transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container" style="margin-top: 25px; display: flex; justify-content: center; gap: 10px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="ledger.php?page=<?= $i; ?>&<?= $qs ?>" 
                   style="padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s;
                   <?= $i == $page ? 'background: #ff4d2d; color: white;' : 'background: white; color: #555; border: 1px solid #ddd;'; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    <!-- Export Excel Modal -->
    <div id="exportModal" class="export-modal-overlay" style="display: none;">
        <div class="export-modal-card">
            <div class="modal-header">
                <h3><i class="fas fa-file-excel"></i> Export Ledger to Excel</h3>
                <span class="modal-close" id="btnCloseExportModal">&times;</span>
            </div>
            
            <div class="modal-body">
                <!-- Step 1: Input Limit & Filters Info -->
                <div id="exportStepSetup">
                    <div class="form-group-modal">
                        <label for="exportLimitRows">How many rows would you like to export?</label>
                        <input type="number" id="exportLimitRows" placeholder="e.g. 500 (Leave blank for all)" min="1">
                        <p class="input-hint">Leave blank to export all matching records based on your active filters.</p>
                    </div>
                </div>

                <!-- Step 2: Loading State -->
                <div id="exportStepLoading" style="display: none; text-align: center; padding: 20px 0;">
                    <div class="modal-spinner"></div>
                    <p style="margin-top: 15px; font-weight: 500; color: #555;">Exporting transactions... Please wait.</p>
                    
                    <div class="export-recommendation-box" style="margin-top: 20px; text-align: left;">
                        <div class="recommendation-header" style="margin-bottom: 10px;">
                            <i class="fas fa-info-circle"></i>
                            <strong>Exporting filtered records only:</strong>
                        </div>
                        <ul id="exportActiveFiltersList" style="list-style: none; padding-left: 0; font-size: 13px; color: #555; line-height: 1.6; margin: 0;">
                            <!-- populated dynamically by JS -->
                        </ul>
                    </div>
                </div>

                <!-- Step 3: Success State -->
                <div id="exportStepSuccess" style="display: none; text-align: center; padding: 20px 0;">
                    <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                    <p id="exportSuccessMessage" style="margin-top: 15px; font-weight: 600; color: #27ae60; font-size: 16px;"></p>
                </div>
            </div>

            <div class="modal-footer" id="exportModalFooter">
                <button type="button" class="modal-btn-cancel" id="btnCancelExport">Cancel</button>
                <button type="button" class="modal-btn-confirm" id="btnConfirmExport">Export Now</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const btnOpenExport = document.getElementById("btnOpenExport");
        const exportModal = document.getElementById("exportModal");
        const btnCloseExportModal = document.getElementById("btnCloseExportModal");
        const btnCancelExport = document.getElementById("btnCancelExport");
        const btnConfirmExport = document.getElementById("btnConfirmExport");
        
        const exportStepSetup = document.getElementById("exportStepSetup");
        const exportStepLoading = document.getElementById("exportStepLoading");
        const exportStepSuccess = document.getElementById("exportStepSuccess");
        const exportModalFooter = document.getElementById("exportModalFooter");
        const exportLimitRows = document.getElementById("exportLimitRows");
        const exportSuccessMessage = document.getElementById("exportSuccessMessage");
        const exportActiveFiltersList = document.getElementById("exportActiveFiltersList");

        // Open Modal
        btnOpenExport.addEventListener("click", function() {
            exportStepSetup.style.display = "block";
            exportStepLoading.style.display = "none";
            exportStepSuccess.style.display = "none";
            exportModalFooter.style.display = "flex";
            exportLimitRows.value = "";
            exportModal.style.display = "flex";
        });

        // Close Modal
        function closeModal() {
            exportModal.style.display = "none";
        }
        
        btnCloseExportModal.addEventListener("click", closeModal);
        btnCancelExport.addEventListener("click", closeModal);
        
        // Close on click outside card
        exportModal.addEventListener("click", function(event) {
            if (event.target === exportModal) {
                closeModal();
            }
        });

        // Confirm & Execute Export
        btnConfirmExport.addEventListener("click", function() {
            const urlParams = new URLSearchParams();
            
            const movieSelect = document.getElementById("filter_movie");
            const movieVal = movieSelect.value;
            const movieText = movieSelect.options[movieSelect.selectedIndex].text;
            
            const showSelect = document.getElementById("filter_show");
            const showVal = showSelect.value;
            const showText = showSelect.options[showSelect.selectedIndex].text;
            
            const typeSelect = document.querySelector("select[name='transaction_type']");
            const typeVal = typeSelect.value;
            const typeText = typeSelect.options[typeSelect.selectedIndex].text;
            
            const fromVal = document.querySelector("input[name='date_from']").value;
            const toVal = document.querySelector("input[name='date_to']").value;
            const sortSelect = document.querySelector("select[name='sort']");
            const sortVal = sortSelect.value;
            const sortText = sortSelect.options[sortSelect.selectedIndex].text;
            
            const limitVal = exportLimitRows.value.trim();
            
            if (movieVal) urlParams.set("movie_id", movieVal);
            if (showVal) urlParams.set("show_id", showVal);
            if (typeVal) urlParams.set("transaction_type", typeVal);
            if (fromVal) urlParams.set("date_from", fromVal);
            if (toVal) urlParams.set("date_to", toVal);
            if (sortVal) urlParams.set("sort", sortVal);
            if (limitVal !== "" && !isNaN(limitVal)) {
                urlParams.set("limit", limitVal);
            }
            
            // Build the active filters list HTML to show while exporting
            let filterHtml = "";
            filterHtml += `<li><strong>Movie:</strong> ${movieVal ? movieText : 'All Movies'}</li>`;
            filterHtml += `<li><strong>Show:</strong> ${showVal ? showText : 'All Shows'}</li>`;
            filterHtml += `<li><strong>Type:</strong> ${typeVal ? typeText : 'All Types'}</li>`;
            if (fromVal || toVal) {
                filterHtml += `<li><strong>Date Range:</strong> ${fromVal || 'Any'} to ${toVal || 'Any'}</li>`;
            }
            filterHtml += `<li><strong>Sort By:</strong> ${sortText}</li>`;
            if (limitVal) {
                filterHtml += `<li><strong>Limit Rows:</strong> ${limitVal}</li>`;
            }
            exportActiveFiltersList.innerHTML = filterHtml;
            
            // Show loading state
            exportStepSetup.style.display = "none";
            exportModalFooter.style.display = "none";
            exportStepLoading.style.display = "block";
            
            // Trigger AJAX fetch to get JSON data
            fetch("ledger.php?action=get_export_data&" + urlParams.toString())
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        alert("Error: " + data.error);
                        closeModal();
                        return;
                    }
                    
                    // Convert JSON to CSV data format (compatible with Excel)
                    let csvContent = "\uFEFF"; // UTF-8 BOM for Excel auto-encoding
                    const headers = ["Date & Time", "Booking ID", "Movie Name", "Show Date", "Show Time", "Screen Name", "Customer Name", "Transaction Type", "Amount", "Running Balance"];
                    csvContent += headers.map(h => `"${h}"`).join(",") + "\r\n";
                    
                    data.forEach(row => {
                        const formattedDate = row.transaction_date;
                        const bookingId = "BK" + String(row.bk_display_id).padStart(4, '0');
                        const movieName = row.movie_name;
                        const showDate = row.show_date;
                        const showTime = row.show_time;
                        const screenName = row.screen_name;
                        const customerName = row.customer_name;
                        const type = row.transaction_type;
                        const amount = (type === 'CANCELLATION' ? '-' : '+') + ' Rs. ' + Math.abs(row.amount).toFixed(2);
                        const balance = 'Rs. ' + parseFloat(row.running_balance).toFixed(2);
                        
                        const rowData = [formattedDate, bookingId, movieName, showDate, showTime, screenName, customerName, type, amount, balance];
                        csvContent += rowData.map(val => `"${String(val).replace(/"/g, '""')}"`).join(",") + "\r\n";
                    });
                    
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const downloadLink = document.createElement("a");
                    downloadLink.href = url;
                    downloadLink.download = "Ledger_Transactions_Export.csv";
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    
                    // Set success state
                    exportStepLoading.style.display = "none";
                    exportStepSuccess.style.display = "block";
                    exportSuccessMessage.textContent = `Success! ${data.length} transactions exported to Excel.`;
                    
                    setTimeout(closeModal, 3000);
                })
                .catch(error => {
                    console.error("Export failure:", error);
                    alert("Failed to export. Please try again.");
                    closeModal();
                });
        });
    });
    </script>
    </div>
</div>
</body>
</html>
