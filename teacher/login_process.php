<?php
// teacher/login_process.php - Process Teacher Login
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username/Email and password are required']);
    exit;
}

$db = getDB();

// Check if username is email or username
$query = "SELECT * FROM teachers WHERE email = ? OR username = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid username/email or password']);
    exit;
}

$teacher = $result->fetch_assoc();

// Check password (plain text for development)
if ($password !== $teacher['password']) {
    echo json_encode(['success' => false, 'message' => 'Invalid username/email or password']);
    exit;
}

// Set session
$_SESSION['user_id'] = $teacher['teacher_id'];
$_SESSION['user_type'] = 'teacher';
$_SESSION['user_name'] = $teacher['full_name'];
$_SESSION['teacher_id'] = $teacher['teacher_id'];

// Log login
logLogin('teacher', $teacher['teacher_id']);

echo json_encode(['success' => true, 'message' => 'Login successful!', 'redirect' => 'dashboard.php']);
?>