<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Only superadmin can manage users
if (!isSuperAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$db = getDBConnection();
$admin = getCurrentAdmin();

// Get all admin users
$stmt = $db->query("SELECT * FROM admin_users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>User Admin - Admin Panel</title>
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
                <a href="users.php" class="active">User Admin</a>
                <a href="audit.php">Audit Log</a>
                <a href="settings.php">Pengaturan</a>
                <?php endif; ?>
                <a href="profile.php">Profil</a>
                <a href="logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>User Admin</h1>
            </header>

            <div class="reports-section">
                <div class="reports-table-container">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Login Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'superadmin'): ?>
                                    <span class="badge urgency-emergency">Super Admin</span>
                                    <?php else: ?>
                                    <span class="badge urgency-normal">Staff BK</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                    <span class="badge status-resolved">Aktif</span>
                                    <?php else: ?>
                                    <span class="badge status-closed">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Belum pernah'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../public/assets/js/admin-chat.js"></script>
</body>
</html>
