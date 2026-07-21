<?php
// setup_simple.php - Simple setup script without hashing
require_once 'config.php';

$db = getDB();

// Check if users already exist
$check_admin = $db->query("SELECT COUNT(*) as count FROM admins");
$admin_count = $check_admin->fetch_assoc()['count'];

if ($admin_count > 0) {
    echo "<div class='alert alert-warning'>Users already exist in the database!</div>";
    echo "<a href='index.php'>Go to Home Page</a>";
    exit;
}

// Insert Admin
$admin_query = "INSERT INTO admins (username, email, password, full_name) VALUES 
                ('admin', 'admin@university.edu', 'admin123', 'System Admin')";
$db->query($admin_query);

// Insert Teacher
$teacher_query = "INSERT INTO teachers (email, password, full_name, department_id, phone) VALUES 
                  ('teacher@university.edu', 'admin123', 'Mr. Ahmed', 1, '1234567890')";
$db->query($teacher_query);

// Insert Student
$student_query = "INSERT INTO students (roll_number, full_name, email, password, department_id, batch_id, semester_id, section_id, phone) VALUES 
                  ('2025-SE-001', 'Akash Manglani', 'akash@university.edu', 'admin123', 1, 3, 2, 1, '1234567890')";
$db->query($student_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Complete - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3><i class="fas fa-check-circle"></i> Setup Complete!</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    Default users have been created successfully!
                </div>
                
                <h5>Login Credentials:</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-danger">Admin</span></td>
                            <td>admin@university.edu</td>
                            <td><code>admin123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-success">Teacher</span></td>
                            <td>teacher@university.edu</td>
                            <td><code>admin123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-primary">Student</span></td>
                            <td>akash@university.edu</td>
                            <td><code>admin123</code></td>
                        </tr>
                    </tbody>
                </table>
                
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go to Home Page
                </a>
            </div>
        </div>
    </div>
</body>
</html>