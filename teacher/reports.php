<?php
// teacher/reports.php - Attendance Reports
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    redirect('../index.php');
}

$teacher_id = $_SESSION['teacher_id'];
$db = getDB();

$session_id = intval($_GET['session_id'] ?? 0);
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// If session_id is provided, show specific session report
if ($session_id > 0) {
    $query = "SELECT sess.*, s.subject_name, t.full_name as teacher_name,
              COUNT(a.attendance_id) as total_present
              FROM attendance_sessions sess
              JOIN subjects s ON sess.subject_id = s.subject_id
              JOIN teachers t ON sess.teacher_id = t.teacher_id
              LEFT JOIN attendance a ON sess.session_id = a.session_id AND a.status = 'Present'
              WHERE sess.session_id = ? AND sess.teacher_id = ?
              GROUP BY sess.session_id";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $session_id, $teacher_id);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    
    if (!$session) {
        redirect('dashboard.php');
    }
    
    // Get attendance details for this session
    $attendance_query = "SELECT a.*, s.full_name as student_name, s.roll_number
                         FROM attendance a
                         JOIN students s ON a.student_id = s.student_id
                         WHERE a.session_id = ?
                         ORDER BY s.roll_number";
    $attendance_stmt = $db->prepare($attendance_query);
    $attendance_stmt->bind_param("i", $session_id);
    $attendance_stmt->execute();
    $attendance_list = $attendance_stmt->get_result();
    
    // Get total students
    $total_query = "SELECT COUNT(*) as total 
                    FROM students 
                    WHERE semester_id = ? AND batch_id = ? AND section_id = ?";
    $total_stmt = $db->prepare($total_query);
    $total_stmt->bind_param("iii", $session['semester_id'], $session['batch_id'], $session['section_id']);
    $total_stmt->execute();
    $total_students = $total_stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    $absent_count = max(0, $total_students - ($attendance_list ? $attendance_list->num_rows : 0));
} else {
    // Show summary report for date range
    $conditions = "sess.teacher_id = ?";
    $params = [$teacher_id];
    $types = "i";
    
    if ($date_from) {
        $conditions .= " AND sess.session_date >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    if ($date_to) {
        $conditions .= " AND sess.session_date <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    
    $query = "SELECT sess.*, s.subject_name,
              COUNT(a.attendance_id) as total_present,
              (SELECT COUNT(*) FROM students st 
               WHERE st.semester_id = sess.semester_id 
               AND st.batch_id = sess.batch_id 
               AND st.section_id = sess.section_id) as total_students
              FROM attendance_sessions sess
              JOIN subjects s ON sess.subject_id = s.subject_id
              LEFT JOIN attendance a ON sess.session_id = a.session_id AND a.status = 'Present'
              WHERE $conditions
              GROUP BY sess.session_id
              ORDER BY sess.session_date DESC, sess.start_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $sessions = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Reports - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 11pt !important;
            }
            .app-navbar, .btn, .btn-theme-toggle, [data-table-search], input, .ms-auto, .d-flex.gap-2 {
                display: none !important;
            }
            .container-fluid {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .custom-table-container {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
                background: #ffffff !important;
            }
            .custom-table th, .custom-table td {
                border: 1px solid #dee2e6 !important;
                padding: 8px !important;
                color: #000000 !important;
            }
            .stat-card {
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
                background: #f8f9fa !important;
                color: #000000 !important;
                margin-bottom: 10px !important;
            }
            .stat-icon {
                display: none !important;
            }
        }
    </style>
</head>
<body style="background: radial-gradient(circle at 50% 10%, rgba(16, 185, 129, 0.15), transparent 70%), var(--bg-body); min-height: 100vh;">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4" style="max-width: 1400px; margin: 0 auto;">
            <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="dashboard.php">
                <div class="brand-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(16,185,129,0.35);">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.95rem;">Faculty Reports <span class="badge bg-success-subtle text-success border border-success-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Faculty</span></span>
            </a>
            
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Light/Dark Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="dashboard.php" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4" style="max-width: 1300px;">
        <?php if ($session_id > 0 && isset($session)): ?>
            <!-- Session Detail View -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fas fa-file-alt text-success me-2"></i> Session Report: <?php echo htmlspecialchars($session['subject_name']); ?></h3>
                    <p class="text-muted small mb-0">Session Code #<?php echo str_pad($session['session_id'], 4, '0', STR_PAD_LEFT); ?> &bull; Date: <?php echo date('M d, Y', strtotime($session['session_date'])); ?></p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-print me-1"></i> Print Report
                    </button>
                    <a href="reports.php" class="btn btn-outline-primary rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Summary
                    </a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
                        <div class="stat-value"><?php echo $attendance_list ? $attendance_list->num_rows : 0; ?></div>
                        <div class="stat-label">Present Students</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon danger"><i class="fas fa-user-times"></i></div>
                        <div class="stat-value"><?php echo $absent_count; ?></div>
                        <div class="stat-label">Estimated Absent</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon info"><i class="fas fa-users"></i></div>
                        <div class="stat-value"><?php echo $total_students; ?></div>
                        <div class="stat-label">Total Section Enrolled</div>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="custom-table-container">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-3">
                    <span class="fw-bold"><i class="fas fa-list me-1 text-success"></i> Checked-In Students</span>
                    <div style="max-width: 280px;" class="w-100">
                        <input type="text" class="form-control form-control-sm" placeholder="Filter student name/roll..." data-table-search="sessionDetailTable">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table custom-table" id="sessionDetailTable">
                        <thead>
                            <tr>
                                <th>Roll Number</th>
                                <th>Student Name</th>
                                <th>Check-in Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($attendance_list && $attendance_list->num_rows > 0): ?>
                                <?php while ($row = $attendance_list->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace"><?php echo htmlspecialchars($row['roll_number']); ?></td>
                                        <td class="fw-bold text-main"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td class="text-muted"><i class="fas fa-clock me-1"></i> <?php echo date('h:i A', strtotime($row['attendance_time'])); ?></td>
                                        <td>
                                            <span class="badge badge-custom badge-present"><i class="fas fa-check"></i> Present</span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">No student check-ins recorded for this session.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <!-- All Sessions Overview -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fas fa-chart-line text-success me-2"></i> Attendance Summary Reports</h3>
                    <p class="text-muted small mb-0">Overview of all class sessions created and attendance logs.</p>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-print me-1"></i> Print Summary
                    </button>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="glass-card mb-4">
                <div class="glass-card-body p-3">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Date From</label>
                            <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Date To</label>
                            <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-success px-4 w-100 fw-bold">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                            <a href="reports.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Table -->
            <div class="custom-table-container">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-3">
                    <span class="fw-bold"><i class="fas fa-table me-1 text-success"></i> Sessions Overview</span>
                    <div style="max-width: 280px;" class="w-100">
                        <input type="text" class="form-control form-control-sm" placeholder="Search sessions..." data-table-search="reportsTable">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table custom-table" id="reportsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Session Code</th>
                                <th>Subject</th>
                                <th>Present Count</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($sessions) && $sessions->num_rows > 0): ?>
                                <?php while ($row = $sessions->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo date('M d, Y', strtotime($row['session_date'])); ?></td>
                                        <td class="font-monospace fw-bold text-primary">#<?php echo str_pad($row['session_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td class="fw-bold text-main"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                        <td>
                                            <span class="badge badge-custom badge-present">
                                                <i class="fas fa-users me-1"></i> <?php echo $row['total_present'] ?? 0; ?> Present
                                            </span>
                                        </td>
                                        <td>
                                            <a href="reports.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
                                                <i class="fas fa-eye me-1"></i> View Detailed Report
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No attendance sessions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>