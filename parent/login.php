<?php
// parent/login.php - Parent Portal Login Page
require_once '../config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = sanitize($_POST['password'] ?? '');
    
    if ($username && $password) {
        $db = getDB();
        
        // Auto-Generate Parent Profile Dynamically on Request
        if (strpos($username, 'parent_') === 0) {
            $student_uname = substr($username, 7); // extract student username after 'parent_'
            $st_stmt = $db->prepare("SELECT student_id FROM students WHERE username = ?");
            $st_stmt->bind_param("s", $student_uname);
            $st_stmt->execute();
            $st = $st_stmt->get_result()->fetch_assoc();
            
            if ($st) {
                $student_id = $st['student_id'];
                
                // Verify if a parent profile already exists for this student
                $p_check = $db->prepare("SELECT parent_id FROM parent_profiles WHERE student_id = ?");
                $p_check->bind_param("i", $student_id);
                $p_check->execute();
                if ($p_check->get_result()->num_rows === 0) {
                    $default_pass = '123456'; // Default password for parent portal accounts
                    $ins = $db->prepare("INSERT INTO parent_profiles (student_id, username, password) VALUES (?, ?, ?)");
                    $ins->bind_param("iss", $student_id, $username, $default_pass);
                    $ins->execute();
                }
            }
        }
        
        $stmt = $db->prepare("SELECT * FROM parent_profiles WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $parent = $stmt->get_result()->fetch_assoc();
        
        // Using password verify or simple checks depending on migration parameters
        if ($parent && ($password === $parent['password'] || password_verify($password, $parent['password']))) {
            $_SESSION['user_id'] = $parent['parent_id'];
            $_SESSION['user_type'] = 'parent';
            $_SESSION['student_id'] = $parent['student_id'];
            $_SESSION['parent_id'] = $parent['parent_id'];
            redirect('dashboard.php');
        } else {
            $error = 'Invalid parent portal credentials.';
        }
    } else {
        $error = 'Please fill out all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body style="background: radial-gradient(circle at 50% 10%, rgba(79, 70, 229, 0.15), transparent 70%), var(--bg-body); min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div class="container py-5" style="max-width: 440px;">
        <div class="glass-card p-4">
            <div class="text-center mb-4">
                <div class="brand-icon mx-auto mb-3" style="background: linear-gradient(135deg, #4f46e5, #0ea5e9); color: white; width:54px; height:54px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.6rem; box-shadow: 0 4px 14px rgba(79,70,229,0.35);">
                    <i class="fas fa-user-friends"></i>
                </div>
                <h4 class="fw-bold text-main mb-1">Parent Portal</h4>
                <p class="text-muted small">Monitor child's academic attendance & risk forecasts</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger rounded-3 small py-2"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label font-semibold small">Parent Account Username</label>
                    <input type="text" class="form-control" name="username" required placeholder="Enter username...">
                </div>
                <div class="mb-3">
                    <label class="form-label font-semibold small">Password</label>
                    <input type="password" class="form-control" name="password" required placeholder="Enter password...">
                </div>
                <button type="submit" class="btn btn-primary-custom w-100 py-2.5 fw-bold mt-2">
                    <i class="fas fa-sign-in-alt me-1"></i> Access Portal
                </button>
            </form>
            <div class="text-center mt-4 small">
                <a href="../index.php" class="text-muted text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Return Home</a>
            </div>
        </div>
    </div>
</body>
</html>
