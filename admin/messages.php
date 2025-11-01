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

// Get all reports with unread messages
$stmt = $db->prepare("
    SELECT r.*, c.name as category_name,
           (SELECT COUNT(*) FROM report_messages WHERE report_id = r.id AND sender = 'reporter' AND is_read = 0) as unread_count,
           (SELECT message FROM report_messages WHERE report_id = r.id ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM report_messages WHERE report_id = r.id ORDER BY created_at DESC LIMIT 1) as last_message_time
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    WHERE r.status NOT IN ('closed')
    HAVING unread_count > 0 OR last_message_time IS NOT NULL
    ORDER BY last_message_time DESC
    LIMIT 50
");
$stmt->execute();
$reports_with_messages = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Pesan - Admin Panel</title>
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
                <a href="messages.php" class="active">Pesan</a>
                <a href="categories.php">Kategori</a>
                <?php if (isSuperAdmin()): ?>
                <a href="users.php">User Admin</a>
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
                <h1>Pesan</h1>
                <div class="header-actions">
                    <span><?php echo date('l, d F Y'); ?></span>
                </div>
            </header>

            <div class="reports-section">
                <h2>Laporan dengan Pesan</h2>
                
                <?php if (empty($reports_with_messages)): ?>
                <p style="text-align: center; padding: 40px; color: #6b7280;">Tidak ada pesan saat ini.</p>
                <?php else: ?>
                <div class="reports-table-container">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Kode Laporan</th>
                                <th>Kategori</th>
                                <th>Pesan Terakhir</th>
                                <th>Waktu</th>
                                <th>Pesan Baru</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports_with_messages as $report): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($report['tracking_code']); ?></code></td>
                                <td><?php echo htmlspecialchars($report['category_name']); ?></td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars(substr($report['last_message'] ?? '-', 0, 50)); ?>
                                </td>
                                <td><?php echo $report['last_message_time'] ? date('d/m/Y H:i', strtotime($report['last_message_time'])) : '-'; ?></td>
                                <td>
                                    <?php if ($report['unread_count'] > 0): ?>
                                    <span class="badge badge-new"><?php echo $report['unread_count']; ?> baru</span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge status-<?php echo $report['status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'new' => 'Baru',
                                            'reviewed' => 'Ditinjau',
                                            'escalated' => 'Eskalasi',
                                            'resolved' => 'Selesai',
                                            'closed' => 'Ditutup'
                                        ];
                                        echo $status_labels[$report['status']];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="report_view.php?id=<?php echo $report['id']; ?>" class="btn-action">
                                        Lihat & Balas
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../public/assets/js/admin-chat.js"></script>
</body>
</html>
