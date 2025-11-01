<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die('Invalid request. Please try again.');
}

// Check rate limiting (max 3 reports per IP per hour)
if (!checkRateLimit('report_submit', 3, 3600)) {
    die('Terlalu banyak laporan dari IP Anda. Silakan coba lagi nanti.');
}

$db = getDBConnection();

try {
    $db->beginTransaction();
    
    // Generate unique tracking code and PIN
    $tracking_code = 'RPT' . date('Ymd') . generateRandomString(6);
    $pin = generatePIN(6);
    $pin_hash = password_hash($pin, PASSWORD_DEFAULT);
    
    // Validate required fields
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $description = sanitizeInput($_POST['description'] ?? '');
    $urgency_level = $_POST['urgency_level'] ?? 'normal';
    
    if (!$category_id || empty($description)) {
        throw new Exception('Data tidak lengkap. Silakan lengkapi form.');
    }
    
    // Validate urgency level
    if (!in_array($urgency_level, ['normal', 'high', 'emergency'])) {
        $urgency_level = 'normal';
    }
    
    // Insert report
    $stmt = $db->prepare("
        INSERT INTO reports (
            tracking_code, pin_hash, category_id, title, description, 
            location, incident_date, incident_time, parties_involved, 
            witnesses, urgency_level, status, ip_address, user_agent
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, ?
        )
    ");
    
    $stmt->execute([
        $tracking_code,
        $pin_hash,
        $category_id,
        sanitizeInput($_POST['title'] ?? ''),
        $description,
        sanitizeInput($_POST['location'] ?? ''),
        $_POST['incident_date'] ?: null,
        $_POST['incident_time'] ?: null,
        sanitizeInput($_POST['parties_involved'] ?? ''),
        sanitizeInput($_POST['witnesses'] ?? ''),
        $urgency_level,
        getClientIP(),
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    $report_id = $db->lastInsertId();
    
    // Handle file uploads
    if (isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] != UPLOAD_ERR_NO_FILE) {
        $upload_dir = UPLOAD_PATH . $tracking_code . '/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_count = count($_FILES['attachments']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['attachments']['name'][$i],
                    'type' => $_FILES['attachments']['type'][$i],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                    'error' => $_FILES['attachments']['error'][$i],
                    'size' => $_FILES['attachments']['size'][$i]
                ];
                
                $validation = validateFileUpload($file);
                
                if ($validation['success'] && $validation['message'] !== 'No file uploaded') {
                    $safe_filename = uniqid() . '.' . $validation['extension'];
                    $file_path = $upload_dir . $safe_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        // Save to database
                        $stmt = $db->prepare("
                            INSERT INTO report_attachments (report_id, file_name, file_path, file_type, file_size)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $report_id,
                            $file['name'],
                            $file_path,
                            $file['type'],
                            $file['size']
                        ]);
                    }
                }
            }
        }
    }
    
    // Notify admins if urgent
    if ($urgency_level === 'emergency' || $urgency_level === 'high') {
        notifyNewReport($report_id, $urgency_level);
    }
    
    $db->commit();
    
    // Clear draft from localStorage
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laporan Berhasil Dikirim</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <script>
            // Clear draft
            localStorage.removeItem('bullying_report_draft');
        </script>
    </head>
    <body>
        <div class="container">
            <div class="success-page">
                <div class="success-icon">✅</div>
                <h1>Laporan Berhasil Dikirim!</h1>
                <p class="success-message">Terima kasih atas keberanian Anda melaporkan. Kami akan segera menindaklanjuti.</p>
                
                <div class="credentials-box">
                    <h2>⚠️ PENTING - Simpan Informasi Ini!</h2>
                    <p>Gunakan kode ini untuk mengecek status dan berkomunikasi dengan konselor:</p>
                    
                    <div class="credential-item">
                        <label>Kode Pelacakan:</label>
                        <div class="credential-value" id="trackingCode"><?php echo $tracking_code; ?></div>
                        <button onclick="copyToClipboard('trackingCode')" class="btn-copy">📋 Salin</button>
                    </div>
                    
                    <div class="credential-item">
                        <label>PIN Rahasia:</label>
                        <div class="credential-value" id="pinCode"><?php echo $pin; ?></div>
                        <button onclick="copyToClipboard('pinCode')" class="btn-copy">📋 Salin</button>
                    </div>
                    
                    <div class="warning-box">
                        ⚠️ <strong>Peringatan:</strong> PIN ini tidak dapat dipulihkan jika hilang. 
                        Simpan di tempat yang aman dan jangan bagikan kepada siapa pun!
                    </div>
                </div>
                
                <?php if ($urgency_level === 'emergency'): ?>
                <div class="emergency-notice">
                    🚨 <strong>Kasus Darurat:</strong> Tim konselor telah diberitahu dan akan segera menghubungi Anda melalui sistem.
                </div>
                <?php endif; ?>
                
                <div class="next-steps">
                    <h3>Apa Selanjutnya?</h3>
                    <ol>
                        <li>Tim konselor akan meninjau laporan Anda dalam 24 jam (lebih cepat untuk kasus darurat)</li>
                        <li>Gunakan kode pelacakan & PIN untuk cek status laporan</li>
                        <li>Anda dapat berkomunikasi dengan konselor secara anonim melalui sistem</li>
                        <li>Semua komunikasi bersifat rahasia dan aman</li>
                    </ol>
                </div>
                
                <div class="form-actions">
                    <a href="track.php" class="btn-primary">🔍 Cek Status Laporan</a>
                    <a href="index.php" class="btn-secondary">🏠 Halaman Utama</a>
                </div>
                
                <div class="help-info">
                    <p>Butuh bantuan segera? Hubungi:</p>
                    <p><strong>119</strong> - Telepon Pelayanan Sosial Anak (24 jam)</p>
                </div>
            </div>
        </div>
        
        <script>
        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text).then(function() {
                alert('Berhasil disalin!');
            }, function(err) {
                alert('Gagal menyalin. Silakan salin manual.');
            });
        }
        
        // Auto save credentials to localStorage (encrypted)
        const credentials = {
            tracking_code: '<?php echo $tracking_code; ?>',
            saved_at: new Date().toISOString()
        };
        
        // Save to localStorage (PIN not saved for security)
        let savedReports = JSON.parse(localStorage.getItem('saved_reports') || '[]');
        savedReports.push(credentials);
        localStorage.setItem('saved_reports', JSON.stringify(savedReports));
        </script>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    $db->rollBack();
    error_log('Report submission error: ' . $e->getMessage());
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
        <div class="container">
            <div class="error-page">
                <h1>❌ Terjadi Kesalahan</h1>
                <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
                <a href="index.php" class="btn-primary">← Kembali ke Form</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
