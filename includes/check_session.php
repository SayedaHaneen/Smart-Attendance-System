<?php
// includes/check_session.php - Check if session is valid
// Include this in every page after session_start()

function validateSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    $db = getDB();
    $student_id = $_SESSION['user_id'];
    $session_token = $_SESSION['session_token'];
    
    $query = "SELECT * FROM active_sessions 
              WHERE student_id = ? AND session_token = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("is", $student_id, $session_token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    // Update last activity
    $update = $db->prepare("UPDATE active_sessions SET last_activity = NOW() WHERE session_token = ?");
    $update->bind_param("s", $session_token);
    $update->execute();
    
    return true;
}

// Usage: Add this at the top of every page after session_start()
// if (!validateSession()) {
//     session_destroy();
//     redirect('login.php');
// }
?>