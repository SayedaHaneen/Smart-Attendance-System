<?php
// admin/logout.php - Admin Logout
require_once '../config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
redirect('../index.php');
?>