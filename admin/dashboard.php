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

// Get statistics
$stats = [];

// Total reports
$stmt = $db->query("SELECT COUNT(*) as total FROM reports");
$stats['total'] = $stmt->fetch()['total'];

// Reports by status
$stmt = $db->query("
    SELECT status, COUNT(*) as count 
    FROM reports 
    GROUP BY status
");
$statusCounts = $stmt->fetchAll();
foreach ($statusCounts as $row) {
    $stats[$row['status']] = $row['count'];
}

// Emergency reports
$stmt = $db->query("SELECT COUNT(*) as count FROM reports WHERE urgency_level = 'emergency' AND status IN ('new', 'reviewed')");
$stats['emergency'] = $stmt->fetch()['count'];

// Reports today
$stmt = $db->query("SELECT COUNT(*) as count FROM reports WHERE DATE(created_at) = CURDATE()");
$stats['today'] = $stmt->fetch()['count'];

// Get recent reports
$stmt = $db->prepare("
    SELECT r.*, c.name as category_name,
           (SELECT COUNT(*) FROM report_messages WHERE report_id = r.id AND sender = 'reporter' AND is_read = 0) as unread_messages
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    ORDER BY 
        CASE WHEN r.urgency_level = 'emergency' THEN 1 
             WHEN r.urgency_level = 'high' THEN 2 
             ELSE 3 END,
        r.created_at DESC
    LIMIT 20
");
$stmt->execute();
$reports = $stmt->fetchAll();

// Get notifications
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE admin_id = ? AND is_read = 0 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$admin['id']]);
$notifications = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Dashboard Admin - Sistem Pengaduan Bullying</title>
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
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="reports.php">Laporan</a>
                <a href="messages.php">Pesan</a>
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
                <h1>Dashboard</h1>
                <div class="header-actions">
                    <span class="notification-badge">
                        🔔 <?php echo count($notifications); ?>
                    </span>
                    <span><?php echo date('l, d F Y'); ?></span>
                </div>
            </header>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-details">
                        <h3>Total Laporan</h3>
                        <p class="stat-number"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card primary">
                    <div class="stat-icon">📥</div>
                    <div class="stat-details">
                        <h3>Laporan Baru</h3>
                        <p class="stat-number"><?php echo $stats['new'] ?? 0; ?></p>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-details">
                        <h3>Sedang Diproses</h3>
                        <p class="stat-number"><?php echo ($stats['reviewed'] ?? 0) + ($stats['escalated'] ?? 0); ?></p>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-details">
                        <h3>Terselesaikan</h3>
                        <p class="stat-number"><?php echo ($stats['resolved'] ?? 0) + ($stats['closed'] ?? 0); ?></p>
                    </div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-icon">🚨</div>
                    <div class="stat-details">
                        <h3>Kasus Darurat</h3>
                        <p class="stat-number"><?php echo $stats['emergency']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">📅</div>
                    <div class="stat-details">
                        <h3>Hari Ini</h3>
                        <p class="stat-number"><?php echo $stats['today']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <?php if (!empty($notifications)): ?>
            <div class="notifications-section">
                <h2>🔔 Notifikasi</h2>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item">
                        <span class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></span>
                        <span class="notif-time"><?php echo date('H:i', strtotime($notif['created_at'])); ?></span>
                        <?php if ($notif['message']): ?>
                        <p class="notif-message"><?php echo htmlspecialchars($notif['message']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Reports -->
            <div class="reports-section">
                <div class="section-header">
                    <h2>📋 Laporan Terbaru</h2>
                    <a href="reports.php" class="btn-link">Lihat Semua →</a>
                </div>
                
                <div class="reports-table-container">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Kategori</th>
                                <th>Judul</th>
                                <th>Urgensi</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Pesan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                            <tr class="<?php echo $report['urgency_level'] === 'emergency' ? 'emergency-row' : ''; ?>">
                                <td>
                                    <code><?php echo htmlspecialchars($report['tracking_code']); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($report['category_name']); ?></td>
                                <td>
                                    <?php 
                                    $title = $report['title'] ?: substr($report['description'], 0, 50) . '...';
                                    echo htmlspecialchars($title);
                                    ?>
                                </td>
                                <td>
                                    <span class="badge urgency-<?php echo $report['urgency_level']; ?>">
                                        <?php 
                                        $urgency_labels = [
                                            'normal' => '🟢 Normal',
                                            'high' => '🟡 Tinggi',
                                            'emergency' => '🔴 Darurat'
                                        ];
                                        echo $urgency_labels[$report['urgency_level']];
                                        ?>
                                    </span>
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
                                <td><?php echo date('d/m/y H:i', strtotime($report['created_at'])); ?></td>
                                <td>
                                    <?php if ($report['unread_messages'] > 0): ?>
                                    <span class="badge badge-new"><?php echo $report['unread_messages']; ?> baru</span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="report_view.php?id=<?php echo $report['id']; ?>" class="btn-action">
                                        👁️ Lihat
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../public/assets/js/admin-chat.js"></script>
    <script>
    // Mark notifications as read when clicked
    document.querySelectorAll('.notification-item').forEach(function(item) {
        item.addEventListener('click', function() {
            // Mark as read via AJAX
            this.style.opacity = '0.5';
        });
    });
    </script>
</body>
</html>
