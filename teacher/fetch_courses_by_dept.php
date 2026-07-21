<?php
// teacher/fetch_courses_by_dept.php - AJAX endpoint returning filtered subjects by department & semester
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$department_id = intval($_GET['department_id'] ?? ($_POST['department_id'] ?? 0));
$semester_id = intval($_GET['semester_id'] ?? ($_POST['semester_id'] ?? 0));

$db = getDB();

$query = "SELECT subject_id, subject_code, subject_name FROM subjects WHERE 1=1";
$params = [];
$types = "";

if ($department_id > 0) {
    $query .= " AND (department_id = ? OR department_id IS NULL)";
    $params[] = $department_id;
    $types .= "i";
}

if ($semester_id > 0) {
    $query .= " AND (semester_id = ? OR semester_id IS NULL)";
    $params[] = $semester_id;
    $types .= "i";
}

$query .= " ORDER BY subject_code, subject_name";
$stmt = $db->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = [
        'subject_id' => $row['subject_id'],
        'subject_code' => $row['subject_code'],
        'subject_name' => $row['subject_name']
    ];
}

echo json_encode([
    'success' => true,
    'courses' => $courses,
    'count' => count($courses)
]);
?>
