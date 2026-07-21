<?php
// admin/manage_teachers.php - Faculty Management & Complete Teacher CRUD
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();
$message = '';
$message_type = '';

// Delete Teacher
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $teacher_id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacher_id);
    if ($stmt->execute()) {
        $message = 'Teacher account removed successfully!';
        $message_type = 'warning';
    } else {
        $message = 'Failed to delete teacher: ' . $db->error;
        $message_type = 'danger';
    }
}

// Handle Add / Edit Teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_teacher'])) {
    $action = $_POST['action_teacher'];
    $teacher_id = intval($_POST['teacher_id'] ?? 0);
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $department_id = intval($_POST['department_id'] ?? 1);
    $phone = sanitize($_POST['phone'] ?? '');

    if (empty($full_name) || empty($email)) {
        $message = 'Full Name and Email are required.';
        $message_type = 'danger';
    } else {
        if ($action === 'add') {
            if (empty($password)) {
                $message = 'Password is required for new teacher.';
                $message_type = 'danger';
            } else {
                $check = $db->prepare("SELECT teacher_id FROM teachers WHERE email = ?");
                $check->bind_param("s", $email);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $message = 'Teacher email already exists!';
                    $message_type = 'danger';
                } else {
                    $username = explode('@', $email)[0];
                    $stmt = $db->prepare("INSERT INTO teachers (username, full_name, email, password, department_id, phone) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssis", $username, $full_name, $email, $password, $department_id, $phone);
                    if ($stmt->execute()) {
                        $message = 'Teacher registered successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to register teacher: ' . $db->error;
                        $message_type = 'danger';
                    }
                }
            }
        } elseif ($action === 'edit' && $teacher_id > 0) {
            if (!empty($password)) {
                $stmt = $db->prepare("UPDATE teachers SET full_name = ?, email = ?, password = ?, department_id = ?, phone = ? WHERE teacher_id = ?");
                $stmt->bind_param("sssisi", $full_name, $email, $password, $department_id, $phone, $teacher_id);
            } else {
                $stmt = $db->prepare("UPDATE teachers SET full_name = ?, email = ?, department_id = ?, phone = ? WHERE teacher_id = ?");
                $stmt->bind_param("ssisi", $full_name, $email, $department_id, $phone, $teacher_id);
            }
            if ($stmt->execute()) {
                $message = 'Teacher profile updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update teacher profile: ' . $db->error;
                $message_type = 'danger';
            }
        }
    }
}

// Search and Filter
$filter_dept = intval($_GET['dept_id'] ?? 0);
$search = sanitize($_GET['search'] ?? '');

$query = "SELECT t.*, d.department_name,
          (SELECT COUNT(*) FROM attendance_sessions s WHERE s.teacher_id = t.teacher_id) as session_count
          FROM teachers t
          LEFT JOIN departments d ON t.department_id = d.department_id
          WHERE 1=1";
$params = [];
$types = "";

if ($filter_dept > 0) {
    $query .= " AND t.department_id = ?";
    $params[] = $filter_dept;
    $types .= "i";
}
if (!empty($search)) {
    $query .= " AND (t.full_name LIKE ? OR t.email LIKE ? OR t.username LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
    $types .= "sss";
}

$query .= " ORDER BY d.department_name, t.full_name";
$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$teachers = $stmt->get_result();

