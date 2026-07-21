<?php
// student/save_attendance.php - Anti-Proxy Device-Locked Attendance Processor
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$raw_input = sanitize(strval($data['session_id'] ?? ''));
$client_device_id = sanitize($_POST['device_id'] ?? ($data['device_id'] ?? ($_SESSION['device_id'] ?? '')));

if (empty($raw_input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid session code or QR data']);
    exit;
}

// Extract 4-digit code if payload is formatted as "code|timestamp"
$code_parts = explode('|', $raw_input);
$code_input = trim($code_parts[0]);
$code_numeric = intval($code_input);

$student_id = $_SESSION['student_id'];
$db = getDB();

// 1. Verify Student Single-Device Hardware Binding
$device_stmt = $db->prepare("SELECT device_identifier FROM student_devices WHERE student_id = ? AND is_active = 1");
$device_stmt->bind_param("i", $student_id);
$device_stmt->execute();
$bound_device = $device_stmt->get_result()->fetch_assoc();

if ($bound_device) {
    $registered_token = $bound_device['device_identifier'];
    if (!empty($client_device_id) && $client_device_id !== $registered_token) {
        echo json_encode([
            'success' => false, 
            'message' => '⛔ Device Security Error: You can only mark attendance from your registered bound device. Proxy login from unauthorized device is blocked.'
        ]);
        exit;
    }
    $effective_device_id = $registered_token;
} else {
    $effective_device_id = !empty($client_device_id) ? $client_device_id : ('DEV-' . $student_id);
}

// 2. Find Active Session matching session_code, numeric session_id, or qr_code
$query = "SELECT sess.*, s.subject_name, t.full_name as teacher_name 
          FROM attendance_sessions sess
          JOIN subjects s ON sess.subject_id = s.subject_id
          JOIN teachers t ON sess.teacher_id = t.teacher_id
          WHERE (sess.session_code = ? OR sess.session_id = ? OR sess.qr_code LIKE ?) AND sess.is_active = 1
          ORDER BY sess.session_id DESC LIMIT 1";

$qr_pattern = '%' . $code_input . '%';
$stmt = $db->prepare($query);
$stmt->bind_param("sis", $code_input, $code_numeric, $qr_pattern);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired session code']);
    exit;
}

$actual_session_id = $session['session_id'];

// Verify Student Cohort (Department, Batch, Semester, Section) matches Session Requirements
$student_info_stmt = $db->prepare("SELECT department_id, batch_id, semester_id, section_id FROM students WHERE student_id = ?");
$student_info_stmt->bind_param("i", $student_id);
$student_info_stmt->execute();
$student_info = $student_info_stmt->get_result()->fetch_assoc();

if ($student_info) {
    // Check Department Match
    $session_dept_id = $session['department_id'] ?? 0;
    if ($session_dept_id <= 0 && !empty($session['subject_id'])) {
        $sub_dept = $db->query("SELECT department_id FROM subjects WHERE subject_id = " . intval($session['subject_id']))->fetch_assoc();
        $session_dept_id = $sub_dept['department_id'] ?? 0;
    }

    if ($session_dept_id > 0 && $session_dept_id != $student_info['department_id']) {
        echo json_encode(['success' => false, 'message' => '⚠️ Access Blocked: This QR session is for a different Department class.']);
        exit;
    }
    if (!empty($session['batch_id']) && $session['batch_id'] != $student_info['batch_id']) {
        echo json_encode(['success' => false, 'message' => '⚠️ Access Blocked: This QR session is for a different Batch year.']);
        exit;
    }
    if (!empty($session['semester_id']) && $session['semester_id'] != $student_info['semester_id']) {
        echo json_encode(['success' => false, 'message' => '⚠️ Access Blocked: This QR session is for a different Semester class.']);
        exit;
    }
    if (!empty($session['section_id']) && $session['section_id'] != $student_info['section_id']) {
        echo json_encode(['success' => false, 'message' => '⚠️ Access Blocked: This QR session is for a different Section.']);
        exit;
    }
}

// 3. Rule 1: Check if THIS student already marked attendance for this session
$check_student = $db->prepare("SELECT attendance_id FROM attendance WHERE student_id = ? AND session_id = ?");
$check_student->bind_param("ii", $student_id, $actual_session_id);
$check_student->execute();
if ($check_student->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => '⚠️ Attendance already marked for this course session.']);
    exit;
}

// 4. Rule 2: Anti-Proxy Check - Prevent another student from using the SAME physical device for the SAME session
$check_device = $db->prepare("SELECT attendance_id, student_id FROM attendance WHERE session_id = ? AND device_identifier = ?");
$check_device->bind_param("is", $actual_session_id, $effective_device_id);
$check_device->execute();
$device_record = $check_device->get_result();

if ($device_record->num_rows > 0) {
    $existing = $device_record->fetch_assoc();
    if ($existing['student_id'] != $student_id) {
        echo json_encode([
            'success' => false, 
            'message' => '⛔ Proxy Prevention: This physical device has already been used by another student to mark attendance for this session.'
        ]);
        exit;
    }
}

// 5. Record Student Attendance with Device Lock Identification
$insert_query = "INSERT INTO attendance (student_id, session_id, teacher_id, attendance_date, attendance_time, status, device_identifier) 
                VALUES (?, ?, ?, CURDATE(), CURTIME(), 'Present', ?)";
$insert_stmt = $db->prepare($insert_query);
$insert_stmt->bind_param("iiis", $student_id, $actual_session_id, $session['teacher_id'], $effective_device_id);

if ($insert_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Attendance marked successfully',
        'subject' => $session['subject_name'],
        'teacher' => $session['teacher_name'],
        'time' => date('h:i A'),
        'status' => 'Present'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record attendance: ' . $db->error]);
}
?>