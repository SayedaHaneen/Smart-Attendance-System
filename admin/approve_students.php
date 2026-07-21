<?php
// admin/approve_students.php - Admin Approve Students
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();
$message = '';
$message_type = '';

// Approve student
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $student_id = intval($_GET['approve']);
    
    $query = "UPDATE students SET is_approved = 1, approved_at = NOW() WHERE student_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $student_id);
    
    if ($stmt->execute()) {
        $message = "Student approved successfully!";
        $message_type = "success";
    } else {
        $message = "Failed to approve student";
        $message_type = "danger";
    }
}

// Reject/Delete student
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $student_id = intval($_GET['reject']);
    
    $query = "DELETE FROM students WHERE student_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $student_id);
    
    if ($stmt->execute()) {
        $message = "Student account request rejected!";
        $message_type = "warning";
    } else {
        $message = "Failed to reject student";
        $message_type = "danger";
    }
}

// Get pending students
$pending_query = "SELECT s.*, d.department_name, b.batch_year, sem.semester_name, sec.section_name 
                  FROM students s
                  LEFT JOIN departments d ON s.department_id = d.department_id
                  LEFT JOIN batches b ON s.batch_id = b.batch_id
                  LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
                  LEFT JOIN sections sec ON s.section_id = sec.section_id
                  WHERE s.is_approved = 0
                  ORDER BY s.created_at DESC";
$pending_students = $db->query($pending_query);

// Get approved students
$approved_query = "SELECT s.*, d.department_name, b.batch_year, sem.semester_name, sec.section_name 
                   FROM students s
                   LEFT JOIN departments d ON s.department_id = d.department_id
                   LEFT JOIN batches b ON s.batch_id = b.batch_id
                   LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
                   LEFT JOIN sections sec ON s.section_id = sec.section_id
                   WHERE s.is_approved = 1
                   ORDER BY s.approved_at DESC";
$approved_students = $db->query($approved_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Approvals - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="container-fluid px-3 px-md-4 py-4 animate-slide-up" style="max-width: 1400px; margin: 0 auto;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Pending Approvals Section -->
        <div class="custom-table-container mb-5 border-warning">
            <div class="p-3 border-bottom bg-warning-subtle d-flex justify-content-between align-items-center gap-3">
                <div>
                    <span class="fw-bold text-dark"><i class="fas fa-clock me-1 text-warning"></i> Pending Registrations (<?php echo $pending_students ? $pending_students->num_rows : 0; ?>)</span>
                </div>
                <div style="max-width: 280px;" class="w-100">
                    <input type="text" class="form-control form-control-sm" placeholder="Search pending..." data-table-search="pendingTable">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table" id="pendingTable">
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_students && $pending_students->num_rows > 0): ?>
                            <?php while ($row = $pending_students->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold font-monospace"><?php echo htmlspecialchars($row['roll_number']); ?></td>
                                    <td class="fw-bold text-main"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['semester_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="approve_students.php?approve=<?php echo $row['student_id']; ?>" class="btn btn-success btn-sm rounded-pill px-3 me-1">
                                            <i class="fas fa-check me-1"></i> Approve
                                        </a>
                                        <a href="approve_students.php?reject=<?php echo $row['student_id']; ?>" onclick="return confirm('Reject this student request?')" class="btn btn-outline-danger btn-sm rounded-pill px-2">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-check-double fa-2x mb-2 d-block opacity-50 text-success"></i>
                                    No pending student registrations. All caught up!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approved Students History -->
        <div class="custom-table-container">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center gap-3">
                <span class="fw-bold"><i class="fas fa-user-check me-1 text-success"></i> Approved Active Students (<?php echo $approved_students ? $approved_students->num_rows : 0; ?>)</span>
                <div style="max-width: 280px;" class="w-100">
                    <input type="text" class="form-control form-control-sm" placeholder="Search approved..." data-table-search="approvedTable">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table" id="approvedTable">
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($approved_students && $approved_students->num_rows > 0): ?>
                            <?php while ($row = $approved_students->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold font-monospace"><?php echo htmlspecialchars($row['roll_number']); ?></td>
                                    <td class="fw-bold text-main"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>