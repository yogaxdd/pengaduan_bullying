<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/settings.php';

$db = getDBConnection();

// Get categories for dropdown
$stmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Get school settings
$school = getSchoolSettings($db);

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($school['name']); ?> - Laporkan Bullying</title>
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
            <p class="subtitle"><?php echo htmlspecialchars($school['tagline']); ?></p>
        </header>

        <nav class="main-nav">
            <a href="index.php" class="active">Buat Laporan</a>
            <a href="track.php">Cek Status Laporan</a>
            <a href="info.php">Informasi & Bantuan</a>
        </nav>

        <div class="info-box">
            <h3>Keamanan & Privasi Anda Terjamin</h3>
            <ul>
                <li>Tidak perlu login atau memberikan identitas</li>
                <li>Komunikasi terenkripsi dan aman</li>
                <li>Hanya Anda yang tahu kode pelacakan</li>
                <li>Tim konselor terlatih siap membantu</li>
            </ul>
        </div>

        <form id="reportForm" action="submit_report.php" method="POST" enctype="multipart/form-data" class="report-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-section">
                <h2>1. Kategori Kasus</h2>
                <div class="form-group">
                    <label for="category">Pilih jenis bullying yang Anda alami: <span class="required">*</span></label>
                    <select name="category_id" id="category" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" data-desc="<?php echo htmlspecialchars($cat['description']); ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="categoryDesc" class="field-description"></div>
                </div>
            </div>

            <div class="form-section">
                <h2>2. Detail Kejadian</h2>
                
                <div class="form-group">
                    <label for="title">Judul Singkat (opsional):</label>
                    <input type="text" name="title" id="title" maxlength="200" 
                           placeholder="Contoh: Diejek terus di kelas">
                    <div class="field-help">Beri judul singkat untuk memudahkan identifikasi laporan Anda</div>
                </div>

                <div class="form-group">
                    <label for="description">Ceritakan apa yang terjadi: <span class="required">*</span></label>
                    <textarea name="description" id="description" rows="8" required 
                              placeholder="Ceritakan dengan detail apa yang Anda alami. Semakin lengkap informasinya, semakin baik kami bisa membantu Anda..."></textarea>
                    <div class="field-help">
                        Tips: Ceritakan kronologi kejadian, apa yang dikatakan/dilakukan, bagaimana perasaan Anda
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Lokasi Kejadian:</label>
                        <input type="text" name="location" id="location" maxlength="200" 
                               placeholder="Contoh: Kelas 10-A, Kantin, Toilet">
                    </div>

                    <div class="form-group">
                        <label for="incident_date">Tanggal Kejadian:</label>
                        <input type="date" name="incident_date" id="incident_date" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="incident_time">Waktu Kejadian:</label>
                        <input type="time" name="incident_time" id="incident_time">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>3. Pihak Terlibat</h2>
                
                <div class="form-group">
                    <label for="parties_involved">Siapa yang melakukan bullying? (opsional):</label>
                    <textarea name="parties_involved" id="parties_involved" rows="3" 
                              placeholder="Anda bisa menggunakan inisial atau deskripsi. Contoh: 'Siswa kelas 11 berinisial A', 'Sekelompok siswa dari kelas sebelah'"></textarea>
                    <div class="field-help">Tidak perlu nama lengkap. Gunakan inisial atau deskripsi saja.</div>
                </div>

                <div class="form-group">
                    <label for="witnesses">Apakah ada saksi? (opsional):</label>
                    <textarea name="witnesses" id="witnesses" rows="2" 
                              placeholder="Contoh: 'Beberapa teman sekelas', 'Guru matematika'"></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2>4. Bukti Pendukung</h2>
                
                <div class="form-group">
                    <label for="attachments">Upload bukti (opsional):</label>
                    <input type="file" name="attachments[]" id="attachments" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.mp4,.avi,.mov">
                    <div class="field-help">
                        Format: JPG, PNG, PDF, DOC, MP4 (Maks. 10MB per file)<br>
                        File akan disimpan dengan aman dan hanya bisa diakses oleh konselor
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>5. Tingkat Urgensi</h2>
                
                <div class="form-group">
                    <label>Seberapa mendesak kasus ini? <span class="required">*</span></label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="urgency_level" value="normal" checked>
                            <span class="radio-text">
                                <strong>Normal</strong> - Saya butuh bantuan tapi tidak dalam bahaya
                            </span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="urgency_level" value="high">
                            <span class="radio-text">
                                <strong>Tinggi</strong> - Situasi serius dan perlu penanganan cepat
                            </span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="urgency_level" value="emergency">
                            <span class="radio-text">
                                <strong>Darurat</strong> - Saya dalam bahaya atau butuh bantuan SEGERA
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label class="checkbox-label important-checkbox">
                        <input type="checkbox" name="agreement" id="agreement" required>
                        <span>
                            Saya memahami bahwa memberikan laporan palsu dapat memiliki konsekuensi serius. 
                            Saya menyatakan bahwa informasi yang saya berikan adalah benar.
                        </span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary" id="submitBtn">
                    Kirim Laporan Anonim
                </button>
                <button type="button" class="btn-secondary" onclick="if(confirm('Yakin ingin mengosongkan form?')) this.form.reset()">
                    Reset Form
                </button>
            </div>
        </form>

        <footer>
            <p>Anda tidak sendirian. Kami peduli dan siap membantu.</p>
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

    // Show category description
    document.getElementById('category').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const desc = selected.getAttribute('data-desc');
        const descDiv = document.getElementById('categoryDesc');
        
        if (desc) {
            descDiv.innerHTML = desc;
            descDiv.style.display = 'block';
        } else {
            descDiv.style.display = 'none';
        }
    });

    // Form validation
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        
        // Check agreement
        if (!document.getElementById('agreement').checked) {
            alert('Mohon centang pernyataan persetujuan terlebih dahulu.');
            return;
        }
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Mengirim laporan...';
        
        // Submit form
        this.submit();
    });

    // Auto-save draft to localStorage
    const formInputs = document.querySelectorAll('input[type="text"], textarea, select');
    const DRAFT_KEY = 'bullying_report_draft';

    // Load draft
    window.addEventListener('load', function() {
        const draft = localStorage.getItem(DRAFT_KEY);
        if (draft) {
            if (confirm('Ada draft laporan yang tersimpan. Muat draft?')) {
                const data = JSON.parse(draft);
                for (let key in data) {
                    const field = document.querySelector(`[name="${key}"]`);
                    if (field) field.value = data[key];
                }
            } else {
                localStorage.removeItem(DRAFT_KEY);
            }
        }
    });

    // Save draft on input
    let saveTimeout;
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                const formData = {};
                formInputs.forEach(field => {
                    if (field.name && field.value) {
                        formData[field.name] = field.value;
                    }
                });
                localStorage.setItem(DRAFT_KEY, JSON.stringify(formData));
            }, 1000);
        });
    });
    </script>
</body>
</html>
