<?php
// admin/manage_students.php - Structured Cohort Directory & Student CRUD Operations
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();
$message = '';
$message_type = '';

if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = sanitize($_GET['type'] ?? 'info');
}

// Handle CSV Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file_path = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            // Read header row
            $header = fgetcsv($handle);
            
            $imported = 0;
            $skipped = 0;
            $errors = 0;
            
            $ins = $db->prepare("INSERT INTO students (username, full_name, roll_number, email, password, department_id, batch_id, semester_id, section_id, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 9) {
                    $errors++;
                    continue;
                }
                $full_name = sanitize($data[0]);
                $roll_number = sanitize($data[1]);
                $username = sanitize($data[2]);
                $email = sanitize($data[3]);
                $password = $data[4];
                $department_id = intval($data[5] ?: 1);
                $batch_id = intval($data[6] ?: 1);
                $semester_id = intval($data[7] ?: 1);
                $section_id = intval($data[8] ?: 1);
                
                // Check duplicate
                $chk = $db->prepare("SELECT student_id FROM students WHERE username = ? OR roll_number = ? OR email = ?");
                $chk->bind_param("sss", $username, $roll_number, $email);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    $skipped++;
                } else {
                    $ins->bind_param("sssssiiii", $username, $full_name, $roll_number, $email, $password, $department_id, $batch_id, $semester_id, $section_id);
                    if ($ins->execute()) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                }
            }
            fclose($handle);
            $message = "Import results: Successfully imported $imported students. Skipped $skipped duplicates. Errors: $errors.";
            $message_type = ($imported > 0) ? "success" : "warning";
            
            header("Location: manage_students.php?msg=" . urlencode($message) . "&type=" . $message_type);
            exit;
        } else {
            $message = "Failed to open uploaded CSV file.";
            $message_type = "danger";
        }
    } else {
        $message = "Please choose a valid CSV file to upload.";
        $message_type = "danger";
    }
}

// Handle Delete Student
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $del = $db->prepare("DELETE FROM students WHERE student_id = ?");
    $del->bind_param("i", $del_id);
    if ($del->execute()) {
        $message = "Student record deleted successfully!";
        $message_type = "warning";
    } else {
        $message = "Failed to delete student: " . $db->error;
        $message_type = "danger";
    }
}

