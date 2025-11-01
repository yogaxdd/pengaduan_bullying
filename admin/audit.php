<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Only superadmin can view audit log
if (!isSuperAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$db = getDBConnection();
$admin = getCurrentAdmin();

// Get audit logs
$stmt = $db->prepare("
    SELECT a.*, u.full_name as admin_name
    FROM audit_log a
    LEFT JOIN admin_users u ON a.admin_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 100
");
$stmt->execute();
$logs = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Audit Log - Admin Panel</title>
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
                <a href="audit.php" class="active">Audit Log</a>
                <a href="settings.php">Pengaturan</a>
                <?php endif; ?>
                <a href="profile.php">Profil</a>
                <a href="logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1>Audit Log</h1>
                <p style="color: #6b7280;">100 aktivitas terakhir</p>
            </header>

            <div class="reports-section">
                <div class="reports-table-container">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Admin</th>
                                <th>Aksi</th>
                                <th>Target</th>
                                <th>Detail</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['admin_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
                                <td><?php echo htmlspecialchars($log['target_type'] ?? '-'); ?> #<?php echo $log['target_id'] ?? '-'; ?></td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($log['details'] ?? '-'); ?>
                                </td>
                                <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
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
