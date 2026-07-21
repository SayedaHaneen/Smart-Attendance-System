<?php
// student/logout.php - Proper logout with session cleanup
session_start();
require_once '../config.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
    $db = getDB();
    
    // Deactivate the active session
    $query = "UPDATE active_sessions SET is_active = 0 WHERE session_token = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $_SESSION['session_token']);
    $stmt->execute();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
redirect('../index.php?logout=success');
?>