// Handle Add / Edit Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_student'])) {
    $action = $_POST['action_student'];
    $student_id = intval($_POST['student_id'] ?? 0);
    $full_name = sanitize($_POST['full_name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $roll_number = sanitize($_POST['roll_number'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 1);
    $batch_id = intval($_POST['batch_id'] ?? 1);
    $semester_id = intval($_POST['semester_id'] ?? 1);
    $section_id = intval($_POST['section_id'] ?? 1);
    $password = $_POST['password'] ?? '';

    if ($action === 'add') {
        $chk = $db->prepare("SELECT student_id FROM students WHERE username = ? OR roll_number = ?");
        $chk->bind_param("ss", $username, $roll_number);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $message = "Username or Roll Number already exists!";
            $message_type = "danger";
        } else {
            $ins = $db->prepare("INSERT INTO students (username, full_name, roll_number, email, password, department_id, batch_id, semester_id, section_id, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $ins->bind_param("sssssiiii", $username, $full_name, $roll_number, $email, $password, $department_id, $batch_id, $semester_id, $section_id);
            if ($ins->execute()) {
                $message = "Student added and approved successfully!";
                $message_type = "success";
            } else {
                $message = "Failed to add student: " . $db->error;
                $message_type = "danger";
            }
        }
    } elseif ($action === 'edit' && $student_id > 0) {
        if (!empty($password)) {
            $up = $db->prepare("UPDATE students SET full_name = ?, roll_number = ?, email = ?, password = ?, department_id = ?, batch_id = ?, semester_id = ?, section_id = ? WHERE student_id = ?");
            $up->bind_param("ssssiiiii", $full_name, $roll_number, $email, $password, $department_id, $batch_id, $semester_id, $section_id, $student_id);
        } else {
            $up = $db->prepare("UPDATE students SET full_name = ?, roll_number = ?, email = ?, department_id = ?, batch_id = ?, semester_id = ?, section_id = ? WHERE student_id = ?");
            $up->bind_param("sssiiiii", $full_name, $roll_number, $email, $department_id, $batch_id, $semester_id, $section_id, $student_id);
        }
        if ($up->execute()) {
            $message = "Student profile updated!";
            $message_type = "success";
        } else {
            $message = "Failed to update student: " . $db->error;
            $message_type = "danger";
        }
    }
}

// Get filter inputs
$filter_dept = intval($_GET['dept_id'] ?? 0);
$filter_batch = intval($_GET['batch_id'] ?? 0);
$filter_sem = intval($_GET['sem_id'] ?? 0);
$filter_sec = intval($_GET['sec_id'] ?? 0);
$search = sanitize($_GET['search'] ?? '');

// Build query
$query = "SELECT s.*, d.department_name, b.batch_year, sem.semester_name, sec.section_name,
          sd.device_identifier, sd.is_active as device_active
          FROM students s
          LEFT JOIN departments d ON s.department_id = d.department_id
          LEFT JOIN batches b ON s.batch_id = b.batch_id
          LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
          LEFT JOIN sections sec ON s.section_id = sec.section_id
          LEFT JOIN student_devices sd ON s.student_id = sd.student_id
          WHERE 1=1";

$params = [];
$types = "";

if ($filter_dept > 0) {
    $query .= " AND s.department_id = ?";
    $params[] = $filter_dept;
    $types .= "i";
}
if ($filter_batch > 0) {
    $query .= " AND s.batch_id = ?";
    $params[] = $filter_batch;
    $types .= "i";
}
if ($filter_sem > 0) {
    $query .= " AND s.semester_id = ?";
    $params[] = $filter_sem;
    $types .= "i";
}
if ($filter_sec > 0) {
    $query .= " AND s.section_id = ?";
    $params[] = $filter_sec;
    $types .= "i";
}
if (!empty($search)) {
    $query .= " AND (s.full_name LIKE ? OR s.roll_number LIKE ? OR s.username LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
    $types .= "sss";
}

$query .= " GROUP BY s.student_id ORDER BY s.roll_number ASC, s.full_name ASC";
$stmt = $db->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Full Name', 'Roll Number', 'Username', 'Email', 'Department', 'Batch', 'Semester', 'Section', 'Status']);
    
    while ($row = $students->fetch_assoc()) {
        $status = ($row['is_approved'] == 1) ? 'Approved' : 'Pending';
        fputcsv($output, [
            $row['full_name'],
            $row['roll_number'],
            $row['username'],
            $row['email'],
            $row['department_name'],
            $row['batch_year'],
            $row['semester_name'],
            $row['section_name'],
            $status
        ]);
    }
    fclose($output);
    exit;
}

