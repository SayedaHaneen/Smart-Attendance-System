<?php
// student/login_process.php - CLEAN VERSION
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember_device = isset($_POST['remember_device']);
$device_id = $_POST['device_id'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username/Email and password are required']);
    exit;
}

if (empty($device_id)) {
    $device_id = 'MOB-' . strtoupper(substr(md5($_SERVER['HTTP_USER_AGENT'] . ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 14));
}

$db = getDB();

// Check if username is email or username
$query = "SELECT * FROM students WHERE email = ? OR username = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '❌ Invalid username/email or password']);
    exit;
}

$student = $result->fetch_assoc();

// Check if student account is approved by admin
if ($student['is_approved'] != 1) {
    echo json_encode([
        'success' => false,
        'message' => '⏳ Account Pending Approval: Your registration is waiting for Admin approval. Please contact administrator to approve your profile.'
    ]);
    exit;
}

// Check password - DIRECT COMPARISON
if ($password !== $student['password']) {
    echo json_encode(['success' => false, 'message' => '❌ Invalid username/email or password']);
    exit;
}

// Check device binding
$device_check = $db->prepare("SELECT * FROM student_devices WHERE student_id = ?");
$device_check->bind_param("i", $student['student_id']);
$device_check->execute();
$device_result = $device_check->get_result();

$is_new_device = true;

if ($device_result->num_rows > 0) {
    $device = $device_result->fetch_assoc();
    if ($device['device_identifier'] === $device_id) {
        $is_new_device = false;
        // Update last used
        $update = $db->prepare("UPDATE student_devices SET last_used = NOW() WHERE device_id = ?");
        $update->bind_param("i", $device['device_id']);
        $update->execute();
    } else {
        // Different device - prevent login
        echo json_encode([
            'success' => false, 
            'message' => '⚠️ This account is already registered on another device. Contact admin to reset.'
        ]);
        exit;
    }
}

// If new device, bind it
if ($is_new_device) {
    $device_query = "INSERT INTO student_devices (student_id, device_identifier, device_name, is_active) 
                     VALUES (?, ?, ?, 1)";
    $device_name = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
    $device_stmt = $db->prepare($device_query);
    $device_stmt->bind_param("iss", $student['student_id'], $device_id, $device_name);
    $device_stmt->execute();
}

// Set session
$_SESSION['user_id'] = $student['student_id'];
$_SESSION['user_type'] = 'student';
$_SESSION['user_name'] = $student['full_name'];
$_SESSION['student_id'] = $student['student_id'];
$_SESSION['device_id'] = $device_id;
$_SESSION['login_time'] = time();

// Log login
logLogin('student', $student['student_id']);

echo json_encode([
    'success' => true, 
    'message' => '✅ Login successful! Welcome ' . $student['full_name'],
    'redirect' => 'dashboard.php'
]);
?>