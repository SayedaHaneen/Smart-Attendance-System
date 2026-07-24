<?php
// teacher/manage_leaves.php - Manage Student Leave Applications
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    redirect('../index.php');
}

$teacher_id = $_SESSION['teacher_id'];
$db = getDB();
$message = '';
$msg_type = 'info';

// Get teacher details
$t_stmt = $db->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$t_stmt->bind_param("i", $teacher_id);
$t_stmt->execute();
$teacher = $t_stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = intval($_POST['leave_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    
    if ($leave_id > 0 && ($status === 'Approved_Teacher' || $status === 'Rejected')) {
        $stmt = $db->prepare("UPDATE leave_requests SET status = ?, reviewed_by = ? WHERE leave_id = ?");
        $stmt->bind_param("sii", $status, $teacher_id, $leave_id);
        if ($stmt->execute()) {
            $message = 'Leave request status updated successfully.';
            $msg_type = 'success';
        } else {
            $message = 'Failed to update leave request.';
            $msg_type = 'danger';
        }
    }
}

// Fetch pending leave requests for students in the teacher's department
$dept_id = $teacher['department_id'];
$leaves_query = "SELECT lr.*, s.full_name as student_name, s.roll_number 
                 FROM leave_requests lr
                 JOIN students s ON lr.student_id = s.student_id
                 WHERE s.department_id = ?
                 ORDER BY lr.created_at DESC";
$leaves_stmt = $db->prepare($leaves_query);
$leaves_stmt->bind_param("i", $dept_id);
$leaves_stmt->execute();
$requests = $leaves_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave Applications - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body style="background: radial-gradient(circle at 50% 10%, rgba(16, 185, 129, 0.15), transparent 70%), var(--bg-body); min-height: 100vh;">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4" style="max-width: 1400px; margin: 0 auto;">
            <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="dashboard.php">
                <div class="brand-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(16,185,129,0.35);">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.95rem;">Manage Leaves <span class="badge bg-success-subtle text-success border border-success-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Faculty</span></span>
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

    <div class="container py-4" style="max-width: 1000px;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $msg_type; ?> rounded-4 mb-3"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="glass-card p-4">
            <h5 class="fw-bold text-main mb-3"><i class="fas fa-file-signature text-success me-2"></i> Leave Requests Dashboard</h5>
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Leave Period</th>
                            <th>Type & Reason</th>
                            <th>Evidence</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($requests) > 0): ?>
                            <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-main"><?php echo htmlspecialchars($r['student_name']); ?></div>
                                        <div class="small text-muted font-monospace"><?php echo htmlspecialchars($r['roll_number']); ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-bold text-main"><?php echo date('M d, Y', strtotime($r['start_date'])); ?></div>
                                        <div class="small text-muted">&rarr; <?php echo date('M d, Y', strtotime($r['end_date'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary mb-1"><?php echo $r['leave_type']; ?></span>
                                        <p class="mb-0 small text-muted"><?php echo htmlspecialchars($r['reason']); ?></p>
                                    </td>
                                    <td>
                                        <?php if ($r['document_path']): ?>
                                            <a href="../<?php echo $r['document_path']; ?>" target="_blank" class="btn btn-outline-primary btn-xs rounded-pill">
                                                <i class="fas fa-download me-1"></i> View Attachment
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">No evidence</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['status'] === 'Pending'): ?>
                                            <form method="POST" class="d-inline-flex gap-2">
                                                <input type="hidden" name="leave_id" value="<?php echo $r['leave_id']; ?>">
                                                <button type="submit" name="status" value="Approved_Teacher" class="btn btn-success btn-sm rounded-pill px-3">
                                                    <i class="fas fa-check me-1"></i> Approve
                                                </button>
                                                <button type="submit" name="status" value="Rejected" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                                    <i class="fas fa-times me-1"></i> Reject
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge <?php echo $r['status'] === 'Rejected' ? 'bg-danger' : 'bg-success'; ?>">
                                                <?php echo htmlspecialchars($r['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No student leave requests pending in your department.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