// Fetch dropdown data
$departments = $db->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
$batches = $db->query("SELECT batch_id, batch_year FROM batches ORDER BY batch_year DESC");
$semesters = $db->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_id");
$sections = $db->query("SELECT section_id, section_name FROM sections ORDER BY section_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cohort Student Directory - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="container-fluid px-3 px-md-4 py-4 animate-slide-up" style="max-width: 1400px; margin: 0 auto;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Cohort Filter Header Bar -->
        <div class="cohort-filter-bar mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="fw-bold mb-1 text-main"><i class="fas fa-filter text-primary me-2"></i> Structured Cohort Selection</h5>
                    <p class="text-muted small mb-0">Select Department, Batch, Semester, and Section to filter enrolled students.</p>
                </div>
                <button type="button" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="resetStudentForm()" class="btn btn-primary-custom rounded-pill px-4 fw-bold">
                    <i class="fas fa-user-plus me-1"></i> Add New Student
                </button>
            </div>

            <form method="GET" action="manage_students.php" class="row g-2 align-items-center">
                <!-- 1. Department -->
                <div class="col-md-3">
                    <label class="form-label small font-semibold text-muted mb-1">Department</label>
                    <select name="dept_id" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                        <option value="0">-- All Departments --</option>
                        <?php 
                        $departments->data_seek(0);
                        while ($d = $departments->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $d['department_id']; ?>" <?php echo $filter_dept == $d['department_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['department_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- 2. Batch -->
                <div class="col-md-2">
                    <label class="form-label small font-semibold text-muted mb-1">Batch Year</label>
                    <select name="batch_id" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                        <option value="0">-- All Batches --</option>
                        <?php 
                        $batches->data_seek(0);
                        while ($b = $batches->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $b['batch_id']; ?>" <?php echo $filter_batch == $b['batch_id'] ? 'selected' : ''; ?>>
                                Batch <?php echo htmlspecialchars($b['batch_year']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- 3. Semester -->
                <div class="col-md-2">
                    <label class="form-label small font-semibold text-muted mb-1">Semester</label>
                    <select name="sem_id" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                        <option value="0">-- All Semesters --</option>
                        <?php 
                        $semesters->data_seek(0);
                        while ($sem = $semesters->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $sem['semester_id']; ?>" <?php echo $filter_sem == $sem['semester_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sem['semester_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- 4. Section -->
                <div class="col-md-2">
                    <label class="form-label small font-semibold text-muted mb-1">Section</label>
                    <select name="sec_id" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                        <option value="0">-- All Sections --</option>
                        <?php 
                        $sections->data_seek(0);
                        while ($sec = $sections->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $sec['section_id']; ?>" <?php echo $filter_sec == $sec['section_id'] ? 'selected' : ''; ?>>
                                Section <?php echo htmlspecialchars($sec['section_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-md-3">
                    <label class="form-label small font-semibold text-muted mb-1">Search Student</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control rounded-start-3" placeholder="Roll No / Name / Username" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary-custom rounded-end-3"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Student Directory Table -->
        <div class="custom-table-container">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold"><i class="fas fa-user-graduate text-primary me-2"></i> Enrolled Roster</span>
                    <span class="badge badge-custom badge-pending"><?php echo $students ? $students->num_rows : 0; ?> Students Listed</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-file-import me-1"></i> Import CSV
                    </button>
                    <a href="manage_students.php?export=csv&dept_id=<?php echo $filter_dept; ?>&batch_id=<?php echo $filter_batch; ?>&sem_id=<?php echo $filter_sem; ?>&sec_id=<?php echo $filter_sec; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
                        <i class="fas fa-file-export me-1"></i> Export CSV
                    </a>
                    <?php if ($filter_dept || $filter_batch || $filter_sem || $filter_sec || $search): ?>
                        <a href="manage_students.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                            <i class="fas fa-times me-1"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Batch & Semester</th>
                            <th>Section</th>
                            <th>Bound Single-Device Token</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students && $students->num_rows > 0): ?>
                            <?php while ($s = $students->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold font-monospace text-primary"><?php echo htmlspecialchars($s['roll_number']); ?></td>
                                    <td>
                                        <div class="fw-bold text-main"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                        <small class="text-muted">@<?php echo htmlspecialchars($s['username']); ?></small>
                                    </td>
                                    <td><span class="badge badge-custom badge-present"><?php echo htmlspecialchars($s['department_name'] ?? 'SE'); ?></span></td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill me-1">Batch <?php echo htmlspecialchars($s['batch_year'] ?? '2024'); ?></span>
                                        <span class="badge bg-info-subtle text-info rounded-pill"><?php echo htmlspecialchars($s['semester_name'] ?? 'Semester 1'); ?></span>
                                    </td>
                                    <td><span class="fw-bold text-main">Section <?php echo htmlspecialchars($s['section_name'] ?? 'A'); ?></span></td>
                                    <td>
                                        <?php if (!empty($s['device_identifier'])): ?>
                                            <span class="font-monospace small text-success" title="<?php echo htmlspecialchars($s['device_identifier']); ?>">
                                                <i class="fas fa-mobile-alt me-1"></i> <?php echo htmlspecialchars(substr($s['device_identifier'], 0, 14)); ?>...
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fas fa-unlock me-1"></i> No Device Bound</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($s['is_approved'] == 1): ?>
                                            <span class="badge badge-custom badge-present"><i class="fas fa-check-circle me-1"></i> Approved</span>
                                        <?php else: ?>
                                            <span class="badge badge-custom badge-pending"><i class="fas fa-clock me-1"></i> Pending Approval</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-2.5" 
                                                    onclick="editStudent(<?php echo htmlspecialchars(json_encode($s)); ?>)" title="Edit Student">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (!empty($s['device_identifier'])): ?>
                                                <a href="reset_device.php?id=<?php echo $s['student_id']; ?>" 
                                                   class="btn btn-outline-warning btn-sm rounded-pill px-2.5" 
                                                   onclick="return confirm('Reset bound single-device lock for <?php echo htmlspecialchars(addslashes($s['full_name'])); ?>?')" title="Reset Bound Device">
                                                    <i class="fas fa-redo"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="manage_students.php?delete_id=<?php echo $s['student_id']; ?>" 
                                               class="btn btn-outline-danger btn-sm rounded-pill px-2.5" 
                                               onclick="return confirm('Permanently delete student <?php echo htmlspecialchars(addslashes($s['full_name'])); ?>?')" title="Delete Student">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-50"></i>
                                    No students found for the selected cohort filter.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Student Add / Edit Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content glass-card border-0 p-3">
                <div class="modal-header border-bottom pb-2">
                    <h5 class="modal-title fw-bold text-main" id="modalTitle"><i class="fas fa-user-plus text-primary me-2"></i> Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="studentForm">
                    <input type="hidden" name="action_student" id="formAction" value="add">
                    <input type="hidden" name="student_id" id="studentId" value="0">

                    <div class="modal-body py-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" id="m_full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Username *</label>
                                <input type="text" class="form-control" name="username" id="m_username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Roll Number *</label>
                                <input type="text" class="form-control font-monospace" name="roll_number" id="m_roll_number" placeholder="2025-SE-001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Email *</label>
                                <input type="email" class="form-control" name="email" id="m_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Password</label>
                                <input type="password" class="form-control" name="password" id="m_password" placeholder="Leave blank to keep unchanged">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Department *</label>
                                <select class="form-select" name="department_id" id="m_department_id" required>
                                    <?php 
                                    $departments->data_seek(0);
                                    while ($d = $departments->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $d['department_id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Batch *</label>
                                <select class="form-select" name="batch_id" id="m_batch_id" required>
                                    <?php 
                                    $batches->data_seek(0);
                                    while ($b = $batches->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $b['batch_id']; ?>">Batch <?php echo htmlspecialchars($b['batch_year']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Semester *</label>
                                <select class="form-select" name="semester_id" id="m_semester_id" required>
                                    <?php 
                                    $semesters->data_seek(0);
                                    while ($sem = $semesters->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $sem['semester_id']; ?>"><?php echo htmlspecialchars($sem['semester_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Section *</label>
                                <select class="form-select" name="section_id" id="m_section_id" required>
                                    <?php 
                                    $sections->data_seek(0);
                                    while ($sec = $sections->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $sec['section_id']; ?>">Section <?php echo htmlspecialchars($sec['section_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-top pt-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom btn-sm rounded-pill px-4 fw-bold">
                            <i class="fas fa-save me-1"></i> Save Student Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel"><i class="fas fa-file-import text-primary me-2"></i> Batch Import Students (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="manage_students.php" enctype="multipart/form-data">
                    <input type="hidden" name="import_csv" value="1">
                    <div class="modal-body">
                        <div class="alert alert-info small rounded-3">
                            <strong>CSV Formatting Requirements:</strong><br>
                            1. The first row must be headers (will be skipped).<br>
                            2. Columns order: <code>FullName, RollNumber, Username, Email, Password, DepartmentID, BatchID, SemesterID, SectionID</code><br>
                            3. Columns 6 to 9 represent database IDs. Default is <code>1</code>.
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Select CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        function editStudent(item) {
            $('#formAction').val('edit');
            $('#studentId').val(item.student_id);
            $('#m_full_name').val(item.full_name);
            $('#m_username').val(item.username);
            $('#m_roll_number').val(item.roll_number);
            $('#m_email').val(item.email);
            $('#m_department_id').val(item.department_id);
            $('#m_batch_id').val(item.batch_id);
            $('#m_semester_id').val(item.semester_id);
            $('#m_section_id').val(item.section_id);

            $('#modalTitle').html('<i class="fas fa-user-edit text-info me-2"></i> Edit Student Profile');
            const modal = new bootstrap.Modal(document.getElementById('studentModal'));
            modal.show();
        }

        function resetStudentForm() {
            $('#formAction').val('add');
            $('#studentId').val('0');
            $('#modalTitle').html('<i class="fas fa-user-plus text-primary me-2"></i> Add New Student');
        }
    </script>
</body>
</html>