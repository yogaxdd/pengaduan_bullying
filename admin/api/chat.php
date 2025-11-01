<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/session.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDBConnection();
$admin = getCurrentAdmin();

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_chats':
        // Get list of reports with recent messages
        $stmt = $db->prepare("
            SELECT r.id, r.tracking_code, r.status, r.urgency_level, c.name as category_name,
                   (SELECT COUNT(*) FROM report_messages WHERE report_id = r.id AND sender = 'reporter' AND is_read = 0) as unread_count,
                   (SELECT message FROM report_messages WHERE report_id = r.id ORDER BY created_at DESC LIMIT 1) as last_message,
                   (SELECT created_at FROM report_messages WHERE report_id = r.id ORDER BY created_at DESC LIMIT 1) as last_message_time
            FROM reports r
            LEFT JOIN categories c ON r.category_id = c.id
            WHERE r.status NOT IN ('closed')
            HAVING last_message_time IS NOT NULL
            ORDER BY last_message_time DESC
            LIMIT 20
        ");
        $stmt->execute();
        $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'chats' => $chats]);
        break;
        
    case 'get_messages':
        $report_id = filter_input(INPUT_GET, 'report_id', FILTER_VALIDATE_INT);
        if (!$report_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid report ID']);
            exit;
        }
        
        // Get report details
        $stmt = $db->prepare("
            SELECT r.*, c.name as category_name
            FROM reports r
            LEFT JOIN categories c ON r.category_id = c.id
            WHERE r.id = ?
        ");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found']);
            exit;
        }
        
        // Get messages
        $stmt = $db->prepare("
            SELECT m.*, u.full_name as admin_name
            FROM report_messages m
            LEFT JOIN admin_users u ON m.sender_id = u.id
            WHERE m.report_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$report_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark reporter messages as read
        $stmt = $db->prepare("UPDATE report_messages SET is_read = 1 WHERE report_id = ? AND sender = 'reporter'");
        $stmt->execute([$report_id]);
        
        echo json_encode([
            'success' => true, 
            'report' => $report,
            'messages' => $messages
        ]);
        break;
        
    case 'send_message':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
        $message = trim($_POST['message'] ?? '');
        
        if (!$report_id || !$message) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
        
        try {
            // Insert message
            $stmt = $db->prepare("
                INSERT INTO report_messages (report_id, sender, sender_id, message) 
                VALUES (?, 'admin', ?, ?)
            ");
            $stmt->execute([$report_id, $admin['id'], $message]);
            
            $message_id = $db->lastInsertId();
            
            // Update report timestamp
            $stmt = $db->prepare("UPDATE reports SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$report_id]);
            
            // Add audit log
            addAuditLog('send_message', 'report', $report_id, 'Sent message via chat');
            
            // Get the inserted message
            $stmt = $db->prepare("
                SELECT m.*, u.full_name as admin_name
                FROM report_messages m
                LEFT JOIN admin_users u ON m.sender_id = u.id
                WHERE m.id = ?
            ");
            $stmt->execute([$message_id]);
            $new_message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => $new_message
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send message']);
        }
        break;
        
    case 'get_unread_count':
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM report_messages 
            WHERE sender = 'reporter' AND is_read = 0
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'unread_count' => (int)$result['count']
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
