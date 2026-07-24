<?php
// teacher/generate_qr.php - Live QR Code Broadcasting View
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
$query = "SELECT sess.*, s.subject_name, t.full_name as teacher_name 
          FROM attendance_sessions sess
          JOIN subjects s ON sess.subject_id = s.subject_id
          JOIN teachers t ON sess.teacher_id = t.teacher_id
          WHERE sess.session_id = ? AND sess.teacher_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $session_id, $teacher_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    redirect('dashboard.php');
}

// Check if dynamic session code exists or if teacher requested a fresh code refresh
if (empty($session['session_code']) || isset($_GET['refresh_code'])) {
    $session_code = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    $qr_data = $session_code . '|' . time();
    $update_stmt = $db->prepare("UPDATE attendance_sessions SET session_code = ?, qr_code = ? WHERE session_id = ? AND teacher_id = ?");
    $update_stmt->bind_param("ssii", $session_code, $qr_data, $session_id, $teacher_id);
    $update_stmt->execute();
    $session['session_code'] = $session_code;
    $session['qr_code'] = $qr_data;
} else {
    $session_code = $session['session_code'];
    $qr_data = !empty($session['qr_code']) ? $session['qr_code'] : ($session_code . '|' . time());
}

$qr_image_url = generateQRCode($qr_data, 340);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live QR Broadcast - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <script src="../assets/js/qrious.min.js"></script>
    <style>
        .qr-display-box {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .session-code-display {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: 0.5rem;
            font-family: 'Courier New', monospace;
            color: var(--primary);
            background: var(--primary-light);
            border: 2px dashed var(--primary);
            padding: 8px 24px;
            border-radius: 16px;
            display: inline-block;
        }
    </style>
</head>
<body style="--primary: var(--success); --primary-hover: #059669; --primary-light: var(--success-light);">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="dashboard.php">
                <div class="brand-icon" style="background: var(--success); color: white;"><i class="fas fa-qrcode"></i></div>
                <span>Live Session Broadcast</span>
            </a>
            
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="dashboard.php" class="btn btn-outline-success btn-sm rounded-pill px-3">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4" style="max-width: 1300px;">
        <div class="row g-4">
            <!-- QR & Code Projector View Column -->
            <div class="col-lg-5">
                <div class="glass-card text-center p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge badge-custom badge-present">
                            <i class="fas fa-signal me-1"></i> Live Session Active
                        </span>
                        <span id="timer" class="badge badge-custom badge-warning fs-6">⏱️ Initializing...</span>
                    </div>

                    <h4 class="fw-bold mb-1 text-main"><?php echo htmlspecialchars($session['subject_name']); ?></h4>
                    <p class="text-muted small mb-3">Instruct students to scan QR or enter 4-digit code below</p>

                    <!-- Large QR Code Display -->
                    <div class="qr-display-box my-3" style="min-height: 280px; display: flex; align-items: center; justify-content: center;">
                        <img id="qrImage" src="<?php echo $qr_image_url; ?>" alt="Attendance QR Code" class="img-fluid" style="max-width:280px;" onerror="switchToLocalQR()">
                        <canvas id="qrCanvas" style="display: none; max-width: 280px;"></canvas>
                    </div>

                    <!-- 4-Digit Manual Code Banner -->
                    <div class="my-3">
                        <div class="text-muted small fw-bold uppercase mb-1">4-Digit Session Code</div>
                        <div class="session-code-display" id="sessionCodeDisplay"><?php echo $session_code; ?></div>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <button onclick="copyCode()" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="fas fa-copy me-1"></i> Copy Code
                        </button>
                        <a href="generate_qr.php?session_id=<?php echo $session_id; ?>&refresh_code=1" class="btn btn-outline-success btn-sm rounded-pill px-3">
                            <i class="fas fa-sync me-1"></i> Refresh New Code & QR
                        </a>
                    </div>
                </div>
            </div>

            <!-- Real-Time Live Attendance Counter & Feed Column -->
            <div class="col-lg-7">
                <div class="glass-card h-100 d-flex flex-column">
                    <div class="glass-card-header bg-body border-bottom p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="fw-bold text-main"><i class="fas fa-users text-primary me-2"></i> Live Check-in Feed</span>
                            <span class="badge badge-custom badge-pending ms-2"><span id="markedCount">0</span> Students Checked In</span>
                        </div>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#manualMarkModal" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold">
                            <i class="fas fa-user-check me-1"></i> Manual Mark Student
                        </button>
                    </div>

                    <div class="custom-table-container border-0 flex-grow-1">
                        <div class="table-responsive">
                            <table class="table custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Roll Number</th>
                                        <th>Student Name</th>
                                        <th>Check-in Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="attendanceBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <div class="spinner-border text-primary spinner-border-sm me-2"></div>
                                            Connecting to live attendance stream...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
        let currentQrData = '';

        function updateQRCode(qrData) {
            if (qrData === currentQrData) return;
            currentQrData = qrData;
            
            const img = document.getElementById("qrImage");
            if (img) img.style.display = "none";
            const canvas = document.getElementById("qrCanvas");
            if (canvas) {
                canvas.style.display = "inline-block";
                new QRious({
                    element: canvas,
                    value: qrData,
                    size: 280
                });
            }
        }

        function switchToLocalQR() {
            console.warn("Online QR API failed to load. Switching to local offline QRious generator.");
            const qrData = "<?php echo $qr_data; ?>";
            updateQRCode(qrData);
        }

        const sessionId = <?php echo $session_id; ?>;
        const sessionCode = '<?php echo $session_code; ?>';
        const durationMinutes = <?php echo $session['duration_minutes']; ?>;
        let lastMarkedCount = -1;

        // Timer countdown
        function startTimer() {
            const createdTime = <?php echo time(); ?>;
            const expiryTime = createdTime + (durationMinutes * 60);
            
            setInterval(function() {
                const now = Math.floor(Date.now() / 1000);
                const remaining = expiryTime - now;
                
                if (remaining <= 0) {
                    document.getElementById('timer').textContent = '⛔ EXPIRED';
                    document.getElementById('timer').className = 'badge badge-custom badge-absent fs-6';
                    return;
                }
                
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                document.getElementById('timer').textContent = `⏱️ ${minutes}m ${seconds}s remaining`;
            }, 1000);
        }
        startTimer();

        // Real-time AJAX poll for live check-ins
        function pollAttendance() {
            $.ajax({
                url: 'fetch_live_attendance.php',
                type: 'GET',
                data: { session_id: sessionId },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        // Refresh rolling QR code dynamically
                        if (data.qr_data) {
                            updateQRCode(data.qr_data);
                        }
                        
                        const count = data.marked || 0;
                        
                        // Play chime sound and toast details on new student scan
                        if (lastMarkedCount !== -1 && count > lastMarkedCount) {
                            if (window.playSuccessChime) window.playSuccessChime();
                            
                            // Find the newly checked-in student (first entry since list is ordered DESC)
                            if (data.attendance && data.attendance.length > 0) {
                                const newStudent = data.attendance[0];
                                showToast(`Check-in: ${newStudent.student_name} (${newStudent.roll_number}) at ${newStudent.time}`, 'success');
                            } else {
                                showToast('New student check-in recorded!', 'success');
                            }
                        }
                        lastMarkedCount = count;

                        document.getElementById('markedCount').textContent = count;

                        let html = '';
                        if (data.attendance && data.attendance.length > 0) {
                            data.attendance.forEach(item => {
                                html += `<tr>
                                    <td class="fw-bold font-monospace">${item.roll_number}</td>
                                    <td class="fw-bold text-main">${item.student_name}</td>
                                    <td class="text-muted"><i class="fas fa-clock me-1"></i> ${item.time}</td>
                                    <td><span class="badge badge-custom badge-present"><i class="fas fa-check"></i> Present</span></td>
                                </tr>`;
                            });
                        } else {
                            html = `<tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-qrcode fa-2x mb-2 d-block opacity-50"></i>
                                    Waiting for students to scan QR or enter 4-digit code...
                                </td>
                            </tr>`;
                        }
                        document.getElementById('attendanceBody').innerHTML = html;
                    }
                }
            });
        }

        pollAttendance();
        setInterval(pollAttendance, 3000);

        function copyCode() {
            navigator.clipboard.writeText(sessionCode).then(() => {
                showToast(`Session Code ${sessionCode} copied to clipboard!`, 'info');
            });
        }

        $('#manualMarkForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#manualMarkBtn');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Recording...');

            $.ajax({
                url: 'manual_mark_student.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Attendance');
                    if (res.success) {
                        showToast(res.message, 'success');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('manualMarkModal'));
                        if (modal) modal.hide();
                        pollAttendance();
                    } else {
                        showToast(res.message || 'Failed to mark student.', 'danger');
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Attendance');
                    showToast('Server error while recording attendance.', 'danger');
                }
            });
        });
    </script>

    <!-- Manual Mark Student Attendance Modal -->
    <div class="modal fade" id="manualMarkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 p-3">
                <div class="modal-header border-bottom pb-2">
                    <h5 class="modal-title fw-bold text-main"><i class="fas fa-user-check text-warning me-2"></i> Manual Student Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="manualMarkForm">
                    <div class="modal-body py-3">
                        <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">

                        <div class="mb-3">
                            <label class="form-label font-semibold">Select Student *</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">-- Choose Student from Roster --</option>
                                <?php
                                $students_list = $db->query("SELECT student_id, roll_number, full_name FROM students WHERE is_approved = 1 ORDER BY roll_number, full_name");
                                if ($students_list && $students_list->num_rows > 0) {
                                    while ($st = $students_list->fetch_assoc()) {
                                        echo "<option value='{$st['student_id']}'>[" . htmlspecialchars($st['roll_number']) . "] " . htmlspecialchars($st['full_name']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-semibold">Attendance Status *</label>
                            <select class="form-select" name="status" required>
                                <option value="Present" selected>Present</option>
                                <option value="Late">Late</option>
                                <option value="Absent">Absent</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top pt-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="manualMarkBtn" class="btn btn-warning btn-sm rounded-pill px-4 fw-bold">
                            <i class="fas fa-check me-1"></i> Save Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>