<?php
session_start();

// Clear tracking session
unset($_SESSION['report_tracking']);

// Redirect back to tracking page
header('Location: track.php');
exit;
?>
