<?php
// teacher/create_attendance.php - Dynamic Cascading Department, Semester, Batch & Subject Session Creator
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    redirect('../index.php');
}

$teacher_id = $_SESSION['teacher_id'];
$db = getDB();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = intval($_POST['department_id'] ?? 1);
    $semester_id = intval($_POST['semester_id'] ?? 1);
    $batch_id = intval($_POST['batch_id'] ?? 1);
    $section_id = intval($_POST['section_id'] ?? 1);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $session_date = $_POST['session_date'] ?? date('Y-m-d');
    $start_time = $_POST['start_time'] ?? date('H:i');
    $duration_minutes = intval($_POST['duration_minutes'] ?? 10);
    $end_time = date('H:i:s', strtotime("$start_time + $duration_minutes minutes"));
    
    if ($subject_id <= 0) {
        $message = 'Please select a valid Subject / Course for this session.';
        $message_type = 'danger';
    } else {
        // Generate unique random 4-digit session code and QR code data
        $session_code = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $qr_data = $session_code . '|' . time();
        
        $query = "INSERT INTO attendance_sessions 
                  (teacher_id, subject_id, semester_id, section_id, batch_id, 
                   session_date, start_time, end_time, duration_minutes, qr_code, session_code, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("iiiiisssiss", 
            $teacher_id, $subject_id, $semester_id, $section_id, $batch_id,
            $session_date, $start_time, $end_time, $duration_minutes, $qr_data, $session_code
        );
        
        if ($stmt->execute()) {
            $session_id = $db->insert_id;
            header("Location: generate_qr.php?session_id=$session_id&success=1");
            exit;
        } else {
            $message = 'Failed to create session: ' . $db->error;
            $message_type = 'danger';
        }
    }
}

// Fetch dropdown data
$departments = $db->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
$semesters = $db->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
$batches = $db->query("SELECT batch_id, batch_year FROM batches ORDER BY batch_year DESC");
$sections = $db->query("SELECT section_id, section_name FROM sections ORDER BY section_name");

