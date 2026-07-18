<?php
session_start();
require_once "../Includes/db_conn.php";
require_once "../Includes/mail_config.php";
include "components/sidebar.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid Request'); window.location.href='contact_messages.php';</script>";
    exit();
}
$msg_id = (int)$_GET['id'];
$admin_id = $_SESSION['user_id'] ?? null;

$success = "";
$error = "";

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("CSRF Token Validation Failed.");
    }
    $action = $_POST['action'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $status = $_POST['status'] ?? '';
    $admin_reply = trim($_POST['admin_reply'] ?? '');

    if ($action === 'close') {
        $upq = "UPDATE contact_messages SET status = 'CLOSED', updated_at = NOW() WHERE message_id = ?";
        $stmt = $conn->prepare($upq);
        $stmt->bind_param("i", $msg_id);
        if ($stmt->execute()) {
            $success = "Inquiry has been closed.";
        } else {
            $error = "Failed to close inquiry.";
        }
        $stmt->close();
    } elseif ($action === 'save_only') {
        $upq = "UPDATE contact_messages SET priority = ?, status = ?, admin_reply = ?, assigned_to = ?, updated_at = NOW() WHERE message_id = ?";
        $stmt = $conn->prepare($upq);
        $stmt->bind_param("sssii", $priority, $status, $admin_reply, $admin_id, $msg_id);
        if ($stmt->execute()) {
            $success = "Changes saved successfully.";
        } else {
            $error = "Failed to save changes.";
        }
        $stmt->close();
    } elseif ($action === 'send_reply') {
        // We need customer email to send
        $e_query = "SELECT email, full_name, subject FROM contact_messages WHERE message_id = ?";
        $e_stmt = $conn->prepare($e_query);
        $e_stmt->bind_param("i", $msg_id);
        $e_stmt->execute();
        $e_res = $e_stmt->get_result();
        $msg_data = $e_res->fetch_assoc();
        $e_stmt->close();

        if ($msg_data && !empty($admin_reply)) {
            $upq = "UPDATE contact_messages SET priority = ?, status = 'REPLIED', admin_reply = ?, assigned_to = ?, replied_at = NOW(), updated_at = NOW() WHERE message_id = ?";
            $stmt = $conn->prepare($upq);
            $stmt->bind_param("ssii", $priority, $admin_reply, $admin_id, $msg_id);
            if ($stmt->execute()) {
                // Send email
                $to = $msg_data['email'];
                $subject = "RE: " . $msg_data['subject'] . " (MTBS Cinema Support)";
                $body = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                    <h3>Hello " . htmlspecialchars($msg_data['full_name']) . ",</h3>
                    <p>Thank you for reaching out to us regarding <strong>\"" . htmlspecialchars($msg_data['subject']) . "\"</strong>.</p>
                    <hr>
                    <p><strong>Admin Reply:</strong></p>
                    <p>" . nl2br(htmlspecialchars($admin_reply)) . "</p>
                    <hr>
                    <p>Regards,<br><strong>MTBS Cinema Support Team</strong></p>
                </div>";
                
                $mailStatus = sendMail($to, $subject, $body);
                if ($mailStatus['success']) {
                    $success = "Reply sent and saved successfully.";
                } else {
                    $error = "Reply saved but email failed to send: " . $mailStatus['message'];
                }
            } else {
                $error = "Failed to save reply.";
            }
            $stmt->close();
        } else {
            $error = "Reply message cannot be empty.";
        }
    }
}

// Auto change status from NEW to READ if viewing
$check_q = "SELECT status FROM contact_messages WHERE message_id = ?";
if ($s = $conn->prepare($check_q)) {
    $s->bind_param("i", $msg_id);
    $s->execute();
    $res = $s->get_result();
    $row = $res->fetch_assoc();
    if ($row && $row['status'] === 'NEW') {
        $u_s = $conn->prepare("UPDATE contact_messages SET status = 'READ' WHERE message_id = ?");
        $u_s->bind_param("i", $msg_id);
        $u_s->execute();
        $u_s->close();
    }
    $s->close();
}

