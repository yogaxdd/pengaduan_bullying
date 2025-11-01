<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/settings.php';

$error = '';
$report = null;
$messages = [];

// Get school settings
$db_temp = getDBConnection();
$school = getSchoolSettings($db_temp);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if sending message
    if (isset($_POST['send_message'])) {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            $tracking_code = $_SESSION['report_tracking'] ?? '';
            $message = sanitizeInput($_POST['message'] ?? '');
            
            if ($tracking_code && $message) {
                $db = getDBConnection();
                
                // Get report ID
                $stmt = $db->prepare("SELECT id FROM reports WHERE tracking_code = ?");
                $stmt->execute([$tracking_code]);
                $report = $stmt->fetch();
                
                if ($report) {
                    // Insert message
                    $stmt = $db->prepare("
                        INSERT INTO report_messages (report_id, sender, message) 
                        VALUES (?, 'reporter', ?)
                    ");
                    $stmt->execute([$report['id'], $message]);
                    
                    // Redirect to avoid resubmission
                    header('Location: track.php');
                    exit;
                }
            }
        }
    } 
    // Check tracking code and PIN
    else {
        $tracking_code = sanitizeInput($_POST['tracking_code'] ?? '');
        $pin = sanitizeInput($_POST['pin'] ?? '');
        
        if ($tracking_code && $pin) {
            $db = getDBConnection();
            
            // Get report
            $stmt = $db->prepare("
                SELECT r.*, c.name as category_name 
                FROM reports r 
                LEFT JOIN categories c ON r.category_id = c.id 
                WHERE r.tracking_code = ?
            ");
            $stmt->execute([$tracking_code]);
            $report = $stmt->fetch();
            
            if ($report && password_verify($pin, $report['pin_hash'])) {
                // Valid credentials - save in session
                $_SESSION['report_tracking'] = $tracking_code;
                
                // Get messages
                $stmt = $db->prepare("
                    SELECT m.*, u.full_name as admin_name 
                    FROM report_messages m 
                    LEFT JOIN admin_users u ON m.sender_id = u.id 
                    WHERE m.report_id = ? 
                    ORDER BY m.created_at ASC
                ");
                $stmt->execute([$report['id']]);
                $messages = $stmt->fetchAll();
                
                // Mark messages as read
                $stmt = $db->prepare("
                    UPDATE report_messages 
                    SET is_read = 1 
                    WHERE report_id = ? AND sender = 'admin'
                ");
                $stmt->execute([$report['id']]);
            } else {
                $error = 'Kode pelacakan atau PIN tidak valid.';
            }
        } else {
            $error = 'Mohon masukkan kode pelacakan dan PIN.';
        }
    }
} 
// Check if already logged in to a report
elseif (isset($_SESSION['report_tracking'])) {
    $db = getDBConnection();
    $tracking_code = $_SESSION['report_tracking'];
    
    // Get report
    $stmt = $db->prepare("
        SELECT r.*, c.name as category_name 
        FROM reports r 
        LEFT JOIN categories c ON r.category_id = c.id 
        WHERE r.tracking_code = ?
    ");
    $stmt->execute([$tracking_code]);
    $report = $stmt->fetch();
    
    if ($report) {
        // Get messages
        $stmt = $db->prepare("
            SELECT m.*, u.full_name as admin_name 
            FROM report_messages m 
            LEFT JOIN admin_users u ON m.sender_id = u.id 
            WHERE m.report_id = ? 
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$report['id']]);
        $messages = $stmt->fetchAll();
        
        // Mark admin messages as read
        $stmt = $db->prepare("
            UPDATE report_messages 
            SET is_read = 1 
            WHERE report_id = ? AND sender = 'admin'
        ");
        $stmt->execute([$report['id']]);
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($school['name']); ?> - Cek Status Laporan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        <?php if ($school['background']): ?>
        body {
            background: url('/pengaduan_bullying/uploads/<?php echo htmlspecialchars($school['background']); ?>') center/cover no-repeat fixed;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(243, 244, 246, 0.95);
            z-index: -1;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="container">
        <!-- Quick Exit Button -->
        <button id="quickExit" class="quick-exit" onclick="window.location.href='https://www.google.com'">
            Keluar Cepat (ESC)
        </button>

        <header>
            <?php if ($school['logo']): ?>
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="/pengaduan_bullying/uploads/<?php echo htmlspecialchars($school['logo']); ?>" 
                     alt="Logo Sekolah" 
                     style="max-width: 120px; max-height: 120px; object-fit: contain;"
                     onerror="console.error('Failed to load logo:', this.src);">
            </div>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($school['name']); ?></h1>
            <p class="subtitle">Pantau perkembangan dan komunikasi dengan konselor</p>
        </header>

        <nav class="main-nav">
            <a href="index.php">Buat Laporan</a>
            <a href="track.php" class="active">Cek Status Laporan</a>
            <a href="info.php">Informasi & Bantuan</a>
        </nav>

        <?php if (!$report): ?>
        <!-- Login Form -->
        <div class="track-login">
            <div class="info-box">
                <h3>Cara Menggunakan</h3>
                <ol>
                    <li>Masukkan <strong>Kode Pelacakan</strong> yang Anda terima saat membuat laporan</li>
                    <li>Masukkan <strong>PIN 6 digit</strong> rahasia Anda</li>
                    <li>Klik "Cek Status" untuk melihat perkembangan laporan</li>
                    <li>Anda dapat berkomunikasi dengan konselor secara anonim</li>
                </ol>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="track-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="tracking_code">Kode Pelacakan:</label>
                    <input type="text" name="tracking_code" id="tracking_code" 
                           placeholder="Contoh: RPT20240115ABC123" required 
                           value="<?php echo htmlspecialchars($_POST['tracking_code'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="pin">PIN (6 digit):</label>
                    <input type="password" name="pin" id="pin" 
                           placeholder="Masukkan 6 digit PIN" maxlength="6" 
                           pattern="[0-9]{6}" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        Cek Status
                    </button>
                </div>
            </form>

            <div class="saved-reports">
                <h3>Laporan Tersimpan</h3>
                <div id="savedReportsList"></div>
            </div>
        </div>

        <?php else: ?>
        <!-- Report Details -->
        <div class="report-status">
            <div class="status-header">
                <h2>Status Laporan: <?php echo htmlspecialchars($report['tracking_code']); ?></h2>
                <button onclick="logout()" class="btn-logout">Keluar</button>
            </div>

            <!-- Status Badge -->
            <div class="status-badge <?php echo $report['status']; ?>">
                <?php
                $status_labels = [
                    'new' => 'Laporan Baru',
                    'reviewed' => 'Sedang Ditinjau',
                    'escalated' => 'Dieskalasi',
                    'resolved' => 'Terselesaikan',
                    'closed' => 'Ditutup'
                ];
                echo $status_labels[$report['status']] ?? $report['status'];
                ?>
            </div>

            <!-- Report Info -->
            <div class="report-info">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Kategori:</label>
                        <span><?php echo htmlspecialchars($report['category_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Tanggal Laporan:</label>
                        <span><?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Tingkat Urgensi:</label>
                        <span class="urgency-<?php echo $report['urgency_level']; ?>">
                            <?php 
                            $urgency_labels = [
                                'normal' => 'Normal',
                                'high' => 'Tinggi',
                                'emergency' => 'Darurat'
                            ];
                            echo $urgency_labels[$report['urgency_level']];
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>Terakhir Diperbarui:</label>
                        <span><?php echo date('d/m/Y H:i', strtotime($report['updated_at'])); ?></span>
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
                    <p><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                </div>
            </div>

            <!-- Messages Section -->
            <div class="messages-section">
                <h3>Komunikasi dengan Konselor</h3>
                
                <div class="messages-container" id="messagesContainer">
                    <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        <p>Belum ada pesan. Konselor akan segera menghubungi Anda.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['sender']; ?>">
                            <div class="message-header">
                                <span class="sender">
                                    <?php 
                                    if ($msg['sender'] === 'admin') {
                                        echo 'Konselor' . ($msg['admin_name'] ? ' (' . htmlspecialchars($msg['admin_name']) . ')' : '');
                                    } else {
                                        echo 'Anda';
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
                <form method="POST" action="" class="message-form" id="messageForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="send_message" value="1">
                    
                    <div class="form-group">
                        <label for="message">Kirim Pesan ke Konselor:</label>
                        <textarea name="message" id="message" rows="3" required 
                                  placeholder="Tulis pesan Anda di sini..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary" id="sendBtn">
                            Kirim Pesan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Progress Timeline -->
            <div class="progress-timeline">
                <h3>Riwayat Perkembangan</h3>
                <div class="timeline">
                    <div class="timeline-item completed">
                        <div class="timeline-marker">✅</div>
                        <div class="timeline-content">
                            <strong>Laporan Diterima</strong>
                            <p><?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php if (in_array($report['status'], ['reviewed', 'escalated', 'resolved', 'closed'])): ?>
                    <div class="timeline-item completed">
                        <div class="timeline-marker">✅</div>
                        <div class="timeline-content">
                            <strong>Sedang Ditinjau</strong>
                            <p>Tim konselor sedang meninjau laporan Anda</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (in_array($report['status'], ['escalated', 'resolved', 'closed'])): ?>
                    <div class="timeline-item completed">
                        <div class="timeline-marker">✅</div>
                        <div class="timeline-content">
                            <strong>Tindakan Diambil</strong>
                            <p>Kasus sedang/telah ditangani</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (in_array($report['status'], ['resolved', 'closed'])): ?>
                    <div class="timeline-item completed">
                        <div class="timeline-marker">✅</div>
                        <div class="timeline-content">
                            <strong>Selesai</strong>
                            <p>Kasus telah diselesaikan</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <footer>
            <p>Privasi Anda terjaga. Semua komunikasi bersifat rahasia.</p>
            <p>Butuh bantuan segera? Hubungi: <strong>119</strong> (Telepon Pelayanan Sosial Anak)</p>
        </footer>
    </div>

    <script>
    // Quick exit with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'https://www.google.com';
        }
    });

    // Auto-scroll messages to bottom
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Load saved reports from localStorage
    function loadSavedReports() {
        const savedReports = JSON.parse(localStorage.getItem('saved_reports') || '[]');
        const listDiv = document.getElementById('savedReportsList');
        
        if (listDiv && savedReports.length > 0) {
            let html = '<ul>';
            savedReports.forEach(report => {
                html += `<li>
                    <strong>${report.tracking_code}</strong> 
                    <span class="saved-date">(Disimpan: ${new Date(report.saved_at).toLocaleDateString('id-ID')})</span>
                    <button onclick="fillTrackingCode('${report.tracking_code}')" class="btn-small">Gunakan</button>
                </li>`;
            });
            html += '</ul>';
            listDiv.innerHTML = html;
        } else if (listDiv) {
            listDiv.innerHTML = '<p>Tidak ada laporan tersimpan.</p>';
        }
    }

    function fillTrackingCode(code) {
        document.getElementById('tracking_code').value = code;
        document.getElementById('pin').focus();
    }

    function logout() {
        if (confirm('Yakin ingin keluar dari sistem pelacakan?')) {
            // Clear session via AJAX or form submission
            window.location.href = 'logout_tracking.php';
        }
    }

    // Live chat - auto refresh messages every 2 seconds
    <?php if ($report): ?>
    let lastMessageCount = <?php echo count($messages); ?>;
    const csrfToken = '<?php echo $csrf_token; ?>';

    async function refreshMessages() {
        try {
            const response = await fetch('api/reporter_chat.php?action=get_messages');
            const data = await response.json();
            
            if (data.success && data.messages.length > lastMessageCount) {
                const messagesContainer = document.getElementById('messagesContainer');
                const wasAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50;
                
                // Update messages
                messagesContainer.innerHTML = data.messages.map(msg => `
                    <div class="message ${msg.sender}">
                        <div class="message-header">
                            <span class="sender">
                                ${msg.sender === 'admin' ? 'Konselor' + (msg.admin_name ? ' (' + escapeHtml(msg.admin_name) + ')' : '') : 'Anda'}
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
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const sendBtn = document.getElementById('sendBtn');
            const textarea = document.getElementById('message');
            const originalText = sendBtn.innerHTML;
            
            sendBtn.disabled = true;
            sendBtn.innerHTML = 'Mengirim...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('message', textarea.value);
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch('api/reporter_chat.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    textarea.value = '';
                    await refreshMessages();
                    
                    const messagesContainer = document.getElementById('messagesContainer');
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                } else {
                    alert('Gagal mengirim pesan');
                }
            } catch (error) {
                console.error('Failed to send message:', error);
                alert('Gagal mengirim pesan');
            } finally {
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalText;
            }
        });
    }
    <?php endif; ?>

    // Load saved reports on page load
    loadSavedReports();
    </script>
</body>
</html>