// Fetch initial subjects
$all_subjects = $db->query("SELECT subject_id, subject_name, subject_code, department_id, semester_id FROM subjects ORDER BY subject_code, subject_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Attendance Session - <?php echo APP_NAME; ?></title>
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
                    <i class="fas fa-plus-circle"></i>
                </div>
                <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.95rem;">New Session <span class="badge bg-success-subtle text-success border border-success-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Faculty</span></span>
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

    <div class="container py-4" style="max-width: 860px;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="glass-card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="glass-card-header text-white text-center py-4 d-block" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="brand-icon mx-auto mb-2" style="width:52px; height:52px; font-size:1.5rem; background:rgba(255,255,255,0.2); border-radius:14px;">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h4 class="fw-extrabold mb-1" style="letter-spacing:-0.5px;">Create Class Attendance Session</h4>
                <small class="text-white-50 font-semibold">Filter Department & Semester to generate dynamic class QR code</small>
            </div>

            <div class="glass-card-body p-4 p-md-5">
                <form method="POST" autocomplete="off">
                    
                    <div class="row g-3 mb-3">
                        <!-- Department Filter -->
                        <div class="col-md-6">
                            <label for="department_id" class="form-label font-semibold text-main small"><i class="fas fa-building text-success me-1"></i> 1. Select Department *</label>
                            <select class="form-select rounded-3 py-2" id="department_id" name="department_id" required>
                                <option value="">-- Choose Department --</option>
                                <?php if ($departments && $departments->num_rows > 0): ?>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['department_id']; ?>">
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Semester Filter -->
                        <div class="col-md-6">
                            <label for="semester_id" class="form-label font-semibold text-main small"><i class="fas fa-layer-group text-primary me-1"></i> 2. Select Semester *</label>
                            <select class="form-select rounded-3 py-2" id="semester_id" name="semester_id" required>
                                <option value="">-- Choose Semester --</option>
                                <?php if ($semesters && $semesters->num_rows > 0): ?>
                                    <?php while ($sem = $semesters->fetch_assoc()): ?>
                                        <option value="<?php echo $sem['semester_id']; ?>">
                                            <?php echo htmlspecialchars($sem['semester_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Batch Filter -->
                        <div class="col-md-6">
                            <label for="batch_id" class="form-label font-semibold text-main small"><i class="fas fa-graduation-cap text-warning me-1"></i> 3. Select Batch *</label>
                            <select class="form-select rounded-3 py-2" id="batch_id" name="batch_id" required>
                                <?php if ($batches && $batches->num_rows > 0): ?>
                                    <?php while ($b = $batches->fetch_assoc()): ?>
                                        <option value="<?php echo $b['batch_id']; ?>">
                                            Batch <?php echo htmlspecialchars($b['batch_year']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Section Filter -->
                        <div class="col-md-6">
                            <label for="section_id" class="form-label font-semibold text-main small"><i class="fas fa-users-class text-info me-1"></i> 4. Select Section *</label>
                            <select class="form-select rounded-3 py-2" id="section_id" name="section_id" required>
                                <?php if ($sections && $sections->num_rows > 0): ?>
                                    <?php while ($sec = $sections->fetch_assoc()): ?>
                                        <option value="<?php echo $sec['section_id']; ?>">
                                            Section <?php echo htmlspecialchars($sec['section_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Cascading Dynamic Subject Selection -->
                    <div class="mb-4">
                        <label for="subject_id" class="form-label font-semibold text-main small">
                            <i class="fas fa-book text-success me-1"></i> 5. Select Course / Subject *
                            <span class="badge bg-secondary-subtle text-secondary ms-2 small" id="subjectFilterBadge">Filtered by Department</span>
                        </label>
                        <select class="form-select form-select-lg rounded-3 border-2 py-2.5" id="subject_id" name="subject_id" required>
                            <option value="">-- Choose Course / Subject --</option>
                            <?php if ($all_subjects && $all_subjects->num_rows > 0): ?>
                                <?php while ($sub = $all_subjects->fetch_assoc()): ?>
                                    <option value="<?php echo $sub['subject_id']; ?>" data-dept="<?php echo $sub['department_id']; ?>" data-sem="<?php echo $sub['semester_id']; ?>">
                                        [<?php echo htmlspecialchars($sub['subject_code']); ?>] <?php echo htmlspecialchars($sub['subject_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="session_date" class="form-label font-semibold text-main small">Session Date</label>
                            <input type="date" class="form-control rounded-3 py-2" id="session_date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="start_time" class="form-label font-semibold text-main small">Start Time</label>
                            <input type="time" class="form-control rounded-3 py-2" id="start_time" name="start_time" value="<?php echo date('H:i'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="duration_minutes" class="form-label font-semibold text-main small">Session Validity Duration</label>
                        <select class="form-select rounded-3 py-2" id="duration_minutes" name="duration_minutes">
                            <option value="5">5 Minutes (Quick Roll Call)</option>
                            <option value="10" selected>10 Minutes</option>
                            <option value="15">15 Minutes</option>
                            <option value="30">30 Minutes</option>
                            <option value="60">60 Minutes</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-success py-3 fs-6 rounded-pill font-bold shadow" style="background: linear-gradient(135deg, #10b981, #059669); border:none;">
                            <i class="fas fa-qrcode me-2"></i> Launch Session & Broadcast Dynamic QR Code
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
        // Dynamic Cascading Filter for Subjects based on Department & Semester selection
        function filterSubjects() {
            const deptId = $('#department_id').val() || 0;
            const semId = $('#semester_id').val() || 0;

            $.ajax({
                url: 'fetch_courses_by_dept.php',
                type: 'GET',
                data: { department_id: deptId, semester_id: semId },
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.courses && res.courses.length > 0) {
                        let options = '<option value="">-- Choose Course / Subject --</option>';
                        res.courses.forEach(c => {
                            options += `<option value="${c.subject_id}">[${c.subject_code}] ${c.subject_name}</option>`;
                        });
                        $('#subject_id').html(options);
                        $('#subjectFilterBadge').text(`Found ${res.count} course(s)`).removeClass('bg-secondary-subtle').addClass('bg-success-subtle text-success');
                    } else {
                        $('#subject_id').html('<option value="">-- No courses found for selected filter --</option>');
                        $('#subjectFilterBadge').text(`0 courses found`).removeClass('bg-success-subtle').addClass('bg-warning-subtle text-warning');
                    }
                },
                error: function() {
                    console.log('Error fetching courses');
                }
            });
        }

        $('#department_id, #semester_id').on('change', filterSubjects);
        $(document).ready(function() {
            filterSubjects();
        });
    </script>
</body>
</html>