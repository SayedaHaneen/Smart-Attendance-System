<?php
// teacher/live_attendance.php - Live Attendance View
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    redirect('../index.php');
}

$session_id = intval($_GET['session_id'] ?? 0);
if ($session_id <= 0) {
    redirect('dashboard.php');
}

$teacher_id = $_SESSION['teacher_id'];
$db = getDB();

// Get session details
$query = "SELECT sess.*, s.subject_name 
          FROM attendance_sessions sess
          JOIN subjects s ON sess.subject_id = s.subject_id
          WHERE sess.session_id = ? AND sess.teacher_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $session_id, $teacher_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    redirect('dashboard.php');
}

// Get attendance with student details
$attendance_query = "SELECT a.*, s.full_name as student_name, s.roll_number, sd.device_identifier
                     FROM attendance a
                     JOIN students s ON a.student_id = s.student_id
                     LEFT JOIN student_devices sd ON a.device_id = sd.device_id
                     WHERE a.session_id = ?
                     ORDER BY s.roll_number";
$attendance_stmt = $db->prepare($attendance_query);
$attendance_stmt->bind_param("i", $session_id);
$attendance_stmt->execute();
$attendance_list = $attendance_stmt->get_result();

// Get total students for this session
$total_query = "SELECT COUNT(*) as total 
                FROM students 
                WHERE semester_id = ? AND batch_id = ? AND section_id = ?";
$total_stmt = $db->prepare($total_query);
$total_stmt->bind_param("iii", $session['semester_id'], $session['batch_id'], $session['section_id']);
$total_stmt->execute();
$total_students = $total_stmt->get_result()->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Attendance - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/table.css" rel="stylesheet">
    <style>
        .status-present { color: #28a745; }
        .status-absent { color: #dc3545; }
        .status-late { color: #ffc107; }
        .refresh-btn {
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo APP_NAME; ?></a>
            <div class="navbar-nav ms-auto">
                <a href="dashboard.php" class="btn btn-light btn-sm">Back to Dashboard</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-eye"></i> Live Attendance - <?php echo htmlspecialchars($session['subject_name']); ?></h4>
                        <div>
                            <span class="badge bg-light text-dark me-2">
                                <i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($session['start_time'])); ?>
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-users"></i> <?php echo $attendance_list->num_rows; ?>/<?php echo $total_students; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-sync fa-spin"></i> Auto-refreshing every 5 seconds
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="attendanceTable">
                                <thead>
                                    <tr>
                                        <th>Roll</th>
                                        <th>Student</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                        <th>Device</th>
                                    </tr>
                                </thead>
                                <tbody id="attendanceBody">
                                    <?php if ($attendance_list->num_rows > 0): ?>
                                        <?php while ($attendance = $attendance_list->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($attendance['roll_number']); ?></td>
                                                <td><?php echo htmlspecialchars($attendance['student_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $attendance['status'] == 'Present' ? 'success' : ($attendance['status'] == 'Late' ? 'warning' : 'danger'); ?>">
                                                        <?php echo $attendance['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('h:i A', strtotime($attendance['attendance_time'])); ?></td>
                                                <td><small><?php echo substr(htmlspecialchars($attendance['device_identifier'] ?? ''), 0, 10); ?>...</small></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No attendance marked yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <a href="export_excel.php?session_id=<?php echo $session_id; ?>" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </a>
                            <a href="reports.php?session_id=<?php echo $session_id; ?>" class="btn btn-info">
                                <i class="fas fa-file-alt"></i> View Report
                            </a>
                            <button onclick="window.location.reload()" class="btn btn-primary">
                                <i class="fas fa-sync"></i> Refresh Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh attendance every 5 seconds
        setInterval(function() {
            fetch('fetch_live_attendance.php?session_id=<?php echo $session_id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        data.attendance.forEach(item => {
                            const badgeClass = item.status === 'Present' ? 'success' : 
                                             (item.status === 'Late' ? 'warning' : 'danger');
                            html += `<tr>
                                <td>${item.roll_number}</td>
                                <td>${item.student_name}</td>
                                <td><span class="badge bg-${badgeClass}">${item.status}</span></td>
                                <td>${item.time}</td>
                                <td><small>${item.device_id ? item.device_id.substring(0, 10) + '...' : 'N/A'}</small></td>
                            </tr>`;
                        });
                        document.getElementById('attendanceBody').innerHTML = html;
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 5000);
    </script>
</body>
</html>