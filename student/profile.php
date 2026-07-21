<?php
// student/profile.php - Student Profile
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    redirect('../index.php');
}

$student_id = $_SESSION['student_id'];
$db = getDB();

// Get student info
$query = "SELECT s.*, d.department_name, b.batch_year, sem.semester_name, sec.section_name 
          FROM students s
          LEFT JOIN departments d ON s.department_id = d.department_id
          LEFT JOIN batches b ON s.batch_id = b.batch_id
          LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
          LEFT JOIN sections sec ON s.section_id = sec.section_id
          WHERE s.student_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get device info
$device_query = "SELECT device_identifier, device_name, last_used, registered_at 
                 FROM student_devices WHERE student_id = ?";
$device_stmt = $db->prepare($device_query);
$device_stmt->bind_param("i", $student_id);
$device_stmt->execute();
$device = $device_stmt->get_result()->fetch_assoc();

// Update profile
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = sanitize($_POST['phone'] ?? '');
    
    $update_query = "UPDATE students SET phone = ? WHERE student_id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bind_param("si", $phone, $student_id);
    
    if ($update_stmt->execute()) {
        $message = 'Profile updated successfully!';
        $message_type = 'success';
        // Refresh data
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
    } else {
        $message = 'Failed to update profile.';
        $message_type = 'danger';
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $pass_query = "SELECT password FROM students WHERE student_id = ?";
    $pass_stmt = $db->prepare($pass_query);
    $pass_stmt->bind_param("i", $student_id);
    $pass_stmt->execute();
    $user_data = $pass_stmt->get_result()->fetch_assoc();
    
    if ($current_password !== $user_data['password']) {
        $message = 'Current password is incorrect.';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'New passwords do not match.';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'New password must be at least 6 characters.';
        $message_type = 'danger';
    } else {
        $update_query = "UPDATE students SET password = ? WHERE student_id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bind_param("si", $new_password, $student_id);
        
        if ($update_stmt->execute()) {
            $message = 'Password changed successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to change password.';
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="dashboard.php">
                <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
                <span><?php echo APP_NAME; ?></span>
            </a>
            
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4" style="max-width: 1100px;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Student Bio Card -->
            <div class="col-lg-4">
                <div class="glass-card text-center p-4 h-100">
                    <div class="brand-icon mx-auto mb-3" style="width:72px; height:72px; font-size:2.2rem; background: var(--primary-light); color: var(--primary);">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($student['full_name']); ?></h4>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($student['roll_number']); ?></p>

                    <div class="badge badge-custom badge-present mb-4">
                        <i class="fas fa-user-check"></i> Active Student
                    </div>

                    <div class="text-start rounded-4 p-3 mb-3" style="background: var(--bg-body);">
                        <div class="mb-2"><strong class="text-muted small uppercase">Department:</strong> <div class="fw-semibold"><?php echo htmlspecialchars($student['department_name'] ?? 'N/A'); ?></div></div>
                        <div class="mb-2"><strong class="text-muted small uppercase">Batch & Semester:</strong> <div class="fw-semibold"><?php echo htmlspecialchars(($student['batch_year'] ?? '') . ' • ' . ($student['semester_name'] ?? '')); ?></div></div>
                        <div><strong class="text-muted small uppercase">Section:</strong> <div class="fw-semibold"><?php echo htmlspecialchars($student['section_name'] ?? 'N/A'); ?></div></div>
                    </div>

                    <!-- Device Lock Panel -->
                    <div class="p-3 rounded-4 border text-start" style="background: var(--info-light); border-color: var(--info) !important;">
                        <div class="fw-bold text-info mb-1"><i class="fas fa-mobile-alt me-1"></i> Bound Single Device</div>
                        <div class="small text-muted font-monospace text-break mb-1">ID: <?php echo htmlspecialchars($device['device_identifier'] ?? $_SESSION['device_id'] ?? 'Not bound'); ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Only this registered hardware device can mark attendance for your account.</div>
                    </div>
                </div>
            </div>

            <!-- Profile Settings -->
            <div class="col-lg-8">
                <!-- Update Phone Number -->
                <div class="glass-card mb-4">
                    <div class="glass-card-header">
                        <span><i class="fas fa-user-edit me-2 text-primary"></i> Personal Details</span>
                    </div>
                    <div class="glass-card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label font-semibold">Full Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-semibold">Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-semibold">Roll Number</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['roll_number']); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label font-semibold">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" placeholder="03001234567">
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" name="update_profile" class="btn btn-primary-custom px-4">
                                    <i class="fas fa-save me-1"></i> Save Contact Details
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Card -->
                <div class="glass-card">
                    <div class="glass-card-header">
                        <span><i class="fas fa-lock me-2 text-primary"></i> Change Security Password</span>
                    </div>
                    <div class="glass-card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label font-semibold">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label font-semibold">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label font-semibold">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-outline-primary rounded-3 px-4 fw-bold">
                                <i class="fas fa-key me-1"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>