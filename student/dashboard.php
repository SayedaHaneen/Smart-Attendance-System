<?php
// student/dashboard.php - Student Portal Dashboard
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
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    redirect('login.php');
}

$student = $result->fetch_assoc();

// Get today's attendance
$today = date('Y-m-d');
$query = "SELECT a.*, sess.*, s.subject_name 
          FROM attendance a
          JOIN attendance_sessions sess ON a.session_id = sess.session_id
          JOIN subjects s ON sess.subject_id = s.subject_id
          WHERE a.student_id = ? AND a.attendance_date = ? 
          ORDER BY a.attendance_time DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bind_param("is", $student_id, $today);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc();

// Get attendance statistics
$query = "SELECT 
          COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
          COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent,
          COUNT(CASE WHEN status = 'Late' THEN 1 END) as late,
          COUNT(*) as total
          FROM attendance WHERE student_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

if (!$stats) {
    $stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
}

$total_sessions = $stats['total'] > 0 ? $stats['total'] : 1;
$attendance_rate = round(($stats['present'] / $total_sessions) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body style="background: radial-gradient(circle at 50% 10%, rgba(79, 70, 229, 0.15), transparent 70%), var(--bg-body); min-height: 100vh;">
    <!-- Student Portal Top Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4" style="max-width: 1400px; margin: 0 auto;">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="dashboard.php">
                <div class="brand-icon" style="background: linear-gradient(135deg, #4f46e5, #0ea5e9); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(79,70,229,0.35);">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.95rem;"><?php echo APP_NAME; ?> <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Student</span></span>
            </a>
            
            <!-- Mobile Toggle -->
            <div class="d-flex align-items-center gap-2 d-lg-none ms-auto">
                <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="navbar-toggler text-main border-0 p-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#studentNavbarNav">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="collapse navbar-collapse" id="studentNavbarNav">
                <ul class="navbar-nav me-auto ms-lg-3 gap-1 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap active fw-bold" href="dashboard.php">
                            <i class="fas fa-th-large me-1.5 text-primary"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap" href="scan_qr.php">
                            <i class="fas fa-qrcode me-1.5 text-success"></i> Scan QR
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap" href="manual_entry.php">
                            <i class="fas fa-keyboard me-1.5 text-info"></i> Enter Code
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap" href="history.php">
                            <i class="fas fa-history me-1.5 text-warning"></i> History
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2.5 ms-auto mt-2 mt-lg-0">
                    <div class="user-profile-badge d-none d-xl-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill" style="background: rgba(79, 70, 229, 0.1); border: 1px solid rgba(79, 70, 229, 0.2); color: var(--text-main);">
                        <i class="fas fa-user-circle text-primary small"></i>
                        <span class="small font-semibold text-nowrap" style="font-size:0.75rem;"><?php echo htmlspecialchars($student['full_name']); ?></span>
                    </div>

                    <button class="btn-theme-toggle d-none d-lg-inline-flex" onclick="toggleAppTheme()" title="Toggle Light/Dark Theme">
                        <i class="fas fa-moon"></i>
                    </button>

                    <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap" style="font-size: 0.8rem;">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4" style="max-width: 1300px;">
        <!-- Banner / Welcome -->
        <div class="glass-card mb-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(14, 165, 233, 0.05));">
            <div class="glass-card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge badge-custom badge-pending mb-2">
                        <i class="fas fa-shield-alt"></i> Bound Device: <?php echo substr($_SESSION['device_id'] ?? 'Unknown', 0, 12); ?>...
                    </span>
                    <h2 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($student['full_name']); ?> 👋</h2>
                    <p class="text-muted mb-0">
                        <?php echo htmlspecialchars($student['roll_number']); ?> &bull; <?php echo htmlspecialchars($student['department_name'] ?? 'Department'); ?> &bull; <?php echo htmlspecialchars($student['semester_name'] ?? ''); ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="scan_qr.php" class="btn btn-primary-custom px-4 py-2">
                        <i class="fas fa-camera me-2"></i> Scan Attendance QR
                    </a>
                    <a href="manual_entry.php" class="btn btn-outline-primary rounded-3 px-3 py-2 fw-semibold">
                        <i class="fas fa-key me-1"></i> 4-Digit Code
                    </a>
                </div>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value"><?php echo $attendance_rate; ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['present']; ?></div>
                    <div class="stat-label">Total Present</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['absent']; ?></div>
                    <div class="stat-label">Total Absent</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['late']; ?></div>
                    <div class="stat-label">Total Late</div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row g-4">
            <!-- Today's Status -->
            <div class="col-lg-6">
                <div class="glass-card h-100">
                    <div class="glass-card-header">
                        <span><i class="fas fa-calendar-day me-2 text-primary"></i> Today's Attendance</span>
                        <span class="small text-muted"><?php echo date('M d, Y'); ?></span>
                    </div>
                    <div class="glass-card-body d-flex flex-column justify-content-center">
                        <?php if ($today_attendance): ?>
                            <div class="p-4 rounded-4 text-center" style="background: var(--success-light); border: 1px solid var(--success);">
                                <div class="fs-1 text-success mb-2"><i class="fas fa-check-circle"></i></div>
                                <h4 class="fw-bold text-success mb-1">Marked Present!</h4>
                                <p class="mb-1 text-main font-medium">Subject: <strong><?php echo htmlspecialchars($today_attendance['subject_name']); ?></strong></p>
                                <span class="badge badge-custom badge-present">
                                    <i class="fas fa-clock"></i> Marked at <?php echo date('h:i A', strtotime($today_attendance['attendance_time'])); ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="p-4 rounded-4 text-center" style="background: var(--warning-light); border: 1px solid var(--warning);">
                                <div class="fs-1 text-warning mb-2"><i class="fas fa-exclamation-triangle"></i></div>
                                <h5 class="fw-bold text-warning mb-1">No Attendance Marked Today</h5>
                                <p class="text-muted small mb-3">If a class session is active, scan the teacher's QR code or enter the 4-digit code.</p>
                                <a href="scan_qr.php" class="btn btn-primary-custom px-4">
                                    <i class="fas fa-qrcode me-2"></i> Launch Scanner Now
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Profile Overview -->
            <div class="col-lg-6">
                <div class="glass-card h-100">
                    <div class="glass-card-header">
                        <span><i class="fas fa-user-shield me-2 text-primary"></i> Academic Profile Details</span>
                        <a href="profile.php" class="btn btn-sm btn-outline-primary rounded-pill">Manage Profile</a>
                    </div>
                    <div class="glass-card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3" style="background: var(--bg-body);">
                                    <div class="text-muted small fw-bold uppercase">Roll Number</div>
                                    <div class="fw-bold fs-6 text-main"><?php echo htmlspecialchars($student['roll_number']); ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3" style="background: var(--bg-body);">
                                    <div class="text-muted small fw-bold uppercase">Department</div>
                                    <div class="fw-bold fs-6 text-main"><?php echo htmlspecialchars($student['department_name'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3" style="background: var(--bg-body);">
                                    <div class="text-muted small fw-bold uppercase">Semester & Section</div>
                                    <div class="fw-bold fs-6 text-main"><?php echo htmlspecialchars(($student['semester_name'] ?? '') . ' - ' . ($student['section_name'] ?? '')); ?></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3" style="background: var(--bg-body);">
                                    <div class="text-muted small fw-bold uppercase">Batch Year</div>
                                    <div class="fw-bold fs-6 text-main"><?php echo htmlspecialchars($student['batch_year'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>