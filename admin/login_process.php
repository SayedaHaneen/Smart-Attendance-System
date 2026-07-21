<?php
// admin/login_process.php - Process Admin Login
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
$query = "SELECT * FROM admins WHERE email = ? OR username = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid username/email or password']);
    exit;
}

$admin = $result->fetch_assoc();

// Check password (plain text for development)
if ($password !== $admin['password']) {
    echo json_encode(['success' => false, 'message' => 'Invalid username/email or password']);
    exit;
}

// Set session
$_SESSION['user_id'] = $admin['admin_id'];
$_SESSION['user_type'] = 'admin';
$_SESSION['user_name'] = $admin['full_name'];

// Log login
logLogin('admin', $admin['admin_id']);

echo json_encode(['success' => true, 'message' => 'Login successful!', 'redirect' => 'dashboard.php']);
?>