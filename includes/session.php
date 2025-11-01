<?php
/**
 * Session Management & Security Functions
 */

session_start();

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Get current admin ID
 */
function getCurrentAdminId() {
    return isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
}

/**
 * Get current admin data
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    require_once dirname(__DIR__) . '/config/database.php';
    $db = getDBConnection();
    
    $stmt = $db->prepare("SELECT id, username, email, full_name, role FROM admin_users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}

/**
 * Login admin
 */
function loginAdmin($adminId, $username, $role) {
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_role'] = $role;
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Update last login
    require_once dirname(__DIR__) . '/config/database.php';
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$adminId]);
}

/**
 * Logout admin
 */
function logoutAdmin() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if admin is superadmin
 */
function isSuperAdmin() {
    return isAdminLoggedIn() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin';
}

/**
 * Rate limiting check
 */
function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    require_once dirname(__DIR__) . '/config/database.php';
    $db = getDBConnection();
    $ip = getClientIP();
    
    // Clean old records
    $stmt = $db->prepare("DELETE FROM rate_limit WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$timeWindow]);
    
    // Check current attempts
    $stmt = $db->prepare("SELECT attempts FROM rate_limit WHERE ip_address = ? AND action = ?");
    $stmt->execute([$ip, $action]);
    $result = $stmt->fetch();
    
    if ($result && $result['attempts'] >= $maxAttempts) {
        return false; // Rate limit exceeded
    }
    
    // Update or insert attempt
    $stmt = $db->prepare("
        INSERT INTO rate_limit (ip_address, action, attempts, last_attempt) 
        VALUES (?, ?, 1, NOW()) 
        ON DUPLICATE KEY UPDATE 
        attempts = attempts + 1, 
        last_attempt = NOW()
    ");
    $stmt->execute([$ip, $action]);
    
    return true;
}

/**
 * Add audit log entry
 */
function addAuditLog($action, $targetType = null, $targetId = null, $details = null) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    require_once dirname(__DIR__) . '/config/database.php';
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        getCurrentAdminId(),
        $action,
        $targetType,
        $targetId,
        $details,
        getClientIP(),
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    return true;
}

/**
 * Create notification for admin
 */
function createNotification($adminId, $type, $title, $message = null, $relatedId = null) {
    require_once dirname(__DIR__) . '/config/database.php';
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO notifications (admin_id, type, title, message, related_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$adminId, $type, $title, $message, $relatedId]);
}

/**
 * Notify all admins about new report
 */
function notifyNewReport($reportId, $urgencyLevel) {
    require_once dirname(__DIR__) . '/config/database.php';
    $db = getDBConnection();
    
    // Get all active admins
    $stmt = $db->prepare("SELECT id FROM admin_users WHERE is_active = 1");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
    $urgencyText = $urgencyLevel === 'emergency' ? 'DARURAT' : ($urgencyLevel === 'high' ? 'TINGGI' : 'Normal');
    $title = "Laporan Baru - Prioritas: $urgencyText";
    $message = "Ada laporan baru dengan tingkat urgensi $urgencyText yang perlu ditinjau.";
    
    foreach ($admins as $admin) {
        createNotification($admin['id'], 'new_report', $title, $message, $reportId);
    }
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $maxSize = null, $allowedExts = null) {
    if ($maxSize === null) {
        $maxSize = MAX_FILE_SIZE;
    }
    
    if ($allowedExts === null) {
        $allowedExts = ALLOWED_EXTENSIONS;
    }
    
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }
    
    // Check upload error
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => true, 'message' => 'No file uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'File terlalu besar'];
        default:
            return ['success' => false, 'message' => 'Upload gagal'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File terlalu besar (maksimal ' . ($maxSize / 1024 / 1024) . 'MB)'];
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    // Check MIME type for extra security
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $allowedMimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime'
    ];
    
    if (isset($allowedMimeTypes[$ext]) && $mimeType !== $allowedMimeTypes[$ext]) {
        return ['success' => false, 'message' => 'File tidak valid'];
    }
    
    return ['success' => true, 'extension' => $ext];
}
?>
