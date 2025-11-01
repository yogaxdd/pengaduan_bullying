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
$new_status = sanitizeInput($_POST['status'] ?? '');
$assigned_to = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT);
$notes = sanitizeInput($_POST['notes'] ?? '');

if (!$report_id) {
    header('Location: dashboard.php');
    exit;
}

$db = getDBConnection();

try {
    $db->beginTransaction();
    
    // Get current report status
    $stmt = $db->prepare("SELECT status, tracking_code FROM reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    if (!$report) {
        throw new Exception('Report not found');
    }
    
    $updates = [];
    $params = [];
    
    // Update status if changed
    if ($new_status && $new_status !== $report['status']) {
        $updates[] = "status = ?";
        $params[] = $new_status;
        
        // Add audit log for status change
        addAuditLog('status_changed', 'report', $report_id, 
                   "Status changed from {$report['status']} to {$new_status}. Notes: {$notes}");
        
        // Create notification message for reporter
        $status_messages = [
            'reviewed' => 'Laporan Anda sedang ditinjau oleh tim konselor.',
            'escalated' => 'Laporan Anda telah dieskalasi untuk penanganan lebih lanjut.',
            'resolved' => 'Laporan Anda telah diselesaikan. Terima kasih atas laporan Anda.',
            'closed' => 'Laporan Anda telah ditutup.'
        ];
        
        if (isset($status_messages[$new_status])) {
            // Insert automatic message
            $stmt = $db->prepare("
                INSERT INTO report_messages (report_id, sender, sender_id, message) 
                VALUES (?, 'admin', ?, ?)
            ");
            $message = "📢 Status Update: " . $status_messages[$new_status];
            if ($notes) {
                $message .= "\n\nCatatan: " . $notes;
            }
            $stmt->execute([$report_id, getCurrentAdminId(), $message]);
        }
    }
    
    // Update assignment
    if ($assigned_to !== null) {
        $updates[] = "assigned_to = ?";
        $params[] = $assigned_to ?: null;
        
        if ($assigned_to) {
            // Get assigned admin name
            $stmt = $db->prepare("SELECT full_name FROM admin_users WHERE id = ?");
            $stmt->execute([$assigned_to]);
            $assigned_admin = $stmt->fetch();
            
            addAuditLog('report_assigned', 'report', $report_id, 
                       "Report assigned to {$assigned_admin['full_name']}");
            
            // Create notification for assigned admin
            createNotification($assigned_to, 'assignment', 
                             "Laporan baru di-assign kepada Anda", 
                             "Kode laporan: {$report['tracking_code']}", $report_id);
        } else {
            addAuditLog('report_unassigned', 'report', $report_id, 
                       "Report assignment removed");
        }
    }
    
    // Execute update if there are changes
    if (!empty($updates)) {
        $updates[] = "updated_at = NOW()";
        $params[] = $report_id;
        
        $sql = "UPDATE reports SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }
    
    $db->commit();
    
    // Redirect back with success message
    header('Location: report_view.php?id=' . $report_id . '&success=status_updated');
    
} catch (Exception $e) {
    $db->rollBack();
    error_log('Status update error: ' . $e->getMessage());
    header('Location: report_view.php?id=' . $report_id . '&error=update_failed');
}
?>
