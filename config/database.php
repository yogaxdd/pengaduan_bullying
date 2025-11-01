<?php
/**
 * Database Configuration
 * Sesuaikan dengan konfigurasi XAMPP Anda
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'pengaduan_bullying');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password kosong
define('DB_CHARSET', 'utf8mb4');

// Upload configuration
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/'); // Di luar webroot
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'mp4', 'avi', 'mov']);

// Security configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Site configuration
define('SITE_NAME', 'Sistem Pengaduan Bullying');
define('SITE_URL', 'http://localhost/pengaduan_bullying');
define('ADMIN_EMAIL', 'admin@sekolah.id');

// Timezone
date_default_timezone_set('Asia/Jakarta');

/**
 * Database Connection Function
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error securely, don't expose to user
            error_log('Database connection failed: ' . $e->getMessage());
            die('Terjadi kesalahan koneksi. Silakan coba lagi.');
        }
    }
    
    return $pdo;
}

/**
 * Generate secure random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

/**
 * Generate secure PIN
 */
function generatePIN($length = 6) {
    $pin = '';
    for ($i = 0; $i < $length; $i++) {
        $pin .= random_int(0, 9);
    }
    return $pin;
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                return $ip;
            }
        }
    }
    
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Create upload directory if not exists
 */
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
    
    // Create .htaccess to prevent direct access
    $htaccess = UPLOAD_PATH . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order Deny,Allow\nDeny from all");
    }
}
?>
