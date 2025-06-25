<?php
// public/admin/logout.php
require_once __DIR__ . '/../src/includes/session_auth.php';

// Call the logout function.
// This function handles destroying the session and redirecting the user.
logout_user();
?>
