<?php
require_once '../config/database.php';
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi & Bantuan - Sistem Pengaduan Bullying</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Quick Exit Button -->
        <button id="quickExit" class="quick-exit" onclick="window.location.href='https://www.google.com'">
            ❌ Keluar Cepat (ESC)
        </button>

        <header>
            <h1>ℹ️ Informasi & Bantuan</h1>
            <p class="subtitle">Panduan lengkap sistem pengaduan bullying</p>
        </header>

        <nav class="main-nav">
            <a href="index.php">📝 Buat Laporan</a>
            <a href="track.php">🔍 Cek Status Laporan</a>
            <a href="info.php" class="active">ℹ️ Informasi & Bantuan</a>
        </nav>

        <div class="info-page">
            <!-- Apa itu Bullying -->
            <section class="info-section">
                <h2>🤔 Apa itu Bullying?</h2>
                <p>Bullying adalah perilaku agresif yang dilakukan secara berulang dengan tujuan menyakiti orang lain baik secara fisik maupun emosional.</p>
                
                <div class="info-grid">
                    <div class="info-card">
                        <h3>💢 Bullying Verbal</h3>
                        <ul>
                            <li>Mengejek atau menghina</li>
                            <li>Memberikan julukan yang menyakitkan</li>
                            <li>Mengancam</li>
                            <li>Komentar yang merendahkan</li>
                        </ul>
                    </div>
                    
                    <div class="info-card">
                        <h3>👊 Bullying Fisik</h3>
                        <ul>
                            <li>Memukul atau mendorong</li>
                            <li>Merusak barang milik orang lain</li>
                            <li>Mengambil paksa barang orang</li>
                            <li>Menyakiti secara fisik</li>
                        </ul>
                    </div>
                    
                    <div class="info-card">
                        <h3>📱 Cyberbullying</h3>
                        <ul>
                            <li>Mengirim pesan yang menyakitkan</li>
                            <li>Menyebarkan foto/video tanpa izin</li>
                            <li>Membuat akun palsu untuk menghina</li>
                            <li>Pelecehan di media sosial</li>
                        </ul>
                    </div>
                    
                    <div class="info-card">
                        <h3>🚫 Bullying Sosial</h3>
                        <ul>
                            <li>Mengucilkan dari kelompok</li>
                            <li>Menyebarkan rumor atau gosip</li>
                            <li>Mempermalukan di depan umum</li>
                            <li>Merusak reputasi</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Cara Kerja Sistem -->
            <section class="info-section">
                <h2>⚙️ Cara Kerja Sistem Pengaduan</h2>
                
                <div class="process-timeline">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>📝 Buat Laporan</h4>
                            <p>Isi form laporan secara anonim. Tidak perlu login atau memberikan identitas asli.</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>🔐 Dapatkan Kode & PIN</h4>
                            <p>Setelah submit, Anda akan mendapat kode pelacakan dan PIN rahasia. Simpan baik-baik!</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>👁️ Tim Review</h4>
                            <p>Tim konselor terlatih akan meninjau laporan Anda dalam 24 jam (lebih cepat untuk kasus darurat).</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>💬 Komunikasi Anonim</h4>
                            <p>Gunakan kode & PIN untuk cek status dan berkomunikasi dengan konselor secara anonim.</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h4>✅ Penyelesaian</h4>
                            <p>Tim akan mengambil tindakan yang diperlukan untuk menyelesaikan kasus Anda.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section class="info-section">
                <h2>❓ Pertanyaan yang Sering Diajukan (FAQ)</h2>
                
                <div class="faq-list">
                    <details class="faq-item">
                        <summary>Apakah identitas saya benar-benar aman?</summary>
                        <p>Ya, 100% aman. Sistem kami dirancang untuk melindungi anonimitas Anda. Tidak ada yang bisa mengetahui identitas asli Anda, bahkan admin sekalipun. Yang tersimpan hanya kode pelacakan yang Anda miliki.</p>
                    </details>
                    
                    <details class="faq-item">
                        <summary>Bagaimana jika saya lupa PIN?</summary>
                        <p>Sayangnya, demi keamanan, PIN tidak dapat dipulihkan jika hilang. Pastikan Anda menyimpannya di tempat yang aman. Anda dapat screenshot atau catat di tempat yang hanya Anda yang tahu.</p>
                    </details>
                    
                    <details class="faq-item">
                        <summary>Berapa lama laporan saya akan diproses?</summary>
                        <p>Laporan normal akan ditinjau dalam 24 jam. Laporan dengan prioritas tinggi akan ditinjau dalam 12 jam. Kasus darurat akan segera ditangani dalam beberapa jam.</p>
                    </details>
                    
                    <details class="faq-item">
                        <summary>Apa yang terjadi setelah saya melaporkan?</summary>
                        <p>Tim konselor akan meninjau laporan Anda, menghubungi Anda melalui sistem chat anonim jika perlu informasi tambahan, dan mengambil tindakan yang diperlukan sesuai dengan kebijakan sekolah.</p>
                    </details>
                    
                    <details class="faq-item">
                        <summary>Bisakah pelaku tahu bahwa saya yang melaporkan?</summary>
                        <p>Tidak. Identitas pelapor dijaga ketat. Tindakan akan diambil tanpa mengungkap siapa yang melaporkan.</p>
                    </details>
                    
                    <details class="faq-item">
                        <summary>Apakah saya bisa melaporkan untuk orang lain?</summary>
                        <p>Ya, Anda bisa melaporkan jika melihat orang lain menjadi korban bullying. Jelaskan dalam laporan bahwa Anda adalah saksi.</p>
                    </details>
                    
                    <details class="faq-item">
                        <summary>Apa konsekuensi membuat laporan palsu?</summary>
                        <p>Laporan palsu dapat memiliki konsekuensi serius sesuai peraturan sekolah. Pastikan informasi yang Anda berikan adalah benar dan jujur.</p>
                    </details>
                </div>
            </section>

            <!-- Tips Menghadapi Bullying -->
            <section class="info-section">
                <h2>💡 Tips Menghadapi Bullying</h2>
                
                <div class="tips-grid">
                    <div class="tip-card">
                        <h3>🛡️ Untuk Korban</h3>
                        <ul>
                            <li>Jangan menyalahkan diri sendiri</li>
                            <li>Ceritakan pada orang yang dipercaya</li>
                            <li>Simpan bukti (screenshot, foto, dll)</li>
                            <li>Jauhi pelaku jika memungkinkan</li>
                            <li>Jangan membalas dengan kekerasan</li>
                            <li>Gunakan sistem ini untuk melaporkan</li>
                        </ul>
                    </div>
                    
                    <div class="tip-card">
                        <h3>👥 Untuk Saksi</h3>
                        <ul>
                            <li>Jangan diam saja, laporkan!</li>
                            <li>Dukung korban secara emosional</li>
                            <li>Jangan ikut-ikutan mem-bully</li>
                            <li>Ajak korban untuk melaporkan</li>
                            <li>Jadi saksi yang baik</li>
                            <li>Bantu kumpulkan bukti</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Kontak Darurat -->
            <section class="info-section emergency-section">
                <h2>🚨 Kontak Darurat</h2>
                <p>Jika Anda dalam bahaya segera atau butuh bantuan mendesak:</p>
                
                <div class="emergency-contacts">
                    <div class="contact-card">
                        <h3>📞 Telepon Pelayanan Sosial Anak</h3>
                        <p class="contact-number">119</p>
                        <p>Layanan 24 jam, gratis</p>
                    </div>
                    
                    <div class="contact-card">
                        <h3>💬 Konseling Online KPAI</h3>
                        <p class="contact-number">021-319-01556</p>
                        <p>Senin-Jumat, 08:00-16:00</p>
                    </div>
                    
                    <div class="contact-card">
                        <h3>🏥 Unit Gawat Darurat</h3>
                        <p class="contact-number">112</p>
                        <p>Untuk keadaan darurat medis</p>
                    </div>
                </div>
            </section>

            <!-- Resources -->
            <section class="info-section">
                <h2>📚 Sumber Daya & Bacaan</h2>
                
                <div class="resources-list">
                    <div class="resource-item">
                        <h4>📖 Panduan Anti-Bullying untuk Siswa</h4>
                        <p>Pelajari lebih lanjut tentang hak-hak Anda dan cara melindungi diri.</p>
                    </div>
                    
                    <div class="resource-item">
                        <h4>🎥 Video Edukasi</h4>
                        <p>Tonton video tentang cara mengenali dan menghadapi bullying.</p>
                    </div>
                    
                    <div class="resource-item">
                        <h4>👨‍⚕️ Konseling Psikologi</h4>
                        <p>Jadwal konseling gratis dengan psikolog sekolah setiap hari Rabu.</p>
                    </div>
                </div>
            </section>
        </div>

        <footer>
            <p>💚 Anda tidak sendirian. Kami di sini untuk membantu.</p>
            <p>Sistem Pengaduan Bullying - Dilindungi & Rahasia</p>
        </footer>
    </div>

    <style>
    .info-page {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: var(--shadow-md);
    }
    
    .info-section {
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .info-section:last-child {
        border-bottom: none;
    }
    
    .info-section h2 {
        color: var(--dark);
        margin-bottom: 20px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .info-card {
        background: var(--light);
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid var(--primary-color);
    }
    
    .info-card h3 {
        color: var(--primary-color);
        margin-bottom: 15px;
    }
    
    .info-card ul {
        list-style: none;
        padding-left: 0;
    }
    
    .info-card li {
        padding: 5px 0;
        color: var(--secondary-color);
    }
    
    .info-card li:before {
        content: "• ";
        color: var(--primary-color);
        font-weight: bold;
    }
    
    /* Process Timeline */
    .process-timeline {
        margin: 30px 0;
    }
    
    .process-step {
        display: flex;
        align-items: flex-start;
        margin-bottom: 30px;
        position: relative;
    }
    
    .process-step:not(:last-child):after {
        content: '';
        position: absolute;
        left: 20px;
        top: 45px;
        bottom: -30px;
        width: 2px;
        background: var(--border-color);
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
        margin-right: 20px;
    }
    
    .step-content h4 {
        color: var(--dark);
        margin-bottom: 8px;
    }
    
    .step-content p {
        color: var(--secondary-color);
    }
    
    /* FAQ */
    .faq-list {
        margin-top: 20px;
    }
    
    .faq-item {
        background: var(--light);
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
    }
    
    .faq-item summary {
        font-weight: 600;
        color: var(--dark);
        cursor: pointer;
        padding: 5px;
    }
    
    .faq-item summary:hover {
        color: var(--primary-color);
    }
    
    .faq-item p {
        margin-top: 15px;
        color: var(--secondary-color);
        line-height: 1.6;
    }
    
    /* Tips */
    .tips-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .tip-card {
        background: var(--light);
        padding: 25px;
        border-radius: 10px;
        border-top: 3px solid var(--success-color);
    }
    
    .tip-card h3 {
        color: var(--success-color);
        margin-bottom: 15px;
    }
    
    .tip-card ul {
        list-style: none;
        padding-left: 0;
    }
    
    .tip-card li {
        padding: 8px 0;
        color: var(--secondary-color);
    }
    
    .tip-card li:before {
        content: "✓ ";
        color: var(--success-color);
        font-weight: bold;
    }
    
    /* Emergency Contacts */
    .emergency-section {
        background: #fef2f2;
        padding: 25px;
        border-radius: 10px;
        border-left: 5px solid var(--danger-color);
    }
    
    .emergency-contacts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .contact-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }
    
    .contact-card h3 {
        color: var(--dark);
        font-size: 1em;
        margin-bottom: 10px;
    }
    
    .contact-number {
        font-size: 2em;
        font-weight: bold;
        color: var(--danger-color);
        margin: 10px 0;
    }
    
    /* Resources */
    .resources-list {
        margin-top: 20px;
    }
    
    .resource-item {
        background: var(--light);
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 15px;
        border-left: 3px solid var(--info-color);
    }
    
    .resource-item h4 {
        color: var(--info-color);
        margin-bottom: 10px;
    }
    
    .resource-item p {
        color: var(--secondary-color);
    }
    
    @media (max-width: 768px) {
        .info-grid,
        .tips-grid,
        .emergency-contacts {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
    // Quick exit with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'https://www.google.com';
        }
    });
    </script>
</body>
</html>
