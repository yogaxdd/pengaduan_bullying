<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die('Invalid request.');
}

$report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
$message = sanitizeInput($_POST['message'] ?? '');

if (!$report_id || !$message) {
    header('Location: dashboard.php');
    exit;
}

$db = getDBConnection();
$admin_id = getCurrentAdminId();

try {
    // Insert reply message
    $stmt = $db->prepare("
        INSERT INTO report_messages (report_id, sender, sender_id, message) 
        VALUES (?, 'admin', ?, ?)
    ");
    $stmt->execute([$report_id, $admin_id, $message]);
    
    // Update report's updated_at timestamp
    $stmt = $db->prepare("UPDATE reports SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$report_id]);
    
    // Add audit log
    addAuditLog('reply_sent', 'report', $report_id, 'Sent reply to reporter');
    
    // Redirect back to report view
    header('Location: report_view.php?id=' . $report_id . '&success=reply_sent');
    
} catch (Exception $e) {
    error_log('Reply error: ' . $e->getMessage());
    header('Location: report_view.php?id=' . $report_id . '&error=reply_failed');
}
?>
