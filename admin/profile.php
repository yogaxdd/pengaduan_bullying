<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$admin = getCurrentAdmin();

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if ($full_name && $email) {
            try {
                $stmt = $db->prepare("UPDATE admin_users SET full_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $admin['id']]);
                
                addAuditLog('update_profile', 'admin', $admin['id'], 'Updated profile information');
                $success = 'Profil berhasil diperbarui.';
                
                // Refresh admin data
                $admin = getCurrentAdmin();
            } catch (Exception $e) {
                $error = 'Gagal memperbarui profil.';
            }
        } else {
            $error = 'Mohon isi semua field.';
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Profil - Admin Panel</title>
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
                <a href="settings.php">Pengaturan</a>
                <?php endif; ?>
                <a href="profile.php" class="active">Profil</a>
                <a href="logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Profil Saya</h1>
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
                <h3>Informasi Akun</h3>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                        <small style="color: #6b7280;">Username tidak dapat diubah</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Lengkap:</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Role:</label>
                        <input type="text" class="form-control" value="<?php echo $admin['role'] === 'superadmin' ? 'Super Admin' : 'Staff BK'; ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Login Terakhir:</label>
                        <input type="text" class="form-control" value="<?php echo isset($admin['last_login']) && $admin['last_login'] ? date('d F Y H:i', strtotime($admin['last_login'])) : 'Belum pernah'; ?>" disabled>
                    </div>
                    
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </form>
            </div>
        </main>
    </div>

    <script src="../public/assets/js/admin-chat.js"></script>
</body>
</html>
