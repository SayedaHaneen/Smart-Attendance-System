<?php
// parent/dashboard.php - Parent Portal Dashboard
require_once '../config.php';
require_once '../includes/ai_analytics.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    redirect('login.php');
}

$student_id = $_SESSION['student_id'];
$db = getDB();

// Get student details
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

// Calculate metrics
$stats_stmt = $db->prepare("SELECT 
              COUNT(CASE WHEN status = 'Present' THEN 1 END) as present,
              COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent,
              COUNT(CASE WHEN status = 'Late' THEN 1 END) as late,
              COUNT(*) as total
              FROM attendance WHERE student_id = ?");
$stats_stmt->bind_param("i", $student_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$total = $stats['total'] > 0 ? $stats['total'] : 1;
$rate = round(($stats['present'] / $total) * 100);

$dropout = predictDropoutRisk($student_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body style="background: radial-gradient(circle at 50% 10%, rgba(79, 70, 229, 0.15), transparent 70%), var(--bg-body); min-height: 100vh;">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4" style="max-width: 1400px; margin: 0 auto;">
            <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="dashboard.php">
                <div class="brand-icon" style="background: linear-gradient(135deg, #4f46e5, #0ea5e9); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(79,70,229,0.35);">
                    <i class="fas fa-user-friends"></i>
                </div>
                <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.95rem;">Parent Portal <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Dashboard</span></span>
            </a>
            
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Light/Dark Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4" style="max-width: 1200px; margin: 0 auto;">
        <!-- Welcome Banner -->
        <div class="glass-card mb-4 p-4">
            <h3 class="fw-bold text-main">Parent Oversight Panel 👨‍👩‍👦</h3>
            <p class="text-muted mb-0">
                Enrolled Ward: <strong><?php echo htmlspecialchars($student['full_name']); ?></strong> (<?php echo htmlspecialchars($student['roll_number']); ?>) &bull; <?php echo htmlspecialchars($student['department_name']); ?>
            </p>
        </div>

        <!-- Metrics Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fas fa-chart-bar"></i></div>
                    <div class="stat-value"><?php echo $rate; ?>%</div>
                    <div class="stat-label">Cumulative Attendance Rate</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?php echo $stats['present']; ?></div>
                    <div class="stat-label">Classes Attended</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?php echo $stats['absent']; ?></div>
                    <div class="stat-label">Classes Missed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fas fa-brain"></i></div>
                    <div class="stat-value"><?php echo $dropout['dropout_risk']; ?>%</div>
                    <div class="stat-label">AI Calculated Dropout Risk</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left: Course Details & Recommendations -->
            <div class="col-lg-8">
                <div class="glass-card p-4">
                    <h5 class="fw-bold text-main mb-3"><i class="fas fa-book-reader text-primary me-2"></i> Subject-wise Standing & Forecasts</h5>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Risk Level</th>
                                    <th>Recommendation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sub_query = "SELECT * FROM subjects WHERE department_id = ? AND semester_id = ?";
                                $sub_stmt = $db->prepare($sub_query);
                                $sub_stmt->bind_param("ii", $student['department_id'], $student['semester_id']);
                                $sub_stmt->execute();
                                $subjects = $sub_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                
                                foreach ($subjects as $sub) {
                                    $pred = predictStudentAttendance($student_id, $sub['subject_id']);
                                    $risk = $pred['risk_score'];
                                    $risk_label = 'Low Risk';
                                    $risk_badge = 'bg-success-subtle text-success border border-success-subtle';
                                    if ($risk >= 70) {
                                        $risk_label = 'Critical Defaulter';
                                        $risk_badge = 'bg-danger-subtle text-danger border border-danger-subtle';
                                    } elseif ($risk >= 40) {
                                        $risk_label = 'Shortage Warning';
                                        $risk_badge = 'bg-warning-subtle text-warning border border-warning-subtle';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td class='fw-bold text-main'>" . htmlspecialchars($sub['subject_name']) . "</td>";
                                    echo "<td><span class='badge {$risk_badge}'>{$risk_label} ({$risk}%)</span></td>";
                                    echo "<td class='small text-muted'><i class='fas fa-info-circle text-primary me-1'></i> " . htmlspecialchars($pred['recommendation']) . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Support & Alerts -->
            <div class="col-lg-4">
                <div class="glass-card p-4">
                    <h5 class="fw-bold text-main mb-3"><i class="fas fa-bell text-danger me-2"></i> Security & Alerts</h5>
                    <div class="alert alert-info py-2.5 rounded-3 mb-3">
                        <small>Parent alert thresholds are configured. SMS notifications will be triggered automatically if cumulative check-in rates drop below 75%.</small>
                    </div>
                    
                    <?php if ($dropout['dropout_risk'] >= 45): ?>
                        <div class="alert alert-danger py-2.5 rounded-3 d-flex align-items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong class="small d-block">Urgent Intervention Recommended</strong>
                                <small>Your ward's attendance metrics require immediate academic review.</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
