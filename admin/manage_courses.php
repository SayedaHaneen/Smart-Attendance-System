<?php
// admin/manage_courses.php - Admin CRUD Operations for Courses / Subjects
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();
$message = '';
$message_type = '';

// Add / Edit Course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $subject_name = sanitize($_POST['subject_name'] ?? '');
    $subject_code = sanitize($_POST['subject_code'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 1);
    $semester_id = intval($_POST['semester_id'] ?? 1);
    $subject_id = intval($_POST['subject_id'] ?? 0);

    if (empty($subject_name) || empty($subject_code)) {
        $message = 'Course Name and Course Code are required.';
        $message_type = 'danger';
    } else {
        if ($action === 'add') {
            $check = $db->prepare("SELECT subject_id FROM subjects WHERE subject_code = ?");
            $check->bind_param("s", $subject_code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = 'Course Code already exists!';
                $message_type = 'danger';
            } else {
                $ins = $db->prepare("INSERT INTO subjects (subject_name, subject_code, department_id, semester_id) VALUES (?, ?, ?, ?)");
                $ins->bind_param("ssii", $subject_name, $subject_code, $department_id, $semester_id);
                if ($ins->execute()) {
                    $message = 'Course added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding course: ' . $db->error;
                    $message_type = 'danger';
                }
            }
        } elseif ($action === 'edit' && $subject_id > 0) {
            $up = $db->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, department_id = ?, semester_id = ? WHERE subject_id = ?");
            $up->bind_param("ssiii", $subject_name, $subject_code, $department_id, $semester_id, $subject_id);
            if ($up->execute()) {
                $message = 'Course details updated!';
                $message_type = 'success';
            } else {
                $message = 'Error updating course: ' . $db->error;
                $message_type = 'danger';
            }
        }
    }
}

// Delete Course
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $del = $db->prepare("DELETE FROM subjects WHERE subject_id = ?");
    $del->bind_param("i", $del_id);
    if ($del->execute()) {
        $message = 'Course deleted successfully!';
        $message_type = 'warning';
    } else {
        $message = 'Failed to delete course.';
        $message_type = 'danger';
    }
}

// Fetch Courses list
$query = "SELECT s.*, d.department_name, sem.semester_name 
          FROM subjects s
          LEFT JOIN departments d ON s.department_id = d.department_id
          LEFT JOIN semesters sem ON s.semester_id = sem.semester_id
          ORDER BY d.department_name, s.subject_code";
$courses = $db->query($query);

// Fetch dropdown data
$departments = $db->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
$semesters = $db->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - <?php echo APP_NAME; ?></title>
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
                    <div class="glass-card-header bg-primary text-white py-3">
                        <span id="formTitle"><i class="fas fa-plus-circle me-2"></i> Add New Academic Course</span>
                    </div>
                    <div class="glass-card-body p-4">
                        <form method="POST" id="courseForm">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="subject_id" id="subjectId" value="0">

                            <div class="mb-3">
                                <label for="subject_code" class="form-label font-semibold">Course Code *</label>
                                <input type="text" class="form-control font-monospace" id="subject_code" name="subject_code" placeholder="e.g. CS-301" required>
                            </div>

                            <div class="mb-3">
                                <label for="subject_name" class="form-label font-semibold">Course Name *</label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name" placeholder="e.g. Database Systems" required>
                            </div>

                            <div class="mb-3">
                                <label for="department_id" class="form-label font-semibold">Department</label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    <?php if ($departments && $departments->num_rows > 0): ?>
                                        <?php while ($d = $departments->fetch_assoc()): ?>
                                            <option value="<?php echo $d['department_id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="semester_id" class="form-label font-semibold">Semester</label>
                                <select class="form-select" id="semester_id" name="semester_id" required>
                                    <?php if ($semesters && $semesters->num_rows > 0): ?>
                                        <?php while ($sem = $semesters->fetch_assoc()): ?>
                                            <option value="<?php echo $sem['semester_id']; ?>"><?php echo htmlspecialchars($sem['semester_name']); ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <button type="submit" id="submitBtn" class="btn btn-primary-custom w-100 py-2.5 rounded-3 fw-bold">
                                <i class="fas fa-plus me-1"></i> Save Course
                            </button>
                            <button type="button" id="cancelBtn" onclick="resetCourseForm()" class="btn btn-outline-secondary w-100 py-2 mt-2 rounded-3 d-none">
                                Cancel Edit
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Course Directory Table -->
            <div class="col-lg-8">
                <div class="custom-table-container">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <span class="fw-bold"><i class="fas fa-book text-primary me-2"></i> Course Catalog (<?php echo $courses ? $courses->num_rows : 0; ?> Total)</span>
                        <div style="max-width: 280px;" class="w-100">
                            <input type="text" class="form-control form-control-sm" placeholder="Search course..." data-table-search="coursesTable">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table custom-table" id="coursesTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Course Title</th>
                                    <th>Department</th>
                                    <th>Semester</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($courses && $courses->num_rows > 0): ?>
                                    <?php while ($c = $courses->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace text-primary"><?php echo htmlspecialchars($c['subject_code']); ?></td>
                                            <td class="fw-bold text-main"><?php echo htmlspecialchars($c['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($c['department_name'] ?? 'General'); ?></td>
                                            <td><?php echo htmlspecialchars($c['semester_name'] ?? 'All'); ?></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-2.5" 
                                                            onclick="editCourse(<?php echo htmlspecialchars(json_encode($c)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="manage_courses.php?delete=<?php echo $c['subject_id']; ?>" 
                                                       class="btn btn-outline-danger btn-sm rounded-pill px-2.5"
                                                       onclick="return confirm('Delete course <?php echo htmlspecialchars(addslashes($c['subject_code'])); ?>?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No courses found. Add one above!</td>
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
        function editCourse(item) {
            $('#formAction').val('edit');
            $('#subjectId').val(item.subject_id);
            $('#subject_code').val(item.subject_code);
            $('#subject_name').val(item.subject_name);
            if (item.department_id) $('#department_id').val(item.department_id);
            if (item.semester_id) $('#semester_id').val(item.semester_id);

            $('#formTitle').html('<i class="fas fa-edit me-2"></i> Edit Course Details');
            $('#submitBtn').html('<i class="fas fa-save me-1"></i> Update Course');
            $('#cancelBtn').removeClass('d-none');
        }

        function resetCourseForm() {
            $('#formAction').val('add');
            $('#subjectId').val('0');
            $('#courseForm')[0].reset();
            $('#formTitle').html('<i class="fas fa-plus-circle me-2"></i> Add New Academic Course');
            $('#submitBtn').html('<i class="fas fa-plus me-1"></i> Save Course');
            $('#cancelBtn').addClass('d-none');
        }
    </script>
</body>
</html>
