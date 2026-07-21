<?php
// admin/manage_students.php - With force logout option
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();

// Handle messages
$message = $_GET['message'] ?? '';
$message_type = $_GET['type'] ?? 'info';

// Get active sessions info
$sessions_query = "SELECT as.*, s.full_name, s.roll_number, s.email, sd.device_identifier 
                   FROM active_sessions as
                   JOIN students s ON as.student_id = s.student_id
                   JOIN student_devices sd ON as.device_id = sd.device_id
                   WHERE as.is_active = 1
                   ORDER BY as.login_time DESC";
$active_sessions = $db->query($sessions_query);

// Get search parameters
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query for students
$query = "SELECT s.*, d.department_name, b.batch_year, sem.semester_name, sec.section_name,
          sd.device_identifier, sd.is_active as device_active,
          (SELECT COUNT(*) FROM active_sessions WHERE student_id = s.student_id AND is_active = 1) as active_sessions_count
          FROM students s
          LEFT JOIN departments d ON s.department_id = d.department_id
          LEFT JOIN batches b ON s.batch_id = b.batch_id
          LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
          LEFT JOIN sections sec ON s.section_id = sec.section_id
          LEFT JOIN student_devices sd ON s.student_id = sd.student_id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM students s WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (s.full_name LIKE ? OR s.roll_number LIKE ? OR s.email LIKE ?)";
    $count_query .= " AND (s.full_name LIKE ? OR s.roll_number LIKE ? OR s.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " GROUP BY s.student_id ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Get total count
$count_stmt = $db->prepare($count_query);
if (!empty($params)) {
    $count_params = array_slice($params, 0, count($params) - 2);
    if (!empty($count_params)) {
        $count_types = substr($types, 0, -2);
        $count_stmt->bind_param($count_types, ...$count_params);
    }
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get students
$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/table.css" rel="stylesheet">
    <style>
        .status-online { color: #00b894; }
        .status-offline { color: #b2bec3; }
        .session-badge { font-size: 11px; padding: 2px 8px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo APP_NAME; ?></a>
            <div class="navbar-nav ms-auto">
                <a href="dashboard.php" class="btn btn-light btn-sm">Back to Dashboard</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Active Sessions Overview -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> Active Sessions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Roll</th>
                                <th>Device</th>
                                <th>Login Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($active_sessions && $active_sessions->num_rows > 0): ?>
                                <?php while ($session = $active_sessions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($session['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($session['roll_number']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo substr($session['device_identifier'], 0, 12); ?>...
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y h:i A', strtotime($session['login_time'])); ?></td>
                                        <td>
                                            <a href="force_logout.php?student_id=<?php echo $session['student_id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Force logout this student from all devices?')">
                                                <i class="fas fa-power-off"></i> Force Logout
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No active sessions</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- All Students -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> All Students</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search by name, roll number, or email" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Roll</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Device</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($students->num_rows > 0): ?>
                                <?php while ($student = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td>
                                            <?php if ($student['is_approved']): ?>
                                                <span class="badge bg-success">Approved</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                            <?php if ($student['active_sessions_count'] > 0): ?>
                                                <span class="badge bg-info status-online">
                                                    <i class="fas fa-circle"></i> Online
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['device_identifier']): ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo substr($student['device_identifier'], 0, 10); ?>...
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Registered</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['active_sessions_count'] > 0): ?>
                                                <a href="force_logout.php?student_id=<?php echo $student['student_id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Force logout this student?')">
                                                    <i class="fas fa-power-off"></i> Logout
                                                </a>
                                            <?php endif; ?>
                                            <a href="reset_device.php?student_id=<?php echo $student['student_id']; ?>" 
                                               class="btn btn-sm btn-warning"
                                               onclick="return confirm('Reset device for this student?')">
                                                <i class="fas fa-sync"></i> Reset
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>