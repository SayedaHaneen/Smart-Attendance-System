<?php
// admin/manage_departments.php - Admin CRUD Operations for Departments
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();
$message = '';
$message_type = '';

// Add / Edit Department
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $department_name = sanitize($_POST['department_name'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 0);

    if (empty($department_name)) {
        $message = 'Department Name is required.';
        $message_type = 'danger';
    } else {
        if ($action === 'add') {
            $check = $db->prepare("SELECT department_id FROM departments WHERE department_name = ?");
            $check->bind_param("s", $department_name);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = 'Department name already exists!';
                $message_type = 'danger';
            } else {
                $ins = $db->prepare("INSERT INTO departments (department_name) VALUES (?)");
                $ins->bind_param("s", $department_name);
                if ($ins->execute()) {
                    $message = 'Department added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding department: ' . $db->error;
                    $message_type = 'danger';
                }
            }
        } elseif ($action === 'edit' && $department_id > 0) {
            $up = $db->prepare("UPDATE departments SET department_name = ? WHERE department_id = ?");
            $up->bind_param("si", $department_name, $department_id);
            if ($up->execute()) {
                $message = 'Department updated!';
                $message_type = 'success';
            } else {
                $message = 'Error updating department: ' . $db->error;
                $message_type = 'danger';
            }
        }
    }
}

// Delete Department
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $del = $db->prepare("DELETE FROM departments WHERE department_id = ?");
    $del->bind_param("i", $del_id);
    if ($del->execute()) {
        $message = 'Department deleted successfully!';
        $message_type = 'warning';
    } else {
        $message = 'Failed to delete department.';
        $message_type = 'danger';
    }
}

// Fetch Departments list
$query = "SELECT d.*, 
          (SELECT COUNT(*) FROM students s WHERE s.department_id = d.department_id) as student_count,
          (SELECT COUNT(*) FROM teachers t WHERE t.department_id = d.department_id) as teacher_count
          FROM departments d 
          ORDER BY d.department_name";
$departments = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments - <?php echo APP_NAME; ?></title>
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
            <!-- Add/Edit Form Card -->
            <div class="col-lg-4">
                <div class="glass-card shadow-lg">
                    <div class="glass-card-header bg-success text-white py-3">
                        <span id="formTitle"><i class="fas fa-plus-circle me-2"></i> Create Department</span>
                    </div>
                    <div class="glass-card-body p-4">
                        <form method="POST" id="deptForm">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="department_id" id="deptId" value="0">

                            <div class="mb-4">
                                <label for="department_name" class="form-label font-semibold">Department Name *</label>
                                <input type="text" class="form-control" id="department_name" name="department_name" placeholder="e.g. Artificial Intelligence & Data Science" required>
                            </div>

                            <button type="submit" id="submitBtn" class="btn btn-success w-100 py-2.5 rounded-3 fw-bold">
                                <i class="fas fa-plus me-1"></i> Save Department
                            </button>
                            <button type="button" id="cancelBtn" onclick="resetDeptForm()" class="btn btn-outline-secondary w-100 py-2 mt-2 rounded-3 d-none">
                                Cancel Edit
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Department Directory Table -->
            <div class="col-lg-8">
                <div class="custom-table-container">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <span class="fw-bold"><i class="fas fa-building text-success me-2"></i> Academic Departments (<?php echo $departments ? $departments->num_rows : 0; ?> Total)</span>
                        <div style="max-width: 260px;" class="w-100">
                            <input type="text" class="form-control form-control-sm" placeholder="Search department..." data-table-search="deptTable">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table custom-table" id="deptTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Department Name</th>
                                    <th>Students</th>
                                    <th>Faculty</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($departments && $departments->num_rows > 0): ?>
                                    <?php while ($d = $departments->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace">#<?php echo $d['department_id']; ?></td>
                                            <td class="fw-bold text-main"><?php echo htmlspecialchars($d['department_name']); ?></td>
                                            <td><span class="badge badge-custom badge-pending"><?php echo $d['student_count']; ?> Enrolled</span></td>
                                            <td><span class="badge badge-custom badge-present"><?php echo $d['teacher_count']; ?> Faculty</span></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-2.5" 
                                                            onclick="editDept(<?php echo htmlspecialchars(json_encode($d)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="manage_departments.php?delete=<?php echo $d['department_id']; ?>" 
                                                       class="btn btn-outline-danger btn-sm rounded-pill px-2.5"
                                                       onclick="return confirm('Delete department <?php echo htmlspecialchars(addslashes($d['department_name'])); ?>?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No departments found. Create one above!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        function editDept(item) {
            $('#formAction').val('edit');
            $('#deptId').val(item.department_id);
            $('#department_name').val(item.department_name);

            $('#formTitle').html('<i class="fas fa-edit me-2"></i> Edit Department');
            $('#submitBtn').html('<i class="fas fa-save me-1"></i> Update Department');
            $('#cancelBtn').removeClass('d-none');
        }

        function resetDeptForm() {
            $('#formAction').val('add');
            $('#deptId').val('0');
            $('#deptForm')[0].reset();
            $('#formTitle').html('<i class="fas fa-plus-circle me-2"></i> Create Department');
            $('#submitBtn').html('<i class="fas fa-plus me-1"></i> Save Department');
            $('#cancelBtn').addClass('d-none');
        }
    </script>
</body>
</html>
