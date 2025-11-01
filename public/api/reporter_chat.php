<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/session.php';

// Check if reporter is logged in
if (!isset($_SESSION['report_tracking'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDBConnection();
$tracking_code = $_SESSION['report_tracking'];

// Get report ID
$stmt = $db->prepare("SELECT id FROM reports WHERE tracking_code = ?");
$stmt->execute([$tracking_code]);
$report = $stmt->fetch();

if (!$report) {
    http_response_code(404);
    echo json_encode(['error' => 'Report not found']);
    exit;
}

$report_id = $report['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_messages':
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
        
        // Mark admin messages as read
        $stmt = $db->prepare("UPDATE report_messages SET is_read = 1 WHERE report_id = ? AND sender = 'admin'");
        $stmt->execute([$report_id]);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
        break;
        
    case 'send_message':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $message = trim($_POST['message'] ?? '');
        
        if (!$message) {
            http_response_code(400);
            echo json_encode(['error' => 'Message is required']);
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
                INSERT INTO report_messages (report_id, sender, message) 
                VALUES (?, 'reporter', ?)
            ");
            $stmt->execute([$report_id, $message]);
            
            $message_id = $db->lastInsertId();
            
            // Update report timestamp
            $stmt = $db->prepare("UPDATE reports SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$report_id]);
            
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
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM report_messages 
            WHERE report_id = ? AND sender = 'admin' AND is_read = 0
        ");
        $stmt->execute([$report_id]);
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
