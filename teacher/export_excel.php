<?php
// teacher/export_excel.php - Export Attendance to Excel
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    die('Unauthorized');
}

$session_id = intval($_GET['session_id'] ?? 0);
if ($session_id <= 0) {
    die('Invalid session ID');
}

$teacher_id = $_SESSION['teacher_id'];
$db = getDB();

// Verify teacher owns this session
$verify_query = "SELECT * FROM attendance_sessions WHERE session_id = ? AND teacher_id = ?";
$verify_stmt = $db->prepare($verify_query);
$verify_stmt->bind_param("ii", $session_id, $teacher_id);
$verify_stmt->execute();
$session = $verify_stmt->get_result()->fetch_assoc();

if (!$session) {
    die('Unauthorized');
}

// Get attendance data
$query = "SELECT a.*, s.full_name as student_name, s.roll_number, s.email
          FROM attendance a
          JOIN students s ON a.student_id = s.student_id
          WHERE a.session_id = ?
          ORDER BY s.roll_number";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();

// Get subject name
$subject_query = "SELECT subject_name FROM subjects WHERE subject_id = ?";
$subject_stmt = $db->prepare($subject_query);
$subject_stmt->bind_param("i", $session['subject_id']);
$subject_stmt->execute();
$subject = $subject_stmt->get_result()->fetch_assoc();

// Set headers for Excel download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="attendance_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Roll Number',
    'Student Name',
    'Email',
    'Status',
    'Date',
    'Time'
]);

// Add data
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['roll_number'],
        $row['student_name'],
        $row['email'],
        $row['status'],
        date('d/m/Y', strtotime($row['attendance_date'])),
        date('h:i A', strtotime($row['attendance_time']))
    ]);
}

fclose($output);
exit;