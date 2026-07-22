<?php
// student/register.php - Complete Modern Student Registration Page
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    redirect('../index.php');
}

$db = getDB();

// Fetch dropdown data from DB
$departments = $db->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
$batches = $db->query("SELECT batch_id, batch_year FROM batches ORDER BY batch_year DESC");
$semesters = $db->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
$sections = $db->query("SELECT section_id, section_name FROM sections ORDER BY section_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 10%, rgba(79, 70, 229, 0.15), transparent 60%), var(--bg-body);
            padding: 2.5rem 1rem;
        }

        .register-card {
            width: 100%;
            max-width: 860px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .register-body {
            padding: 2.5rem 2.25rem;
        }

        .roll-prefix-badge {
            background-color: var(--bg-body);
            border-color: var(--border-color);
            color: var(--primary);
            font-weight: 700;
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
        }

        .success-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.85);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(8px);
        }

        .success-overlay.show { display: flex; }

        .success-card {
            background: var(--bg-surface);
            padding: 40px;
            border-radius: 24px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.35s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Registration Success Modal Overlay -->
    <div class="success-overlay" id="successOverlay">
        <div class="success-card">
            <div class="text-success fs-1 mb-3"><i class="fas fa-check-circle fa-2x"></i></div>
            <h3 class="fw-bold mb-2">Registration Successful!</h3>
            <p id="successMessage" class="text-muted small">Your account profile has been created successfully. Redirecting to login portal...</p>
            <div class="spinner-border text-primary my-3"></div>
            <p class="text-muted small mb-0">Please wait a moment...</p>
        </div>
    </div>

    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <button class="btn-theme-toggle position-absolute top-0 end-0 m-3" onclick="toggleAppTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="brand-icon mx-auto mb-3" style="width: 58px; height: 58px; font-size: 1.75rem; background: rgba(255,255,255,0.2);">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3 class="fw-bold mb-1">Student Account Registration</h3>
                <p class="mb-0 text-white-50 small">Create your academic attendance profile securely</p>
            </div>

            <!-- Body Form -->
            <div class="register-body">
                <div id="alertContainer"></div>

                <form id="registerForm" autocomplete="off">
                    <div class="row g-3">
                        
                        <!-- Username -->
                        <div class="col-md-6">
                            <label for="username" class="form-label font-semibold">Username *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-end-0 text-muted"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control border-start-0" id="username" name="username" placeholder="e.g. john_doe" required>
                            </div>
                            <div class="form-text small">Used for portal login</div>
                        </div>

                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label for="full_name" class="form-label font-semibold">Full Name *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-end-0 text-muted"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control border-start-0" id="full_name" name="full_name" placeholder="Full legal name" required>
                            </div>
                        </div>

                        <!-- Department -->
                        <div class="col-md-6">
                            <label for="department_id" class="form-label font-semibold">Department *</label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">-- Select Department --</option>
                                <?php if ($departments && $departments->num_rows > 0): ?>
                                    <?php while ($d = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $d['department_id']; ?>" data-name="<?php echo htmlspecialchars($d['department_name']); ?>">
                                            <?php echo htmlspecialchars($d['department_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Batch -->
                        <div class="col-md-6">
                            <label for="batch_id" class="form-label font-semibold">Batch Year *</label>
                            <select class="form-select" id="batch_id" name="batch_id" required>
                                <option value="">-- Select Batch --</option>
                                <?php if ($batches && $batches->num_rows > 0): ?>
                                    <?php while ($b = $batches->fetch_assoc()): ?>
                                        <option value="<?php echo $b['batch_id']; ?>" data-year="<?php echo htmlspecialchars($b['batch_year']); ?>">
                                            Batch <?php echo htmlspecialchars($b['batch_year']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Roll Number -->
                        <div class="col-md-6">
                            <label for="roll_number" class="form-label font-semibold">Roll Number *</label>
                            <div class="input-group">
                                <span class="input-group-text roll-prefix-badge" id="rollPrefixAddon">2025-SE-</span>
                                <input type="text" class="form-control font-monospace" id="roll_number_num" placeholder="001" required>
                            </div>
                            <input type="hidden" id="finalRollNumber" name="roll_number">
                            <div class="form-text small">Enter sequence number (e.g. 001, 042)</div>
                        </div>

                        <!-- University Email -->
                        <div class="col-md-6">
                            <label for="email" class="form-label font-semibold">University Email *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-end-0 text-muted"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control border-start-0" id="email" name="email" placeholder="student@university.edu" required>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6">
                            <label for="password" class="form-label font-semibold">Password *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" placeholder="Min 6 characters" minlength="6" required>
                                <button class="btn btn-outline-secondary border-start-0 toggle-pass" type="button" data-target="password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label font-semibold">Confirm Password *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-end-0 text-muted"><i class="fas fa-shield-alt"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0" id="confirm_password" name="confirm_password" minlength="6" required>
                                <button class="btn btn-outline-secondary border-start-0 toggle-pass" type="button" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Semester -->
                        <div class="col-md-6">
                            <label for="semester_id" class="form-label font-semibold">Semester *</label>
                            <select class="form-select" id="semester_id" name="semester_id" required>
                                <option value="">-- Select Semester --</option>
                                <?php if ($semesters && $semesters->num_rows > 0): ?>
                                    <?php while ($sem = $semesters->fetch_assoc()): ?>
                                        <option value="<?php echo $sem['semester_id']; ?>">
                                            <?php echo htmlspecialchars($sem['semester_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Section -->
                        <div class="col-md-6">
                            <label for="section_id" class="form-label font-semibold">Section *</label>
                            <select class="form-select" id="section_id" name="section_id" required>
                                <option value="">-- Select Section --</option>
                                <?php if ($sections && $sections->num_rows > 0): ?>
                                    <?php while ($sec = $sections->fetch_assoc()): ?>
                                        <option value="<?php echo $sec['section_id']; ?>">
                                            Section <?php echo htmlspecialchars($sec['section_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="phone" class="form-label font-semibold">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-end-0 text-muted"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control border-start-0" id="phone" name="phone" placeholder="03001234567">
                            </div>
                        </div>

                        <!-- Device Identifier Lock -->
                        <div class="col-md-6">
                            <label for="deviceId" class="form-label font-semibold">Device Lock ID (Hardware)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-end-0 text-muted"><i class="fas fa-mobile-alt"></i></span>
                                <input type="text" class="form-control border-start-0 font-monospace text-muted" id="deviceId" name="device_id" readonly>
                            </div>
                        </div>

                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2 mt-4 pt-2">
                        <button type="submit" class="btn btn-primary-custom py-3 fs-6 rounded-3 fw-bold" id="submitBtn">
                            <i class="fas fa-user-plus me-2"></i> Register Academic Profile
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-secondary-subtle d-flex flex-wrap justify-content-center gap-4">
                    <span class="text-muted small">Already have an account? <a href="login.php" class="fw-bold text-primary text-decoration-none ms-1">Login here</a></span>
                    <a href="../index.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Return to Main Menu</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Bulletproof Mobile Device Identifier Generator
        function getDeviceId() {
            let deviceId = '';
            try {
                deviceId = localStorage.getItem('student_device_id');
                if (!deviceId) {
                    deviceId = 'DEV-' + Math.random().toString(36).substring(2, 10) + '-' + Date.now().toString(36);
                    localStorage.setItem('student_device_id', deviceId);
                }
            } catch(e) {
                deviceId = 'MOB-' + Math.random().toString(36).substring(2, 12);
            }
            return deviceId;
        }

        $(document).ready(function() {
            const devId = getDeviceId();
            $('#deviceId').val(devId);
        });

        // Password visibility toggles
        document.querySelectorAll('.toggle-pass').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
        });

        // Dynamic Roll Number Prefix Update
        function updateRollPrefix() {
            const batchYear = $('#batch_id option:selected').data('year') || '2025';
            const deptName = $('#department_id option:selected').data('name') || '';
            let deptCode = 'SE';
            if (deptName.includes('Computer Science')) deptCode = 'CS';
            if (deptName.includes('Information Technology') || deptName.includes('IT')) deptCode = 'IT';
            
            const prefix = `${batchYear}-${deptCode}-`;
            $('#rollPrefixAddon').text(prefix);

            const num = $('#roll_number_num').val().trim();
            $('#finalRollNumber').val(prefix + num);
        }

        $('#batch_id, #department_id').on('change', updateRollPrefix);
        $('#roll_number_num').on('input', function() {
            this.value = this.value.replace(/[^0-9A-Za-z]/g, '');
            updateRollPrefix();
        });
        updateRollPrefix();

        // Submit via AJAX to register_process.php
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            updateRollPrefix();

            const btn = $('#submitBtn');
            const alertBox = $('#alertContainer');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Processing registration...');
            alertBox.html('');

            $.ajax({
                url: 'register_process.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#successMessage').text(res.message || 'Your account profile has been created successfully. Redirecting to login portal...');
                        $('#successOverlay').addClass('show');
                        setTimeout(function() {
                            window.location.href = 'login.php?registered=1';
                        }, 2000);
                    } else {
                        alertBox.html(`
                            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>${res.message || 'Registration failed. Please try again.'}</div>
                            </div>
                        `);
                        btn.prop('disabled', false).html('<i class="fas fa-user-plus me-2"></i> Register Academic Profile');
                    }
                },
                error: function() {
                    alertBox.html(`<div class="alert alert-danger mb-3">System processing error. Please try again.</div>`);
                    btn.prop('disabled', false).html('<i class="fas fa-user-plus me-2"></i> Register Academic Profile');
                }
            });
        });
    </script>
</body>
</html>