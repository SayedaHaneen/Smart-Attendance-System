<?php
// teacher/dashboard.php - Teacher Portal Dashboard
require_once '../config.php';
require_once '../includes/ai_analytics.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    redirect('../index.php');
}

$teacher_id = $_SESSION['teacher_id'];
$db = getDB();

// Get teacher info
$query = "SELECT t.*, d.department_name 
          FROM teachers t
          LEFT JOIN departments d ON t.department_id = d.department_id
          WHERE t.teacher_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Get statistics
$stats_query = "SELECT 
                COUNT(DISTINCT sess.session_id) as total_sessions,
                COUNT(DISTINCT a.student_id) as total_students_marked,
                COUNT(a.attendance_id) as total_attendance
                FROM attendance_sessions sess
                LEFT JOIN attendance a ON sess.session_id = a.session_id
                WHERE sess.teacher_id = ?";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bind_param("i", $teacher_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get today's active sessions
$today = date('Y-m-d');
$active_sessions = "SELECT sess.*, s.subject_name, 
                    COUNT(DISTINCT a.student_id) as marked_students,
                    COUNT(DISTINCT st.student_id) as total_students
                    FROM attendance_sessions sess
                    JOIN subjects s ON sess.subject_id = s.subject_id
                    LEFT JOIN attendance a ON sess.session_id = a.session_id
                    LEFT JOIN students st ON st.semester_id = sess.semester_id 
                      AND st.batch_id = sess.batch_id 
                      AND st.section_id = sess.section_id
                    WHERE sess.teacher_id = ? AND sess.session_date = ? AND sess.is_active = 1
                    GROUP BY sess.session_id
                    ORDER BY sess.start_time ASC";
$active_stmt = $db->prepare($active_sessions);
$active_stmt->bind_param("is", $teacher_id, $today);
$active_stmt->execute();
$active_sessions_result = $active_stmt->get_result();

// Get recent sessions
$recent_sessions = "SELECT sess.*, s.subject_name, 
                    COUNT(a.attendance_id) as total_attendance
                    FROM attendance_sessions sess
                    JOIN subjects s ON sess.subject_id = s.subject_id
                    LEFT JOIN attendance a ON sess.session_id = a.session_id
                    WHERE sess.teacher_id = ?
                    GROUP BY sess.session_id
                    ORDER BY sess.session_date DESC, sess.start_time DESC
                    LIMIT 10";
