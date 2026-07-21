<?php
// student/register_process.php - FIXED: Redirect to login after registration
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize input
$full_name = sanitize($_POST['full_name'] ?? '');
$username = sanitize($_POST['username'] ?? '');
$roll_number = sanitize($_POST['roll_number'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$department_id = intval($_POST['department_id'] ?? 0);
$batch_id = intval($_POST['batch_id'] ?? 0);
$semester_id = intval($_POST['semester_id'] ?? 0);
$section_id = intval($_POST['section_id'] ?? 0);
$phone = sanitize($_POST['phone'] ?? '');
$device_id = sanitize($_POST['device_id'] ?? '');

// Validation
if (empty($full_name) || empty($username) || empty($roll_number) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (empty($device_id)) {
    $device_id = 'MOB-' . strtoupper(substr(md5($_SERVER['HTTP_USER_AGENT'] . ($_SERVER['REMOTE_ADDR'] ?? '') . time()), 0, 14));
}

$db = getDB();

// Check duplicates
$check = $db->prepare("SELECT student_id FROM students WHERE username = ? OR email = ? OR roll_number = ?");
$check->bind_param("sss", $username, $email, $roll_number);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $dup_check = $db->prepare("SELECT username, email, roll_number FROM students WHERE student_id = ?");
    $dup_check->bind_param("i", $row['student_id']);
    $dup_check->execute();
    $dup_data = $dup_check->get_result()->fetch_assoc();
    
    $message = '';
    if ($dup_data['username'] === $username) {
        $message = 'Username already exists. Please choose another.';
    } elseif ($dup_data['email'] === $email) {
        $message = 'Email already registered. Please use another email.';
    } elseif ($dup_data['roll_number'] === $roll_number) {
        $message = 'Roll number already exists. Please check your roll number.';
    }
    
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Check if device is already registered to another student account
if (!empty($device_id)) {
    $dev_check = $db->prepare("SELECT sd.student_id, s.full_name FROM student_devices sd JOIN students s ON sd.student_id = s.student_id WHERE sd.device_identifier = ?");
    $dev_check->bind_param("s", $device_id);
    $dev_check->execute();
    $dev_res = $dev_check->get_result();

    if ($dev_res->num_rows > 0) {
        $existing = $dev_res->fetch_assoc();
        echo json_encode([
            'success' => false,
            'message' => '⚠️ Registration Blocked: This mobile device is already bound to student (' . htmlspecialchars($existing['full_name']) . '). Only 1 registration per device is allowed!'
        ]);
        exit;
    }
}

// Insert student (Pending Admin Approval: is_approved = 0)
$query = "INSERT INTO students (username, full_name, roll_number, email, password, department_id, batch_id, semester_id, section_id, phone, is_approved) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
$stmt = $db->prepare($query);
$stmt->bind_param("sssssiiiis", $username, $full_name, $roll_number, $email, $password, $department_id, $batch_id, $semester_id, $section_id, $phone);

if ($stmt->execute()) {
    $student_id = $db->insert_id;
    
    // Bind device
    $device_query = "INSERT INTO student_devices (student_id, device_identifier, device_name, is_active) VALUES (?, ?, ?, 1)";
    $device_name = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
    $device_stmt = $db->prepare($device_query);
    $device_stmt->bind_param("iss", $student_id, $device_id, $device_name);
    $device_stmt->execute();
    
    // Create notification for admin
    $notify_query = "INSERT INTO notifications (user_type, user_id, title, message) VALUES 
                     ('admin', 1, 'New Student Registration', 'A new student has registered: $full_name ($username).')";
    $db->query($notify_query);
    
    // Return success with redirect to login
    echo json_encode([
        'success' => true, 
        'message' => '✅ Registration successful! Your account is pending Admin approval. You can log in once approved.',
        'redirect' => 'login.php'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $db->error]);
}
?>