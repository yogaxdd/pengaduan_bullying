<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Check rate limiting
        if (!checkRateLimit('admin_login', MAX_LOGIN_ATTEMPTS, LOCKOUT_TIME)) {
            $error = 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.';
        } else {
            $username = sanitizeInput($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if ($username && $password) {
                $db = getDBConnection();
                
                // Get admin user
                $stmt = $db->prepare("
                    SELECT id, username, password_hash, role, is_active 
                    FROM admin_users 
                    WHERE (username = ? OR email = ?) AND is_active = 1
                ");
                $stmt->execute([$username, $username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password_hash'])) {
                    // Login successful
                    loginAdmin($admin['id'], $admin['username'], $admin['role']);
                    addAuditLog('login', 'admin', $admin['id'], 'Successful login');
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Username/email atau password salah.';
                    
                    // Log failed attempt
                    if ($admin) {
                        addAuditLog('login_failed', 'admin', $admin['id'], 'Failed login attempt');
                    }
                }
            } else {
                $error = 'Mohon isi semua field.';
            }
        }
    }
}

// Get system settings
function getSetting($db, $key, $default = '') {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

$db = getDBConnection();
$school_name = getSetting($db, 'school_name', 'Sistem Pengaduan Bullying');
$school_tagline = getSetting($db, 'school_tagline', 'Portal Admin - Bimbingan Konseling');
$school_logo = getSetting($db, 'school_logo', '');
$school_background = getSetting($db, 'school_background', '');

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sistem Pengaduan Bullying</title>
    <link rel="stylesheet" href="../public/assets/css/login.css">
    <style>
        <?php if ($school_background): ?>
        .login-page {
            background: url('../uploads/<?php echo htmlspecialchars($school_background); ?>') center/cover no-repeat !important;
            position: relative;
        }
        .login-page::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }
        .login-container {
            position: relative;
            z-index: 1;
        }
        <?php endif; ?>
        
        <?php if ($school_logo): ?>
        .school-logo {
            background: white !important;
            padding: 10px;
        }
        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        <?php endif; ?>
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="school-info">
            <div class="school-logo">
                <?php if ($school_logo): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($school_logo); ?>" alt="Logo Sekolah">
                <?php else: ?>
                    🏫
                <?php endif; ?>
            </div>
            <h2 class="school-name"><?php echo htmlspecialchars($school_name); ?></h2>
            <p class="school-tagline"><?php echo htmlspecialchars($school_tagline); ?></p>
        </div>

        <div class="login-box">
            <div class="login-header">
                <h1>Login Admin</h1>
                <p>Masuk ke Panel Administrasi</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="username">Username / Email</label>
                    <input type="text" name="username" id="username" required 
                           placeholder="Masukkan username atau email"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required 
                           placeholder="Masukkan password"
                           autocomplete="current-password">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-block">
                        Login ke Dashboard
                    </button>
                </div>
            </form>

            <div class="login-footer">
                <p>Default credentials:<br>
                Username: <strong>admin</strong><br>
                Password: <strong>Admin123!</strong></p>
                <hr>
                <a href="../public/index.php">← Kembali ke Halaman Utama</a>
            </div>
        </div>
    </div>
</body>
</html>