$recent_stmt = $db->prepare($recent_sessions);
$recent_stmt->bind_param("i", $teacher_id);
$recent_stmt->execute();
$recent_sessions_result = $recent_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body style="--primary: var(--success); --primary-hover: #059669; --primary-light: var(--success-light); background: radial-gradient(circle at 50% 10%, rgba(16, 185, 129, 0.15), transparent 70%), var(--bg-body); min-height: 100vh;">
    <!-- Faculty Portal Top Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4" style="max-width: 1400px; margin: 0 auto;">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="dashboard.php">
                <div class="brand-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(16,185,129,0.35);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.95rem;"><?php echo APP_NAME; ?> <span class="badge bg-success-subtle text-success border border-success-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Faculty</span></span>
            </a>
            
            <!-- Mobile Toggle -->
            <div class="d-flex align-items-center gap-2 d-lg-none ms-auto">
                <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="navbar-toggler text-main border-0 p-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#teacherNavbarNav">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="collapse navbar-collapse" id="teacherNavbarNav">
                <ul class="navbar-nav me-auto ms-lg-3 gap-1 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap active fw-bold" href="dashboard.php">
                            <i class="fas fa-th-large me-1.5 text-success"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap" href="create_attendance.php">
                            <i class="fas fa-plus-circle me-1.5 text-primary"></i> New Session
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap" href="reports.php">
                            <i class="fas fa-chart-bar me-1.5 text-warning"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap" href="manage_leaves.php">
                            <i class="fas fa-file-signature me-1.5 text-info"></i> Manage Leaves
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2.5 ms-auto mt-2 mt-lg-0">
                    <div class="user-profile-badge d-flex align-items-center gap-1.5 px-3 py-1 rounded-pill" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.25); color: var(--text-main);">
                        <i class="fas fa-user-circle text-success small"></i>
                        <span class="small font-semibold text-nowrap" style="font-size:0.8rem;"><?php echo htmlspecialchars($teacher['full_name']); ?></span>
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
        <!-- Banner Header -->
        <div class="glass-card mb-4 overflow-hidden" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(14, 165, 233, 0.05)); border: 1px solid var(--border-color); border-radius: 20px;">
            <div class="glass-card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge badge-custom badge-present mb-2">
                        <i class="fas fa-building me-1"></i> Department: <?php echo htmlspecialchars($teacher['department_name'] ?? 'Faculty'); ?>
                    </span>
                    <h2 class="fw-bold mb-1">Welcome, Prof. <?php echo htmlspecialchars($teacher['full_name']); ?> 👨‍🏫</h2>
                    <p class="text-muted mb-0">Create new attendance sessions, broadcast dynamic QR codes, and manage class check-ins.</p>
                </div>
                <div>
                    <a href="create_attendance.php" class="btn btn-success rounded-pill px-4 py-2.5 fw-bold shadow-sm" style="background: linear-gradient(135deg, #10b981, #059669); border:none;">
                        <i class="fas fa-plus-circle me-2"></i> Start New Class Session
                    </a>
                </div>
            </div>
        </div>

        <!-- Metrics Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_sessions'] ?? 0; ?></div>
                    <div class="stat-label">Total Sessions Created</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_attendance'] ?? 0; ?></div>
                    <div class="stat-label">Total Student Attendance Records</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_students_marked'] ?? 0; ?></div>
                    <div class="stat-label">Unique Students Engaged</div>
                </div>
            </div>
        </div>

        <!-- Active Sessions Section -->
        <?php if ($active_sessions_result && $active_sessions_result->num_rows > 0): ?>
            <div class="glass-card mb-4 border-success">
                <div class="glass-card-header bg-success text-white">
                    <span><i class="fas fa-broadcast-tower me-2"></i> Active Live Class Sessions Today</span>
                    <span class="badge bg-white text-success fw-bold">LIVE BROADCAST</span>
                </div>
                <div class="glass-card-body p-0">
                    <div class="table-responsive">
                        <table class="table custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>4-Digit Code</th>
                                    <th>Date & Time</th>
                                    <th>Live Marked</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($active = $active_sessions_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold text-main">
                                            <?php echo htmlspecialchars($active['subject_name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark fs-6 px-3 py-1 font-monospace">
                                                <?php echo str_pad($active['session_id'], 4, '0', STR_PAD_LEFT); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo date('M d, Y', strtotime($active['session_date'])); ?> &bull; <?php echo date('h:i A', strtotime($active['start_time'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-custom badge-present">
                                                <i class="fas fa-users me-1"></i> <?php echo $active['marked_students']; ?> Students Checked-In
                                            </span>
                                        </td>
                                        <td>
                                            <a href="generate_qr.php?session_id=<?php echo $active['session_id']; ?>" class="btn btn-success btn-sm rounded-pill px-3">
                                                <i class="fas fa-qrcode me-1"></i> Launch Projector QR
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Sessions Table -->
        <div class="custom-table-container">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-3">
                <span class="fw-bold"><i class="fas fa-history me-1 text-primary"></i> Recent Class Sessions History</span>
                <div style="max-width: 280px;" class="w-100">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Search sessions..." data-table-search="recentSessionsTable">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table" id="recentSessionsTable">
                    <thead>
                        <tr>
                            <th>Session Code</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>Check-in Count</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_sessions_result && $recent_sessions_result->num_rows > 0): ?>
                            <?php while ($row = $recent_sessions_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="font-monospace fw-bold text-primary">
                                        #<?php echo str_pad($row['session_id'], 4, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td class="fw-bold text-main"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['session_date'])); ?></td>
                                    <td class="text-muted"><?php echo date('h:i A', strtotime($row['start_time'])); ?></td>
                                    <td>
                                        <span class="badge badge-custom badge-present">
                                            <?php echo $row['total_attendance']; ?> Present
                                        </span>
                                    </td>
                                    <td>
                                        <a href="generate_qr.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                            <i class="fas fa-external-link-alt me-1"></i> View QR Code
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-times fa-3x mb-3 d-block opacity-50"></i>
                                    No past sessions created yet. Click "Start New Class Session" to begin.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <!-- AI Classroom Defaulter Risk Watchlist -->
        <div class="custom-table-container mt-4 mb-4">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-main"><i class="fas fa-brain text-success me-2"></i> AI Classroom Defaulter Watchlist (Risk Forecast)</span>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 font-semibold">Defaulters Risk > 40%</span>
            </div>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Roll Number</th>
                            <th>Subject Enrolled</th>
                            <th>Risk Score</th>
                            <th>System Flags</th>
                            <th>Intervention Recommendation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch students in this teacher's department or courses
                        $watch_query = "SELECT s.student_id, s.full_name, s.roll_number, sub.subject_name, sub.subject_id 
                                        FROM students s
                                        JOIN subjects sub ON s.department_id = sub.department_id AND s.semester_id = sub.semester_id
                                        WHERE s.department_id = ? AND s.is_approved = 1
                                        ORDER BY s.full_name ASC";
                        $watch_stmt = $db->prepare($watch_query);
                        $watch_stmt->bind_param("i", $teacher['department_id']);
                        $watch_stmt->execute();
                        $watchlist = $watch_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        
                        $defaulters_found = 0;
                        foreach ($watchlist as $w) {
                            $pred = predictStudentAttendance($w['student_id'], $w['subject_id']);
                            if ($pred['risk_score'] >= 40) {
                                $defaulters_found++;
                                $risk_color = 'text-warning';
                                if ($pred['risk_score'] >= 70) $risk_color = 'text-danger';
                                
                                echo "<tr>";
                                echo "<td class='fw-bold text-main'>" . htmlspecialchars($w['full_name']) . "</td>";
                                echo "<td class='font-monospace'>" . htmlspecialchars($w['roll_number']) . "</td>";
                                echo "<td>" . htmlspecialchars($w['subject_name']) . "</td>";
                                echo "<td class='fw-extrabold {$risk_color}'>" . $pred['risk_score'] . "%</td>";
                                echo "<td class='small text-muted'>" . htmlspecialchars($pred['reason']) . "</td>";
                                echo "<td class='small text-main font-medium'><i class='fas fa-exclamation-circle text-danger me-1'></i> " . htmlspecialchars($pred['recommendation']) . "</td>";
                                echo "</tr>";
                            }
                        }
                        
                        if ($defaulters_found === 0) {
                            echo "<tr><td colspan='6' class='text-center py-4 text-muted'><i class='fas fa-check-double text-success me-1'></i> Excellent! All classroom student attendance metrics are healthy.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <?php include '../includes/ai_chatbot.php'; ?>
</body>
</html>