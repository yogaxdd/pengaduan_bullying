<?php
require_once '../includes/session.php';

// Add logout audit log before destroying session
if (isAdminLoggedIn()) {
    require_once '../config/database.php';
    addAuditLog('logout', 'admin', getCurrentAdminId(), 'Admin logged out');
}

// Logout admin
logoutAdmin();

// Redirect to login page
header('Location: login.php');
exit;
?>
