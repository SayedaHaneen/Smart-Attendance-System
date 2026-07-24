<?php
// api/login.php - REST API Authentication Endpoint
require_once '../config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$username = sanitize($input['username'] ?? '');
$password = sanitize($input['password'] ?? '');
$device_identifier = sanitize($input['device_id'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Missing username or password.']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM students WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if ($student && ($password === $student['password'] || password_verify($password, $student['password']))) {
    if (!$student['is_approved']) {
        echo json_encode(['success' => false, 'message' => 'Your student account is pending administrator approval.']);
        exit;
    }
    
    // Check Single Device Binding
    $dev_stmt = $db->prepare("SELECT device_identifier FROM student_devices WHERE student_id = ? AND is_active = 1");
    $dev_stmt->bind_param("i", $student['student_id']);
    $dev_stmt->execute();
    $device = $dev_stmt->get_result()->fetch_assoc();
    
    if ($device) {
        if (!empty($device_identifier) && $device_identifier !== $device['device_identifier']) {
            echo json_encode(['success' => false, 'message' => 'Device Lock Activated: You can only login from your registered device.']);
            exit;
        }
    } else {
        if (!empty($device_identifier)) {
            $reg_stmt = $db->prepare("INSERT INTO student_devices (student_id, device_identifier, is_active) VALUES (?, ?, 1)");
            $reg_stmt->bind_param("is", $student['student_id'], $device_identifier);
            $reg_stmt->execute();
        }
    }
    
    $_SESSION['user_id'] = $student['student_id'];
    $_SESSION['user_type'] = 'student';
    $_SESSION['student_id'] = $student['student_id'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Authentication successful.',
        'student' => [
            'student_id' => $student['student_id'],
            'username' => $student['username'],
            'full_name' => $student['full_name'],
            'roll_number' => $student['roll_number']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
}
exit;
?>
