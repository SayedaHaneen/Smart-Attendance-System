<?php
// fix_student.php - Fix student issues
require_once 'config.php';

// Get student ID from session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    echo "Please login as student first";
    exit;
}

$student_id = $_SESSION['student_id'];
$db = getDB();

echo "<h1>Fixing Student: ID $student_id</h1>";

// Check if student exists
$check = $db->query("SELECT * FROM students WHERE student_id = $student_id");
if ($check && $check->num_rows > 0) {
    $student = $check->fetch_assoc();
    echo "<p style='color:green;'>✅ Student found: " . $student['full_name'] . "</p>";
    
    // Update student to be approved
    $update = $db->query("UPDATE students SET is_approved = 1 WHERE student_id = $student_id");
    if ($update) {
        echo "<p style='color:green;'>✅ Student approved!</p>";
    }
    
    // Show student data
    echo "<h2>Student Data:</h2>";
    echo "<table border='1' cellpadding='5'>";
    foreach ($student as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>❌ Student not found! Creating default...</p>";
    
    // Insert default student for this session
    $sql = "INSERT INTO students (username, roll_number, full_name, email, password, department_id, batch_id, semester_id, section_id, phone, is_approved) 
            VALUES ('student', '2025-SE-999', 'Test Student', 'student@university.edu', 'admin123', 1, 3, 2, 1, '1234567890', 1)";
    
    if ($db->query($sql)) {
        $new_id = $db->insert_id;
        echo "<p style='color:green;'>✅ New student created with ID: $new_id</p>";
        echo "<p>Please login again with: student@university.edu / admin123</p>";
    } else {
        echo "<p style='color:red;'>❌ Error: " . $db->error . "</p>";
    }
}

echo "<br><a href='student/dashboard.php' class='btn btn-primary'>Go to Dashboard</a>";
echo " | <a href='logout.php' class='btn btn-danger'>Logout</a>";
?>