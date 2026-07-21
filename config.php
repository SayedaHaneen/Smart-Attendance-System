<?php
// config.php - Main Configuration File with working QR Code
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_system');

// Application Configuration
define('APP_NAME', 'Smart Attendance System');
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$current_host = $_SERVER['HTTP_HOST'] ?? '192.168.100.39';
define('APP_URL', $protocol . $current_host . '/attendance_system_mock/');
define('APP_TIMEZONE', 'Asia/Karachi');

// Set Timezone
date_default_timezone_set(APP_TIMEZONE);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Connection
function getDB() {
    try {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            // Auto-create database if missing
            $rootConn = new mysqli(DB_HOST, DB_USER, DB_PASS);
            if (!$rootConn->connect_error) {
                $rootConn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
                $rootConn->select_db(DB_NAME);
                
                $sql_file = __DIR__ . '/Database/attendance_system.sql';
                if (file_exists($sql_file)) {
                    $sql = file_get_contents($sql_file);
                    $rootConn->multi_query($sql);
                    do {
                        if ($res = $rootConn->store_result()) { $res->free(); }
                    } while ($rootConn->more_results() && $rootConn->next_result());
                }
                return $rootConn;
            }
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        die("Database Connection Error: " . $e->getMessage());
    }
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// QR Code Generation Function - Using QR Server API (Working)
function generateQRCode($data, $size = 300) {
    // Use QR Server API (Free, working)
    $qrData = urlencode($data);
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$qrData}";
}

// Alternative: QR Code using qr-code-generator.com
function generateQRCodeAlt($data, $size = 300) {
    $qrData = urlencode($data);
    return "https://www.qr-code-generator.com/api/qr-code/?data={$qrData}&size={$size}";
}

// Save QR Code to file (if needed)
function generateQRCodeAndSave($data, $size = 300) {
    $tempDir = 'uploads/qr_codes/';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $filename = $tempDir . 'qr_' . md5($data . time()) . '.png';
    $qrUrl = generateQRCode($data, $size);
    
    // Download and save the QR code
    $imageData = file_get_contents($qrUrl);
    if ($imageData !== false) {
        file_put_contents($filename, $imageData);
        return APP_URL . $filename;
    }
    
    // If download fails, return URL directly
    return $qrUrl;
}

// Generate QR Code as Base64 (Inline)
function generateQRCodeBase64($data, $size = 300) {
    $qrUrl = generateQRCode($data, $size);
    $imageData = file_get_contents($qrUrl);
    if ($imageData !== false) {
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    return $qrUrl;
}

// Simple QR Code display (using HTML + JavaScript)
function generateQRCodeHTML($data) {
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($data);
}

// Other helper functions...
function getStudentAttendance($student_id, $limit = 10) {
    $db = getDB();
    $query = "SELECT a.*, s.subject_name, t.full_name as teacher_name, 
              sess.session_date, sess.start_time 
              FROM attendance a
              JOIN attendance_sessions sess ON a.session_id = sess.session_id
              JOIN subjects s ON sess.subject_id = s.subject_id
              JOIN teachers t ON sess.teacher_id = t.teacher_id
              WHERE a.student_id = ?
              ORDER BY a.attendance_date DESC, a.attendance_time DESC
              LIMIT ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $student_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function getTeacherSessions($teacher_id) {
    $db = getDB();
    $query = "SELECT sess.*, s.subject_name, 
              COUNT(a.attendance_id) as total_present
              FROM attendance_sessions sess
              JOIN subjects s ON sess.subject_id = s.subject_id
              LEFT JOIN attendance a ON sess.session_id = a.session_id AND a.status = 'Present'
              WHERE sess.teacher_id = ?
              GROUP BY sess.session_id
              ORDER BY sess.session_date DESC, sess.start_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getStudentCount() {
    $db = getDB();
    $result = $db->query("SELECT COUNT(*) as total FROM students");
    return $result->fetch_assoc()['total'];
}

function getTeacherCount() {
    $db = getDB();
    $result = $db->query("SELECT COUNT(*) as total FROM teachers");
    return $result->fetch_assoc()['total'];
}

function getTodayAttendance() {
    $db = getDB();
    $result = $db->query("SELECT COUNT(*) as total FROM attendance WHERE DATE(attendance_date) = CURDATE()");
    return $result->fetch_assoc()['total'];
}

function authenticateUser($email, $password, $user_type) {
    $db = getDB();
    $table = $user_type . 's';
    
    $query = "SELECT * FROM $table WHERE email = ? AND password = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    return false;
}

function checkDeviceBinding($student_id, $device_id) {
    $db = getDB();
    $query = "SELECT * FROM student_devices WHERE student_id = ? AND device_identifier = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("is", $student_id, $device_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function bindDevice($student_id, $device_id, $device_name = null) {
    $db = getDB();
    
    $query = "SELECT * FROM student_devices WHERE student_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $query = "UPDATE student_devices SET device_identifier = ?, device_name = ?, last_used = NOW() WHERE student_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssi", $device_id, $device_name, $student_id);
    } else {
        $query = "INSERT INTO student_devices (student_id, device_identifier, device_name) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iss", $student_id, $device_id, $device_name);
    }
    
    return $stmt->execute();
}

function logLogin($user_type, $user_id) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $query = "INSERT INTO login_logs (user_type, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("siss", $user_type, $user_id, $ip, $user_agent);
    return $stmt->execute();
}
?>