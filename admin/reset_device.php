<?php
// admin/reset_device.php - Reset Student Device
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$student_id = intval($data['student_id'] ?? 0);

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

$db = getDB();

// Delete device records for this student
$query = "DELETE FROM student_devices WHERE student_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $student_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Device reset successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reset device: ' . $db->error]);
}
?>