<?php
// teacher/manual_mark_student.php - Teacher Manual Attendance Entry Endpoint
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$session_id = intval($_POST['session_id'] ?? ($data['session_id'] ?? 0));
$student_id = intval($_POST['student_id'] ?? ($data['student_id'] ?? 0));
$status = sanitize($_POST['status'] ?? ($data['status'] ?? 'Present'));

if ($session_id <= 0 || $student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid student and session']);
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$db = getDB();

// 1. Verify teacher owns the session
$sess_stmt = $db->prepare("SELECT session_id, subject_id FROM attendance_sessions WHERE session_id = ? AND teacher_id = ?");
$sess_stmt->bind_param("ii", $session_id, $teacher_id);
$sess_stmt->execute();
$session = $sess_stmt->get_result()->fetch_assoc();

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Session not found or unauthorized']);
    exit;
}

// 2. Check if student attendance is already recorded for this session
$check = $db->prepare("SELECT attendance_id FROM attendance WHERE student_id = ? AND session_id = ?");
$check->bind_param("ii", $student_id, $session_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing) {
    // Update existing status
    $up = $db->prepare("UPDATE attendance SET status = ?, marked_at = NOW() WHERE attendance_id = ?");
    $up->bind_param("si", $status, $existing['attendance_id']);
    if ($up->execute()) {
        echo json_encode(['success' => true, 'message' => "Student attendance updated to '$status' successfully!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update attendance: ' . $db->error]);
    }
} else {
    // Insert new manual attendance record
    $ins = $db->prepare("INSERT INTO attendance (student_id, session_id, teacher_id, attendance_date, attendance_time, status, device_identifier) VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, 'MANUAL_TEACHER_ENTRY')");
    $ins->bind_param("iiis", $student_id, $session_id, $teacher_id, $status);
    if ($ins->execute()) {
        echo json_encode(['success' => true, 'message' => "Student attendance manually marked as '$status'!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark attendance: ' . $db->error]);
    }
}
?>
