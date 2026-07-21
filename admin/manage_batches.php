<?php
// admin/manage_batches.php - Admin CRUD Operations for Batches & Semesters
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();
$message = '';
$message_type = '';

// Add / Edit Batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_batch'])) {
    $batch_year = sanitize($_POST['batch_year'] ?? '');
    $batch_id = intval($_POST['batch_id'] ?? 0);

    if (empty($batch_year)) {
        $message = 'Batch Year is required.';
        $message_type = 'danger';
    } else {
        if ($_POST['action_batch'] === 'add') {
            $ins = $db->prepare("INSERT INTO batches (batch_year) VALUES (?)");
            $ins->bind_param("s", $batch_year);
            if ($ins->execute()) {
                $message = 'Batch year added!';
                $message_type = 'success';
            } else {
                $message = 'Error adding batch: ' . $db->error;
                $message_type = 'danger';
            }
        }
    }
}

// Delete Batch
if (isset($_GET['delete_batch']) && is_numeric($_GET['delete_batch'])) {
    $del_id = intval($_GET['delete_batch']);
    $del = $db->prepare("DELETE FROM batches WHERE batch_id = ?");
    $del->bind_param("i", $del_id);
    if ($del->execute()) {
        $message = 'Batch deleted!';
        $message_type = 'warning';
    } else {
        $message = 'Failed to delete batch.';
        $message_type = 'danger';
    }
}

// Fetch Batches & Semesters
$batches = $db->query("SELECT b.*, (SELECT COUNT(*) FROM students s WHERE s.batch_id = b.batch_id) as student_count FROM batches b ORDER BY b.batch_year DESC");
$semesters = $db->query("SELECT sem.*, (SELECT COUNT(*) FROM students s WHERE s.semester_id = sem.semester_id) as student_count FROM semesters sem ORDER BY sem.semester_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Batches & Semesters - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="container-fluid px-3 px-md-4 py-4 animate-slide-up" style="max-width: 1400px; margin: 0 auto;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Batches Column -->
            <div class="col-lg-6">
                <div class="glass-card mb-4">
                    <div class="glass-card-header bg-primary text-white py-3">
                        <span><i class="fas fa-calendar-alt me-2"></i> Add Batch Year</span>
                    </div>
                    <div class="glass-card-body p-3">
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="action_batch" value="add">
                            <input type="text" class="form-control" name="batch_year" placeholder="e.g. 2026" required>
                            <button type="submit" class="btn btn-primary-custom text-nowrap fw-bold"><i class="fas fa-plus me-1"></i> Add Batch</button>
                        </form>
                    </div>
                </div>

                <div class="custom-table-container">
                    <div class="p-3 border-bottom font-semibold"><i class="fas fa-graduation-cap text-primary me-2"></i> Active Academic Batches</div>
                    <div class="table-responsive">
                        <table class="table custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Batch Year</th>
                                    <th>Students</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($batches && $batches->num_rows > 0): ?>
                                    <?php while ($b = $batches->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace">#<?php echo $b['batch_id']; ?></td>
                                            <td class="fw-bold text-main">Batch <?php echo htmlspecialchars($b['batch_year']); ?></td>
                                            <td><span class="badge badge-custom badge-pending"><?php echo $b['student_count']; ?> Students</span></td>
                                            <td>
                                                <a href="manage_batches.php?delete_batch=<?php echo $b['batch_id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm rounded-pill px-2.5"
                                                   onclick="return confirm('Delete batch <?php echo $b['batch_year']; ?>?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Semesters Column -->
            <div class="col-lg-6">
                <div class="custom-table-container h-100">
                    <div class="p-3 border-bottom font-semibold"><i class="fas fa-layer-group text-warning me-2"></i> Academic Semesters</div>
                    <div class="table-responsive">
                        <table class="table custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Semester Name</th>
                                    <th>Enrolled Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($semesters && $semesters->num_rows > 0): ?>
                                    <?php while ($sem = $semesters->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace">#<?php echo $sem['semester_id']; ?></td>
                                            <td class="fw-bold text-main"><?php echo htmlspecialchars($sem['semester_name']); ?></td>
                                            <td><span class="badge badge-custom badge-present"><?php echo $sem['student_count']; ?> Students</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
