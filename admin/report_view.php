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

// Get report ID
$report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$report_id) {
    header('Location: dashboard.php');
    exit;
}

// Get report details
$stmt = $db->prepare("
    SELECT r.*, c.name as category_name, 
           u.full_name as assigned_admin_name
    FROM reports r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN admin_users u ON r.assigned_to = u.id
    WHERE r.id = ?
");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    header('Location: dashboard.php');
    exit;
}

// Get messages
$stmt = $db->prepare("
    SELECT m.*, u.full_name as admin_name
    FROM report_messages m
    LEFT JOIN admin_users u ON m.sender_id = u.id
    WHERE m.report_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$report_id]);
$messages = $stmt->fetchAll();

// Get attachments
$stmt = $db->prepare("SELECT * FROM report_attachments WHERE report_id = ?");
$stmt->execute([$report_id]);
$attachments = $stmt->fetchAll();

// Mark reporter messages as read
$stmt = $db->prepare("UPDATE report_messages SET is_read = 1 WHERE report_id = ? AND sender = 'reporter'");
$stmt->execute([$report_id]);

// Get all staff for assignment
$stmt = $db->query("SELECT id, full_name FROM admin_users WHERE is_active = 1 ORDER BY full_name");
$staff_list = $stmt->fetchAll();

// Log view
addAuditLog('view_report', 'report', $report_id, 'Viewed report details');

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Detail Laporan - Admin Panel</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🛡️ Admin Panel</h2>
                <p>Halo, <?php echo htmlspecialchars($admin['full_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="reports.php" class="active">📝 Laporan</a>
                <a href="messages.php">💬 Pesan</a>
                <a href="categories.php">📁 Kategori</a>
                <?php if (isSuperAdmin()): ?>
                <a href="users.php">👥 User Admin</a>
                <a href="audit.php">📜 Audit Log</a>
                <?php endif; ?>
                <a href="profile.php">👤 Profil</a>
                <a href="logout.php" class="logout">🚪 Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h1>Detail Laporan</h1>
                    <p>Kode: <strong><?php echo htmlspecialchars($report['tracking_code']); ?></strong></p>
                </div>
                <div class="header-actions">
                    <a href="reports.php" class="btn-secondary">← Kembali</a>
                </div>
            </header>

            <div class="report-detail">
                <!-- Status and Actions -->
                <div class="report-actions-card">
                    <h3>📊 Status & Tindakan</h3>
                    
                    <div class="status-info">
                        <div class="current-status">
                            <label>Status Saat Ini:</label>
                            <span class="badge status-<?php echo $report['status']; ?>">
                                <?php 
                                $status_labels = [
                                    'new' => '📥 Laporan Baru',
                                    'reviewed' => '👁️ Sedang Ditinjau',
                                    'escalated' => '⚡ Dieskalasi',
                                    'resolved' => '✅ Terselesaikan',
                                    'closed' => '📁 Ditutup'
                                ];
                                echo $status_labels[$report['status']];
                                ?>
                            </span>
                        </div>
                        
                        <div class="urgency-info">
                            <label>Tingkat Urgensi:</label>
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
                        </div>
                    </div>

                    <form action="update_status.php" method="POST" class="status-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">
                        
                        <div class="form-group">
                            <label>Ubah Status:</label>
                            <select name="status" class="form-control">
                                <option value="">-- Pilih Status Baru --</option>
                                <option value="reviewed" <?php echo $report['status'] == 'reviewed' ? 'selected' : ''; ?>>👁️ Sedang Ditinjau</option>
                                <option value="escalated" <?php echo $report['status'] == 'escalated' ? 'selected' : ''; ?>>⚡ Eskalasi</option>
                                <option value="resolved" <?php echo $report['status'] == 'resolved' ? 'selected' : ''; ?>>✅ Terselesaikan</option>
                                <option value="closed" <?php echo $report['status'] == 'closed' ? 'selected' : ''; ?>>📁 Tutup Kasus</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Assign ke:</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">-- Tidak di-assign --</option>
                                <?php foreach ($staff_list as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo $report['assigned_to'] == $staff['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Catatan Update (opsional):</label>
                            <textarea name="notes" rows="2" class="form-control" 
                                      placeholder="Tambahkan catatan untuk update ini..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn-primary">💾 Update Status</button>
                    </form>
                </div>

                <!-- Report Information -->
                <div class="report-info-card">
                    <h3>📋 Informasi Laporan</h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Kategori:</label>
                            <span><?php echo htmlspecialchars($report['category_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Tanggal Laporan:</label>
                            <span><?php echo date('d F Y H:i', strtotime($report['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Lokasi Kejadian:</label>
                            <span><?php echo htmlspecialchars($report['location'] ?: '-'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Tanggal Kejadian:</label>
                            <span>
                                <?php 
                                if ($report['incident_date']) {
                                    echo date('d F Y', strtotime($report['incident_date']));
                                    if ($report['incident_time']) {
                                        echo ' ' . date('H:i', strtotime($report['incident_time']));
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label>IP Address:</label>
                            <span><?php echo htmlspecialchars($report['ip_address']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Di-assign ke:</label>
                            <span><?php echo htmlspecialchars($report['assigned_admin_name'] ?: 'Belum di-assign'); ?></span>
                        </div>
                    </div>

                    <?php if ($report['title']): ?>
                    <div class="report-title">
                        <label>Judul:</label>
                        <p><?php echo htmlspecialchars($report['title']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="report-description">
                        <label>Deskripsi Kasus:</label>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                        </div>
                    </div>

                    <?php if ($report['parties_involved']): ?>
                    <div class="report-parties">
                        <label>Pihak Terlibat:</label>
                        <p><?php echo nl2br(htmlspecialchars($report['parties_involved'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($report['witnesses']): ?>
                    <div class="report-witnesses">
                        <label>Saksi:</label>
                        <p><?php echo nl2br(htmlspecialchars($report['witnesses'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Attachments -->
                <?php if (!empty($attachments)): ?>
                <div class="attachments-card">
                    <h3>📎 File Bukti</h3>
                    <div class="attachments-list">
                        <?php foreach ($attachments as $att): ?>
                        <div class="attachment-item">
                            <span class="file-name"><?php echo htmlspecialchars($att['file_name']); ?></span>
                            <span class="file-size"><?php echo number_format($att['file_size'] / 1024, 1); ?> KB</span>
                            <a href="download.php?id=<?php echo $att['id']; ?>" class="btn-download">
                                📥 Download
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Messages Section -->
                <div class="messages-card">
                    <h3>💬 Komunikasi dengan Pelapor</h3>
                    
                    <div class="messages-container">
                        <?php if (empty($messages)): ?>
                        <div class="no-messages">
                            <p>Belum ada komunikasi. Kirim pesan pertama ke pelapor.</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                            <div class="message <?php echo $msg['sender']; ?>">
                                <div class="message-header">
                                    <span class="sender">
                                        <?php 
                                        if ($msg['sender'] === 'admin') {
                                            echo '👤 ' . htmlspecialchars($msg['admin_name'] ?: 'Admin');
                                        } else {
                                            echo '📝 Pelapor';
                                        }
                                        ?>
                                    </span>
                                    <span class="timestamp">
                                        <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="message-body">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Reply Form -->
                    <form action="reply.php" method="POST" class="reply-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">
                        
                        <div class="form-group">
                            <label>Balas Pelapor:</label>
                            <textarea name="message" rows="4" class="form-control" required
                                      placeholder="Tulis pesan untuk pelapor (akan dikirim secara anonim)..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn-primary">📤 Kirim Balasan</button>
                    </form>
                </div>

                <!-- Action History -->
                <div class="history-card">
                    <h3>📜 Riwayat Tindakan</h3>
                    <?php
                    $stmt = $db->prepare("
                        SELECT a.*, u.full_name as admin_name
                        FROM audit_log a
                        LEFT JOIN admin_users u ON a.admin_id = u.id
                        WHERE a.target_type = 'report' AND a.target_id = ?
                        ORDER BY a.created_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$report_id]);
                    $history = $stmt->fetchAll();
                    ?>
                    
                    <?php if (empty($history)): ?>
                    <p>Belum ada riwayat tindakan.</p>
                    <?php else: ?>
                    <div class="history-list">
                        <?php foreach ($history as $item): ?>
                        <div class="history-item">
                            <span class="history-time"><?php echo date('d/m H:i', strtotime($item['created_at'])); ?></span>
                            <span class="history-admin"><?php echo htmlspecialchars($item['admin_name']); ?></span>
                            <span class="history-action"><?php echo htmlspecialchars($item['action']); ?></span>
                            <?php if ($item['details']): ?>
                            <span class="history-details"><?php echo htmlspecialchars($item['details']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Auto-scroll messages
    const messagesContainer = document.querySelector('.messages-container');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Live chat - auto refresh messages every 2 seconds
    let lastMessageCount = <?php echo count($messages); ?>;
    const reportId = <?php echo $report_id; ?>;

    async function refreshMessages() {
        try {
            const response = await fetch(`api/chat.php?action=get_messages&report_id=${reportId}`);
            const data = await response.json();
            
            if (data.success && data.messages.length > lastMessageCount) {
                // New messages arrived, reload the messages section
                const messagesContainer = document.querySelector('.messages-container');
                const wasAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50;
                
                // Update messages
                messagesContainer.innerHTML = data.messages.map(msg => `
                    <div class="message ${msg.sender}">
                        <div class="message-header">
                            <span class="sender">
                                ${msg.sender === 'admin' ? '👤 ' + escapeHtml(msg.admin_name || 'Admin') : '📝 Pelapor'}
                            </span>
                            <span class="timestamp">
                                ${formatDateTime(msg.created_at)}
                            </span>
                        </div>
                        <div class="message-body">
                            ${escapeHtml(msg.message).replace(/\n/g, '<br>')}
                        </div>
                    </div>
                `).join('');
                
                lastMessageCount = data.messages.length;
                
                // Auto scroll if was at bottom
                if (wasAtBottom) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
        } catch (error) {
            console.error('Failed to refresh messages:', error);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDateTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleDateString('id-ID', { 
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Start polling every 2 seconds
    setInterval(refreshMessages, 2000);

    // Handle form submission with AJAX
    const replyForm = document.querySelector('.reply-form');
    if (replyForm) {
        replyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const textarea = this.querySelector('textarea[name="message"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Mengirim...';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('reply.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    // Clear textarea
                    textarea.value = '';
                    
                    // Refresh messages immediately
                    await refreshMessages();
                    
                    // Scroll to bottom
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                } else {
                    alert('Gagal mengirim pesan');
                }
            } catch (error) {
                console.error('Failed to send message:', error);
                alert('Gagal mengirim pesan');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
    </script>
</body>
</html>
