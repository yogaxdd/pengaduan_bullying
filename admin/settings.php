<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Only superadmin can access settings
if (!isSuperAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$db = getDBConnection();
$admin = getCurrentAdmin();

$success = '';
$error = '';

// Create settings table if not exists
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT,
            FOREIGN KEY (updated_by) REFERENCES admin_users(id)
        )
    ");
} catch (Exception $e) {
    // Table might already exist
}

// Get current settings
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

function setSetting($db, $key, $value, $admin_id) {
    $stmt = $db->prepare("
        INSERT INTO system_settings (setting_key, setting_value, updated_by) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?
    ");
    $stmt->execute([$key, $value, $admin_id, $value, $admin_id]);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        try {
            $school_name = sanitizeInput($_POST['school_name'] ?? '');
            $school_tagline = sanitizeInput($_POST['school_tagline'] ?? '');
            
            // Handle logo upload
            $logo_path = getSetting($db, 'school_logo', '');
            if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['school_logo']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed) && $_FILES['school_logo']['size'] <= 2 * 1024 * 1024) {
                    $new_filename = 'logo_' . time() . '.' . $ext;
                    $upload_path = '../uploads/' . $new_filename;
                    
                    if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $upload_path)) {
                        // Delete old logo if exists
                        if ($logo_path && file_exists('../uploads/' . $logo_path)) {
                            unlink('../uploads/' . $logo_path);
                        }
                        $logo_path = $new_filename;
                    }
                }
            }
            
            // Handle background upload
            $bg_path = getSetting($db, 'school_background', '');
            if (isset($_FILES['school_background']) && $_FILES['school_background']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $filename = $_FILES['school_background']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed) && $_FILES['school_background']['size'] <= 5 * 1024 * 1024) {
                    $new_filename = 'bg_' . time() . '.' . $ext;
                    $upload_path = '../uploads/' . $new_filename;
                    
                    if (move_uploaded_file($_FILES['school_background']['tmp_name'], $upload_path)) {
                        // Delete old background if exists
                        if ($bg_path && file_exists('../uploads/' . $bg_path)) {
                            unlink('../uploads/' . $bg_path);
                        }
                        $bg_path = $new_filename;
                    }
                }
            }
            
            setSetting($db, 'school_name', $school_name, $admin['id']);
            setSetting($db, 'school_tagline', $school_tagline, $admin['id']);
            setSetting($db, 'school_logo', $logo_path, $admin['id']);
            setSetting($db, 'school_background', $bg_path, $admin['id']);
            
            addAuditLog('update_settings', 'system', 0, 'Updated system settings');
            $success = 'Pengaturan berhasil disimpan!';
        } catch (Exception $e) {
            $error = 'Gagal menyimpan pengaturan: ' . $e->getMessage();
        }
    }
}

// Get current settings
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
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Pengaturan Sistem - Admin Panel</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Halo, <?php echo htmlspecialchars($admin['full_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="reports.php">Laporan</a>
                <a href="messages.php">Pesan</a>
                <a href="categories.php">Kategori</a>
                <?php if (isSuperAdmin()): ?>
                <a href="users.php">User Admin</a>
                <a href="audit.php">Audit Log</a>
                <a href="settings.php" class="active">Pengaturan</a>
                <?php endif; ?>
                <a href="profile.php">Profil</a>
                <a href="logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Pengaturan Sistem</h1>
                <p style="color: #6b7280;">Atur tampilan dan informasi sekolah</p>
            </header>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <div class="report-info-card">
                <h3>Informasi Sekolah</h3>
                <p style="color: #6b7280; margin-bottom: 24px;">Pengaturan ini akan ditampilkan di halaman login admin dan frontend</p>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label>Nama Sekolah / Sistem:</label>
                        <input type="text" name="school_name" class="form-control" 
                               value="<?php echo htmlspecialchars($school_name); ?>" required>
                        <small style="color: #6b7280;">Contoh: SMA Negeri 1 Jakarta</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Tagline / Subtitle:</label>
                        <input type="text" name="school_tagline" class="form-control" 
                               value="<?php echo htmlspecialchars($school_tagline); ?>" required>
                        <small style="color: #6b7280;">Contoh: Portal Admin - Bimbingan Konseling</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Logo Sekolah:</label>
                        <?php if ($school_logo): ?>
                        <div style="margin-bottom: 12px;">
                            <img src="../uploads/<?php echo htmlspecialchars($school_logo); ?>" 
                                 style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 2px solid #e5e7eb;">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="school_logo" class="form-control" accept="image/*">
                        <small style="color: #6b7280;">Upload logo sekolah (JPG, PNG, GIF, WEBP - Max 2MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Background Image:</label>
                        <?php if ($school_background): ?>
                        <div style="margin-bottom: 12px;">
                            <img src="../uploads/<?php echo htmlspecialchars($school_background); ?>" 
                                 style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 2px solid #e5e7eb; object-fit: cover;">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="school_background" class="form-control" accept="image/*">
                        <small style="color: #6b7280;">Upload foto gedung sekolah atau background (JPG, PNG, WEBP - Max 5MB)</small>
                    </div>
                    
                    <button type="submit" class="btn-primary">Simpan Pengaturan</button>
                    <a href="login.php" class="btn-secondary" target="_blank" style="margin-left: 10px;">Preview Login Page</a>
                    <a href="../public/index.php" class="btn-secondary" target="_blank" style="margin-left: 10px;">Preview Frontend</a>
                </form>
            </div>
        </main>
    </div>

    <script src="../public/assets/js/admin-chat.js"></script>
</body>
</html>
