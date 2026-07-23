<?php
// admin/dashboard.php - Interactive Admin Operations Dashboard
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();

// Handle quick approve/reject from dashboard
if (isset($_GET['action']) && isset($_GET['student_id'])) {
    $st_id = intval($_GET['student_id']);
    $act = $_GET['action'];
    if ($act === 'approve') {
        $up = $db->prepare("UPDATE students SET is_approved = 1, approved_at = NOW() WHERE student_id = ?");
        $up->bind_param("i", $st_id);
        $up->execute();
    } elseif ($act === 'reject') {
        $del = $db->prepare("DELETE FROM students WHERE student_id = ?");
        $del->bind_param("i", $st_id);
        $del->execute();
    }
    header("Location: dashboard.php");
    exit;
}

// Get statistics
$stats = [
    'students' => getStudentCount(),
    'teachers' => getTeacherCount(),
    'today_attendance' => getTodayAttendance(),
    'pending_approvals' => 0
];

$pending_res = $db->query("SELECT COUNT(*) as cnt FROM students WHERE is_approved = 0");
if ($pending_res) {
    $stats['pending_approvals'] = $pending_res->fetch_assoc()['cnt'];
}

// Fetch Pending Students List (Max 5 for Dashboard Widget)
$pending_students = $db->query("SELECT s.*, d.department_name, b.batch_year, sem.semester_name, sec.section_name 
                                FROM students s
                                LEFT JOIN departments d ON s.department_id = d.department_id
                                LEFT JOIN batches b ON s.batch_id = b.batch_id
                                LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
                                LEFT JOIN sections sec ON s.section_id = sec.section_id
                                WHERE s.is_approved = 0
                                ORDER BY s.created_at DESC LIMIT 5");

// Fetch Popular Departments for Quick Cohort Shortcuts
$popular_depts = $db->query("SELECT d.department_id, d.department_name, COUNT(s.student_id) as student_count 
                              FROM departments d 
                              LEFT JOIN students s ON d.department_id = s.department_id 
                              GROUP BY d.department_id 
                              ORDER BY student_count DESC LIMIT 4");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css?v=2" rel="stylesheet">
</head>
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="container-fluid px-3 px-md-4 py-4 animate-slide-up" style="max-width: 1400px; margin: 0 auto;">
        <!-- Banner Header -->
        <div class="glass-card mb-4 overflow-hidden" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(79, 70, 229, 0.08));">
            <div class="glass-card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge badge-custom badge-pending mb-2">
                        <i class="fas fa-cogs me-1"></i> Operations Command Center
                    </span>
                    <h2 class="fw-bold mb-1 text-main">Administrator Control Panel 🛡️</h2>
                    <p class="text-muted mb-0">Manage single-device locks, review approval queues, and access structured cohort student directories.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="approve_students.php" class="btn btn-danger rounded-pill px-4 py-2 fw-bold shadow-sm">
                        <i class="fas fa-user-check me-2"></i> Review Approvals (<?php echo $stats['pending_approvals']; ?>)
                    </a>
                </div>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card interactive-hover-card">
                    <div class="stat-icon primary"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-value"><?php echo $stats['students']; ?></div>
                    <div class="stat-label">Total Enrolled Students</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card interactive-hover-card">
                    <div class="stat-icon success"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-value"><?php echo $stats['teachers']; ?></div>
                    <div class="stat-label">Faculty Members</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card interactive-hover-card">
                    <div class="stat-icon info"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-value"><?php echo $stats['today_attendance']; ?></div>
                    <div class="stat-label">Today's Check-ins</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card interactive-hover-card">
                    <div class="stat-icon warning"><i class="fas fa-user-clock"></i></div>
                    <div class="stat-value"><?php echo $stats['pending_approvals']; ?></div>
                    <div class="stat-label">Pending Approval Queue</div>
                </div>
            </div>
        </div>

        <!-- Structured Cohort Shortcuts Grid -->
        <div class="glass-card mb-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-main"><i class="fas fa-filter text-primary me-2"></i> Quick Department Cohorts</h5>
                <a href="manage_students.php" class="btn btn-outline-primary btn-sm rounded-pill">View Full Directory <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <div class="row g-3">
                <?php if ($popular_depts && $popular_depts->num_rows > 0): ?>
                    <?php while ($pd = $popular_depts->fetch_assoc()): ?>
                        <div class="col-md-3 col-sm-6">
                            <a href="manage_students.php?dept_id=<?php echo $pd['department_id']; ?>" class="text-decoration-none">
                                <div class="glass-card p-3 text-center interactive-hover-card h-100 border-start border-4 border-primary">
                                    <div class="fw-bold text-main mb-1"><?php echo htmlspecialchars($pd['department_name']); ?></div>
                                    <span class="cohort-pill-badge">
                                        <i class="fas fa-users me-1"></i> <?php echo $pd['student_count']; ?> Enrolled
                                    </span>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-building me-2"></i> No departments available yet.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Pending Registrations Widget -->
            <div class="col-lg-7">
                <div class="custom-table-container h-100">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-clock text-warning me-2"></i> Pending Student Approvals</span>
                        <a href="approve_students.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold">View Queue</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>Roll Number</th>
                                    <th>Student Name</th>
                                    <th>Department</th>
                                    <th>Batch / Sem</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pending_students && $pending_students->num_rows > 0): ?>
                                    <?php while ($ps = $pending_students->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace text-primary"><?php echo htmlspecialchars($ps['roll_number']); ?></td>
                                            <td class="fw-bold text-main"><?php echo htmlspecialchars($ps['full_name']); ?></td>
                                            <td><span class="badge badge-custom badge-pending"><?php echo htmlspecialchars($ps['department_name'] ?? 'SE'); ?></span></td>
                                            <td><span class="small text-muted">Batch <?php echo htmlspecialchars($ps['batch_year'] ?? '2024'); ?> / Sem <?php echo htmlspecialchars($ps['semester_name'] ?? '-'); ?></span></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="dashboard.php?action=approve&student_id=<?php echo $ps['student_id']; ?>" class="btn btn-success btn-sm rounded-pill px-2">
                                                        <i class="fas fa-check me-1"></i> Approve
                                                    </a>
                                                    <a href="dashboard.php?action=reject&student_id=<?php echo $ps['student_id']; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-2" onclick="return confirm('Reject student?')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fas fa-check-double text-success me-2"></i> No pending student approvals! All caught up.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Operations Hub -->
            <div class="col-lg-5">
                <div class="glass-card h-100 p-4 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-3 text-main"><i class="fas fa-cogs text-danger me-2"></i> Administrative Operations</h5>
                        <div class="d-flex flex-column gap-2">
                            <a href="manage_students.php" class="btn btn-outline-primary text-start p-3 rounded-4 interactive-hover-card">
                                <div class="fw-bold"><i class="fas fa-users-cog me-2"></i> Cohort Student Directory</div>
                                <small class="text-muted">Filter by Department, Batch 2024, Semester 7, Section B & manage device locks.</small>
                            </a>
                            <a href="manage_teachers.php" class="btn btn-outline-success text-start p-3 rounded-4 interactive-hover-card">
                                <div class="fw-bold"><i class="fas fa-chalkboard-teacher me-2"></i> Faculty Management Hub</div>
                                <small class="text-muted">Register professors, assign departments, and manage faculty credentials.</small>
                            </a>
                            <a href="manage_courses.php" class="btn btn-outline-danger text-start p-3 rounded-4 interactive-hover-card">
                                <div class="fw-bold"><i class="fas fa-book me-2"></i> Academic Curriculum & Courses</div>
                                <small class="text-muted">Manage 300+ university subjects across 8 semesters and 7 departments.</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
