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

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$urgency_filter = $_GET['urgency'] ?? '';

// Build query
$query = "
    SELECT r.*, c.name as category_name,
           (SELECT COUNT(*) FROM report_messages WHERE report_id = r.id AND sender = 'reporter' AND is_read = 0) as unread_messages
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    WHERE 1=1
";

$params = [];

if ($status_filter) {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
}

if ($urgency_filter) {
    $query .= " AND r.urgency_level = ?";
    $params[] = $urgency_filter;
}

$query .= " ORDER BY 
    CASE WHEN r.urgency_level = 'emergency' THEN 1 
         WHEN r.urgency_level = 'high' THEN 2 
         ELSE 3 END,
    r.created_at DESC
    LIMIT 100
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Laporan - Admin Panel</title>
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
                <a href="reports.php" class="active">Laporan</a>
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
                <h1>Semua Laporan</h1>
            </header>

            <!-- Filters -->
            <div class="report-info-card" style="margin-bottom: 20px;">
                <form method="GET" action="" style="display: flex; gap: 15px; align-items: end;">
                    <div class="form-group" style="margin: 0; flex: 1;">
                        <label>Filter Status:</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>Baru</option>
                            <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>Ditinjau</option>
                            <option value="escalated" <?php echo $status_filter === 'escalated' ? 'selected' : ''; ?>>Eskalasi</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Ditutup</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin: 0; flex: 1;">
                        <label>Filter Urgensi:</label>
                        <select name="urgency" class="form-control">
                            <option value="">Semua Urgensi</option>
                            <option value="normal" <?php echo $urgency_filter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="high" <?php echo $urgency_filter === 'high' ? 'selected' : ''; ?>>Tinggi</option>
                            <option value="emergency" <?php echo $urgency_filter === 'emergency' ? 'selected' : ''; ?>>Darurat</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="reports.php" class="btn-secondary">Reset</a>
                </form>
            </div>

            <div class="reports-section">
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
                                            'normal' => 'Normal',
                                            'high' => 'Tinggi',
                                            'emergency' => 'Darurat'
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
                                        Lihat
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
</body>
</html>