$departments = $db->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - <?php echo APP_NAME; ?></title>
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

        <!-- Control Bar -->
        <div class="cohort-filter-bar mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="fw-bold mb-1 text-main"><i class="fas fa-user-tie text-success me-2"></i> Faculty Directory & CRUD</h5>
                    <p class="text-muted small mb-0">Manage university professors, assign departments, and manage credentials.</p>
                </div>
                <button type="button" data-bs-toggle="modal" data-bs-target="#teacherModal" onclick="resetTeacherForm()" class="btn btn-success rounded-pill px-4 fw-bold">
                    <i class="fas fa-plus-circle me-1"></i> Register New Teacher
                </button>
            </div>

            <form method="GET" action="manage_teachers.php" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <select name="dept_id" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                        <option value="0">-- All Faculty Departments --</option>
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

                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control rounded-start-3" placeholder="Search by Teacher Name or Email..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-success rounded-end-3"><i class="fas fa-search"></i></button>
                    </div>
                </div>

                <div class="col-md-2">
                    <?php if ($filter_dept || $search): ?>
                        <a href="manage_teachers.php" class="btn btn-outline-secondary btn-sm w-100 rounded-3">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Faculty Directory Table -->
        <div class="custom-table-container">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fas fa-chalkboard-teacher text-success me-2"></i> Faculty Members (<?php echo $teachers ? $teachers->num_rows : 0; ?> Listed)</span>
            </div>

            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Teacher Name</th>
                            <th>Email Address</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Sessions Conducted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($teachers && $teachers->num_rows > 0): ?>
                            <?php while ($t = $teachers->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-main"><?php echo htmlspecialchars($t['full_name']); ?></div>
                                        <small class="text-muted">@<?php echo htmlspecialchars($t['username']); ?></small>
                                    </td>
                                    <td class="font-monospace text-primary"><?php echo htmlspecialchars($t['email']); ?></td>
                                    <td><span class="badge badge-custom badge-present"><?php echo htmlspecialchars($t['department_name'] ?? 'General'); ?></span></td>
                                    <td><?php echo htmlspecialchars($t['phone'] ?: 'N/A'); ?></td>
                                    <td><span class="badge badge-custom badge-pending"><?php echo $t['session_count']; ?> Sessions</span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-2.5" 
                                                    onclick="editTeacher(<?php echo htmlspecialchars(json_encode($t)); ?>)" title="Edit Profile">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="manage_teachers.php?delete=<?php echo $t['teacher_id']; ?>" 
                                               class="btn btn-outline-danger btn-sm rounded-pill px-2.5"
                                               onclick="return confirm('Remove teacher account <?php echo htmlspecialchars(addslashes($t['full_name'])); ?>?')" title="Delete Teacher">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No faculty members found. Register one above!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add / Edit Teacher Modal -->
    <div class="modal fade" id="teacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 p-3">
                <div class="modal-header border-bottom pb-2">
                    <h5 class="modal-title fw-bold text-main" id="modalTitle"><i class="fas fa-user-plus text-success me-2"></i> Register New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="teacherForm">
                    <input type="hidden" name="action_teacher" id="formAction" value="add">
                    <input type="hidden" name="teacher_id" id="teacherId" value="0">

                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label font-semibold">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" id="t_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Email Address *</label>
                            <input type="email" class="form-control" name="email" id="t_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Password</label>
                            <input type="password" class="form-control" name="password" id="t_password" placeholder="Leave blank to keep unchanged">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Department *</label>
                            <select class="form-select" name="department_id" id="t_department_id" required>
                                <?php 
                                $departments->data_seek(0);
                                while ($d = $departments->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $d['department_id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-semibold">Phone Number</label>
                            <input type="text" class="form-control" name="phone" id="t_phone" placeholder="+92 300 1234567">
                        </div>
                    </div>

                    <div class="modal-footer border-top pt-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 fw-bold">
                            <i class="fas fa-save me-1"></i> Save Teacher Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        function editTeacher(item) {
            $('#formAction').val('edit');
            $('#teacherId').val(item.teacher_id);
            $('#t_full_name').val(item.full_name);
            $('#t_email').val(item.email);
            $('#t_department_id').val(item.department_id);
            $('#t_phone').val(item.phone);

            $('#modalTitle').html('<i class="fas fa-user-edit text-info me-2"></i> Edit Faculty Profile');
            const modal = new bootstrap.Modal(document.getElementById('teacherModal'));
            modal.show();
        }

        function resetTeacherForm() {
            $('#modalTitle').html('<i class="fas fa-user-plus text-success me-2"></i> Register New Teacher');
        }
    </script>
</body>
</html>