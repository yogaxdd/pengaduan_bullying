<?php
// Helper function to get system settings
function getSystemSetting($db, $key, $default = '') {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Get all school settings
function getSchoolSettings($db) {
    return [
        'name' => getSystemSetting($db, 'school_name', 'Sistem Pengaduan Bullying'),
        'tagline' => getSystemSetting($db, 'school_tagline', 'Laporan Anda dijamin 100% ANONIM dan akan ditangani dengan serius oleh tim konseling sekolah'),
        'logo' => getSystemSetting($db, 'school_logo', ''),
        'background' => getSystemSetting($db, 'school_background', '')
    ];
}
?>
