<?php
// admin/force_logout.php - Admin can force logout a student
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();

// Get student ID from URL
$student_id = intval($_GET['student_id'] ?? 0);
if ($student_id <= 0) {
    redirect('manage_students.php');
}

// Deactivate all active sessions for this student
$query = "UPDATE active_sessions SET is_active = 0 WHERE student_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $student_id);

if ($stmt->execute()) {
    $message = "✅ Student has been logged out from all devices!";
    $message_type = "success";
} else {
    $message = "❌ Failed to logout student: " . $db->error;
    $message_type = "danger";
}

// Also clear the device binding if requested
if (isset($_GET['reset_device'])) {
    $reset = $db->prepare("UPDATE student_devices SET is_active = 0 WHERE student_id = ?");
    $reset->bind_param("i", $student_id);
    $reset->execute();
    $message .= " Device binding reset!";
}

// Redirect back
header("Location: manage_students.php?message=" . urlencode($message) . "&type=" . $message_type);
exit;
?>