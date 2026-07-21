<?php
// fix_database.php - Fix all database issues
require_once 'config.php';

echo "<h1>Database Fix Script</h1>";

$db = getDB();

// 1. Check if attendance table exists and fix foreign keys
echo "<h2>1. Fixing Attendance Table</h2>";

// Drop foreign keys if they exist
$foreign_keys = [
    'attendance_ibfk_1',
    'attendance_ibfk_2', 
    'attendance_ibfk_3',
    'attendance_ibfk_4'
];

foreach ($foreign_keys as $fk) {
    $check = $db->query("SELECT COUNT(*) as count FROM information_schema.KEY_COLUMN_USAGE 
                         WHERE TABLE_SCHEMA = 'attendance_system' 
                         AND TABLE_NAME = 'attendance' 
                         AND CONSTRAINT_NAME = '$fk'");
    if ($check) {
        $row = $check->fetch_assoc();
        if ($row['count'] > 0) {
            $db->query("ALTER TABLE attendance DROP FOREIGN KEY $fk");
            echo "<p style='color:green;'>✅ Dropped foreign key: $fk</p>";
        }
    }
}

// Drop and recreate attendance table
echo "<h2>2. Recreating Attendance Table</h2>";

$db->query("DROP TABLE IF EXISTS attendance");

$sql = "CREATE TABLE attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    session_id INT NOT NULL,
    teacher_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_time TIME NOT NULL,
    status ENUM('Present', 'Absent', 'Late') DEFAULT 'Present',
    device_id INT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE
)";

if ($db->query($sql)) {
    echo "<p style='color:green;'>✅ Attendance table recreated successfully!</p>";
} else {
    echo "<p style='color:red;'>❌ Error: " . $db->error . "</p>";
}

// 3. Check if default student exists
echo "<h2>3. Checking Default Student</h2>";

$check = $db->query("SELECT * FROM students WHERE email = 'akash@university.edu'");
if ($check && $check->num_rows > 0) {
    $student = $check->fetch_assoc();
    echo "<p style='color:green;'>✅ Student found: " . $student['full_name'] . " (ID: " . $student['student_id'] . ")</p>";
} else {
    // Insert default student
    $sql = "INSERT INTO students (username, roll_number, full_name, email, password, department_id, batch_id, semester_id, section_id, phone, is_approved) 
            VALUES ('akash', '2025-SE-001', 'Akash Manglani', 'akash@university.edu', 'admin123', 1, 3, 2, 1, '1234567890', 1)";
    if ($db->query($sql)) {
        echo "<p style='color:green;'>✅ Default student created successfully!</p>";
    } else {
        echo "<p style='color:red;'>❌ Error creating student: " . $db->error . "</p>";
    }
}

// 4. Check if default teacher exists
echo "<h2>4. Checking Default Teacher</h2>";

$check = $db->query("SELECT * FROM teachers WHERE email = 'teacher@university.edu'");
if ($check && $check->num_rows > 0) {
    $teacher = $check->fetch_assoc();
    echo "<p style='color:green;'>✅ Teacher found: " . $teacher['full_name'] . " (ID: " . $teacher['teacher_id'] . ")</p>";
} else {
    $sql = "INSERT INTO teachers (username, email, password, full_name, department_id, phone) 
            VALUES ('teacher', 'teacher@university.edu', 'admin123', 'Mr. Ahmed', 1, '1234567890')";
    if ($db->query($sql)) {
        echo "<p style='color:green;'>✅ Default teacher created successfully!</p>";
    } else {
        echo "<p style='color:red;'>❌ Error creating teacher: " . $db->error . "</p>";
    }
}

// 5. Check if default admin exists
echo "<h2>5. Checking Default Admin</h2>";

$check = $db->query("SELECT * FROM admins WHERE email = 'admin@university.edu'");
if ($check && $check->num_rows > 0) {
    $admin = $check->fetch_assoc();
    echo "<p style='color:green;'>✅ Admin found: " . $admin['full_name'] . " (ID: " . $admin['admin_id'] . ")</p>";
} else {
    $sql = "INSERT INTO admins (username, email, password, full_name) 
            VALUES ('admin', 'admin@university.edu', 'admin123', 'System Admin')";
    if ($db->query($sql)) {
        echo "<p style='color:green;'>✅ Default admin created successfully!</p>";
    } else {
        echo "<p style='color:red;'>❌ Error creating admin: " . $db->error . "</p>";
    }
}

// 6. Fix any broken foreign keys
echo "<h2>6. Final Check</h2>";

echo "<h3>Tables in database:</h3>";
$tables = $db->query("SHOW TABLES");
echo "<ul>";
while ($row = $tables->fetch_row()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

echo "<h3>Students:</h3>";
$students = $db->query("SELECT student_id, full_name, email, is_approved FROM students");
if ($students && $students->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Approved</th></tr>";
    while ($row = $students->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['student_id'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . ($row['is_approved'] ? '✅ Yes' : '❌ No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>No students found!</p>";
}

echo "<h3>Teachers:</h3>";
$teachers = $db->query("SELECT teacher_id, full_name, email FROM teachers");
if ($teachers && $teachers->num_rows > 0) {
    while ($row = $teachers->fetch_assoc()) {
        echo "<p>ID: " . $row['teacher_id'] . " - " . $row['full_name'] . " (" . $row['email'] . ")</p>";
    }
} else {
    echo "<p style='color:red;'>No teachers found!</p>";
}

echo "<h3>Login Credentials:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Role</th><th>Username</th><th>Email</th><th>Password</th></tr>";
echo "<tr><td>Admin</td><td>admin</td><td>admin@university.edu</td><td>admin123</td></tr>";
echo "<tr><td>Teacher</td><td>teacher</td><td>teacher@university.edu</td><td>admin123</td></tr>";
echo "<tr><td>Student</td><td>akash</td><td>akash@university.edu</td><td>admin123</td></tr>";
echo "</table>";

echo "<br><br>";
echo "<a href='index.php' class='btn btn-primary'>Go to Home</a> ";
echo "<a href='student/dashboard.php' class='btn btn-success'>Student Dashboard</a> ";
echo "<a href='teacher/dashboard.php' class='btn btn-info'>Teacher Dashboard</a> ";
echo "<a href='admin/dashboard.php' class='btn btn-danger'>Admin Dashboard</a>";
?>