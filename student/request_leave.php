<?php
// student/request_leave.php - Request Leave with File Uploads
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    redirect('../index.php');
}

$student_id = $_SESSION['student_id'];
$db = getDB();
$message = '';
$msg_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = sanitize($_POST['leave_type'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');
    
    $document_path = null;
    
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/leaves/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['document']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
            $document_path = 'uploads/leaves/' . $file_name;
        }
    }
    
    if ($leave_type && $start_date && $end_date && $reason) {
        $stmt = $db->prepare("INSERT INTO leave_requests (student_id, leave_type, start_date, end_date, reason, document_path) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $student_id, $leave_type, $start_date, $end_date, $reason, $document_path);
        if ($stmt->execute()) {
            $message = 'Leave request submitted successfully. Waiting for Teacher/HOD approval.';
            $msg_type = 'success';
        } else {
            $message = 'Failed to submit leave request.';
            $msg_type = 'danger';
        }
    } else {
        $message = 'Please fill out all required fields.';
        $msg_type = 'warning';
    }
}

// Fetch past requests
$past_stmt = $db->prepare("SELECT * FROM leave_requests WHERE student_id = ? ORDER BY created_at DESC");
$past_stmt->bind_param("i", $student_id);
$past_stmt->execute();
$past_leaves = $past_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Leave Request - <?php echo APP_NAME; ?></title>
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
                    <i class="fas fa-file-alt"></i>
                </div>
                <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.95rem;">Leave Management <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Student</span></span>
            </a>
            
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Light/Dark Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4" style="max-width: 800px;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $msg_type; ?> rounded-4 mb-3"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="glass-card p-4">
                    <h5 class="fw-bold text-main mb-3"><i class="fas fa-edit text-primary me-2"></i> Leave Request Form</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label font-semibold">Leave Type</label>
                            <select class="form-select" name="leave_type" required>
                                <option value="Medical">Medical Leave</option>
                                <option value="Official">Official Duty Leave</option>
                                <option value="Sports">Sports Leave</option>
                                <option value="Emergency">Emergency Leave</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Reason Description</label>
                            <textarea class="form-control" name="reason" rows="3" required placeholder="Explain why leave is required..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Supporting Evidence (PDF/Image)</label>
                            <input type="file" class="form-control" name="document">
                        </div>
                        <button type="submit" class="btn btn-primary-custom w-100 py-2">
                            <i class="fas fa-paper-plane me-1"></i> Submit Leave Request
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-7">
                <div class="glass-card p-4">
                    <h5 class="fw-bold text-main mb-3"><i class="fas fa-history text-primary me-2"></i> Leave Request History</h5>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Dates</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($past_leaves) > 0): ?>
                                    <?php foreach ($past_leaves as $l): ?>
                                        <tr>
                                            <td>
                                                <div class="small fw-bold text-main"><?php echo date('M d', strtotime($l['start_date'])); ?> - <?php echo date('M d, Y', strtotime($l['end_date'])); ?></div>
                                                <div class="small text-muted" style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($l['reason']); ?></div>
                                            </td>
                                            <td><?php echo $l['leave_type']; ?></td>
                                            <td>
                                                <?php if ($l['status'] === 'Approved_HOD' || $l['status'] === 'Approved_Teacher'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php elseif ($l['status'] === 'Rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">No past leave requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
