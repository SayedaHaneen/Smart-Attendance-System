<?php
// teacher/fetch_live_attendance.php - Fetch Live Attendance Stream (AJAX)
require_once '../config.php';

header('Content-Type: application/json');

$session_id = intval($_GET['session_id'] ?? 0);

if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

$db = getDB();

// Get session details
$session_query = "SELECT session_id, semester_id, batch_id, section_id FROM attendance_sessions WHERE session_id = ?";
$session_stmt = $db->prepare($session_query);
$session_stmt->bind_param("i", $session_id);
$session_stmt->execute();
$session = $session_stmt->get_result()->fetch_assoc();

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Session not found']);
    exit;
}

// Get live attendance with student details (most recent check-in at top)
$query = "SELECT a.*, s.full_name as student_name, s.roll_number, sd.device_identifier
          FROM attendance a
          JOIN students s ON a.student_id = s.student_id
          LEFT JOIN student_devices sd ON a.device_id = sd.device_id
          WHERE a.session_id = ?
          ORDER BY a.attendance_time DESC, a.attendance_id DESC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();

$attendance = [];
while ($row = $result->fetch_assoc()) {
    $attendance[] = [
        'roll_number' => $row['roll_number'],
        'student_name' => $row['student_name'],
        'status' => $row['status'],
        'time' => date('h:i:s A', strtotime($row['attendance_time'])),
        'device_id' => $row['device_identifier'] ?? ''
    ];
}

// Get total students enrolled in this section/semester/batch
$total = 0;
if ($session['semester_id'] && $session['batch_id'] && $session['section_id']) {
    $total_query = "SELECT COUNT(*) as total FROM students 
                    WHERE semester_id = ? AND batch_id = ? AND section_id = ?";
    $total_stmt = $db->prepare($total_query);
    $total_stmt->bind_param("iii", $session['semester_id'], $session['batch_id'], $session['section_id']);
    $total_stmt->execute();
    $total = $total_stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

// Generate dynamic rolling QR code data (valid for 15 seconds)
$rolling_hash = hash_hmac('sha256', $session_id . '|' . floor(time() / 15), 'SAS_SECRET_SALT');
$qr_data = $session_id . '|' . $rolling_hash;

// Update qr_code in DB to keep active state synced
$db->query("UPDATE attendance_sessions SET qr_code = '" . $db->real_escape_string($qr_data) . "' WHERE session_id = " . intval($session_id));

echo json_encode([
    'success' => true,
    'attendance' => $attendance,
    'marked' => count($attendance),
    'total' => $total,
    'qr_data' => $qr_data
]);
?>