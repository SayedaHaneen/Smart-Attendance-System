<?php
// student/history.php - Attendance History
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    redirect('../index.php');
}

$student_id = $_SESSION['student_id'];
$db = getDB();

// Get filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT a.*, s.subject_name, t.full_name as teacher_name, 
          sess.session_date, sess.start_time 
          FROM attendance a
          JOIN attendance_sessions sess ON a.session_id = sess.session_id
          JOIN subjects s ON sess.subject_id = s.subject_id
          JOIN teachers t ON sess.teacher_id = t.teacher_id
          WHERE a.student_id = ?";

$count_query = "SELECT COUNT(*) as total FROM attendance a
                JOIN attendance_sessions sess ON a.session_id = sess.session_id
                WHERE a.student_id = ?";

$params = [$student_id];
$count_params = [$student_id];

if ($status_filter) {
    $query .= " AND a.status = ?";
    $count_query .= " AND a.status = ?";
    $params[] = $status_filter;
    $count_params[] = $status_filter;
}

if ($date_from) {
    $query .= " AND a.attendance_date >= ?";
    $count_query .= " AND a.attendance_date >= ?";
    $params[] = $date_from;
    $count_params[] = $date_from;
}

if ($date_to) {
    $query .= " AND a.attendance_date <= ?";
    $count_query .= " AND a.attendance_date <= ?";
    $params[] = $date_to;
    $count_params[] = $date_to;
}

$query .= " ORDER BY a.attendance_date DESC, a.attendance_time DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Get total count for pagination
$count_stmt = $db->prepare($count_query);
$count_stmt->bind_param(str_repeat('s', count($count_params)), ...$count_params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get attendance records
$stmt = $db->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$attendance_records = $stmt->get_result();

// Get summary statistics
$summary_query = "SELECT 
                  COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
                  COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent,
                  COUNT(CASE WHEN status = 'Late' THEN 1 END) as late,
                  COUNT(*) as total
                  FROM attendance WHERE student_id = ?";
$summary_stmt = $db->prepare($summary_query);
$summary_stmt->bind_param("i", $student_id);
$summary_stmt->execute();
$stats = $summary_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History - <?php echo APP_NAME; ?></title>
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
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap" href="dashboard.php">
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
                        <a class="nav-link px-3 py-1.5 rounded-pill text-nowrap active fw-bold" href="history.php">
                            <i class="fas fa-history me-1.5 text-warning"></i> History
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2.5 ms-auto mt-2 mt-lg-0">
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
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="fas fa-history text-primary me-2"></i> Attendance Log & History</h3>
                <p class="text-muted small mb-0">View all past attendance records and search log history.</p>
            </div>
            <div>
                <a href="scan_qr.php" class="btn btn-primary-custom">
                    <i class="fas fa-qrcode me-2"></i> New Scan
                </a>
            </div>
        </div>

        <!-- Summary Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?php echo $stats['present'] ?? 0; ?></div>
                    <div class="stat-label">Present</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?php echo $stats['absent'] ?? 0; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                    <div class="stat-value"><?php echo $stats['late'] ?? 0; ?></div>
                    <div class="stat-label">Late</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Sessions</div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="glass-card mb-4">
            <div class="glass-card-body p-3">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="Present" <?php echo $status_filter === 'Present' ? 'selected' : ''; ?>>Present</option>
                            <option value="Absent" <?php echo $status_filter === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                            <option value="Late" <?php echo $status_filter === 'Late' ? 'selected' : ''; ?>>Late</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Date To</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-filter me-1"></i> Apply Filter
                        </button>
                        <a href="history.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Live Instant Search & Table Container -->
        <div class="custom-table-container">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-3">
                <span class="fw-bold"><i class="fas fa-list me-1 text-primary"></i> Attendance Records (<?php echo $total_records; ?>)</span>
                <div style="max-width: 280px;" class="w-100">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Instant search rows..." data-table-search="historyTable">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table" id="historyTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($attendance_records && $attendance_records->num_rows > 0): ?>
                            <?php while ($row = $attendance_records->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                                    <td class="text-muted"><i class="fas fa-clock me-1"></i> <?php echo date('h:i A', strtotime($row['attendance_time'])); ?></td>
                                    <td class="fw-bold text-main"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'Present'): ?>
                                            <span class="badge badge-custom badge-present"><i class="fas fa-check"></i> Present</span>
                                        <?php elseif ($row['status'] === 'Late'): ?>
                                            <span class="badge badge-custom badge-late"><i class="fas fa-clock"></i> Late</span>
                                        <?php else: ?>
                                            <span class="badge badge-custom badge-absent"><i class="fas fa-times"></i> Absent</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-50"></i>
                                    No attendance records found matching criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="p-3 border-top d-flex justify-content-center">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>