// Fetch Full details
$q = "
SELECT c.*, u.full_name as admin_name 
FROM contact_messages c 
LEFT JOIN users u ON c.assigned_to = u.user_id 
WHERE c.message_id = ?";
$stmt = $conn->prepare($q);
$stmt->bind_param("i", $msg_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo "<script>alert('Message not found'); window.location.href='contact_messages.php';</script>";
    exit();
}
$msg = $result->fetch_assoc();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message #<?= $msg_id ?> - Admin</title>
    <link rel="stylesheet" href="../Assets/css/Admin/admin_dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../Assets/css/Admin/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .view-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-top: 20px;
        }
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            margin-bottom: 15px;
        }
        .detail-label { font-weight: bold; color: var(--text-secondary); }
        .detail-value { color: var(--text-dark); }
        
        .message-box {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            margin: 20px 0;
            white-space: pre-wrap;
        }
        .reply-box {
            margin-top: 30px;
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }
        .reply-box h3 { margin-bottom: 15px; }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: inherit;
            margin-bottom: 15px;
        }
        textarea.form-control { resize: vertical; min-height: 150px; }
        .btn-group { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; color: white; transition: 0.3s; font-weight: 500; }
        .btn-send { background: #10b981; }
        .btn-send:hover { background: #059669; }
        .btn-save { background: #3b82f6; }
        .btn-save:hover { background: #2563eb; }
        .btn-close { background: #6b7280; }
        .btn-close:hover { background: #4b5563; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        
        .controls-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .control-group label { display: block; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="page-header" style="margin-bottom:0;">
        <div class="page-title">
            <i class="fa-solid fa-envelope-open-text"></i>
            <div>
                <h1>Message #<?= $msg_id ?></h1>
                <p>View and reply to customer inquiry.</p>
            </div>
        </div>
    </div>

    <div class="view-container">
        
        <?php if (!empty($success)): ?>
            <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($success) ?>, 'success'));</script>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($error) ?>, 'error'));</script>
        <?php endif; ?>

        <div class="header-row">
            <h2>Subject: <?= htmlspecialchars($msg['subject']) ?></h2>
            <a href="contact_messages.php" style="color: var(--text-secondary); text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
        </div>

        <div class="detail-row">
            <div class="detail-label">Customer Name</div>
            <div class="detail-value"><?= htmlspecialchars($msg['full_name']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Email</div>
            <div class="detail-value"><a href="mailto:<?= htmlspecialchars($msg['email']) ?>"><?= htmlspecialchars($msg['email']) ?></a></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Phone</div>
            <div class="detail-value"><?= htmlspecialchars($msg['phone']) ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Submitted On</div>
            <div class="detail-value"><?= date('d M Y h:i A', strtotime($msg['submitted_at'])) ?> (IP: <?= htmlspecialchars($msg['ip_address']) ?>)</div>
        </div>

        <div class="message-box">
            <strong>Message:</strong><br><br>
            <?= htmlspecialchars($msg['message']) ?>
        </div>

        <form method="POST" class="reply-box" data-loader-msg="Sending email reply. Please wait...">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <h3>Admin Actions & Reply</h3>
            
            <div class="controls-row">
                <div class="control-group">
                    <label>Priority</label>
                    <select name="priority" class="form-control" style="width: auto;">
                        <option value="LOW" <?= $msg['priority']=='LOW'?'selected':'' ?>>LOW</option>
                        <option value="MEDIUM" <?= $msg['priority']=='MEDIUM'?'selected':'' ?>>MEDIUM</option>
                        <option value="HIGH" <?= $msg['priority']=='HIGH'?'selected':'' ?>>HIGH</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>Status</label>
                    <select name="status" class="form-control" style="width: auto;">
                        <option value="NEW" <?= $msg['status']=='NEW'?'selected':'' ?>>NEW</option>
                        <option value="READ" <?= $msg['status']=='READ'?'selected':'' ?>>READ</option>
                        <option value="REPLIED" <?= $msg['status']=='REPLIED'?'selected':'' ?>>REPLIED</option>
                        <option value="CLOSED" <?= $msg['status']=='CLOSED'?'selected':'' ?>>CLOSED</option>
                    </select>
                </div>
                <?php if($msg['admin_name']): ?>
                <div class="control-group">
                    <label>Last Handled By</label>
                    <div style="padding: 10px 0;"><strong><?= htmlspecialchars($msg['admin_name']) ?></strong></div>
                </div>
                <?php endif; ?>
                <?php if($msg['replied_at']): ?>
                <div class="control-group">
                    <label>Replied At</label>
                    <div style="padding: 10px 0;"><?= date('d M Y h:i A', strtotime($msg['replied_at'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <label><strong>Write Reply to Customer:</strong></label>
            <textarea name="admin_reply" class="form-control" placeholder="Type your response here..."><?= htmlspecialchars($msg['admin_reply'] ?? '') ?></textarea>

            <div class="btn-group">
                <button type="submit" name="action" value="send_reply" class="btn btn-send" onclick="return confirm('Send email to customer?');"><i class="fa-solid fa-paper-plane"></i> Send Reply</button>
                <button type="submit" name="action" value="save_only" class="btn btn-save"><i class="fa-solid fa-floppy-disk"></i> Save Only</button>
                <button type="submit" name="action" value="close" class="btn btn-close" onclick="return confirm('Close this inquiry?');"><i class="fa-solid fa-lock"></i> Close Inquiry</button>
            </div>
        </form>

    </div>
</div>

</body>
</html>
