<?php
// student/login.php - Student Auth Screen
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student') {
    redirect('dashboard.php');
}

$success_msg = isset($_GET['registered']) ? 'Registration successful! Please login with your credentials.' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 10%, rgba(79, 70, 229, 0.15), transparent 70%), var(--bg-body);
            padding: 2rem 1rem;
            position: relative;
        }

        .auth-nav-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 1.25rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            box-shadow: var(--shadow-lg), var(--shadow-primary);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .auth-header-badge {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.12), rgba(14, 165, 233, 0.08));
            padding: 2rem 1.75rem 1.25rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .student-avatar-box {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #4f46e5, #0ea5e9);
            color: #ffffff;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.35);
            margin: 0 auto 1.25rem;
            transition: transform 0.3s ease;
        }

        .auth-card:hover .student-avatar-box {
            transform: scale(1.08) rotate(-3deg);
        }

        .auth-body {
            padding: 2rem 1.85rem;
        }

        .custom-input-group .input-group-text {
            background-color: var(--bg-body);
            border-color: var(--border-color);
            color: var(--text-muted);
            border-radius: 14px 0 0 14px;
            padding-left: 1.1rem;
        }

        .custom-input-group .form-control {
            border-radius: 0 14px 14px 0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }

        .custom-input-group .form-control-with-btn {
            border-radius: 0;
        }

        .custom-input-group .btn-eye {
            border-radius: 0 14px 14px 0;
            border-color: var(--border-color);
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        
        <!-- Top Sticky Header -->
        <header class="auth-nav-header">
            <a href="../index.php" class="d-flex align-items-center gap-2.5 text-decoration-none">
                <div class="brand-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; box-shadow: 0 4px 12px rgba(239,68,68,0.35);">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <span class="fw-bold tracking-tight text-main" style="font-size: 0.95rem;"><?php echo APP_NAME; ?></span>
            </a>

            <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Light/Dark Theme">
                <i class="fas fa-moon"></i>
            </button>
        </header>

        <!-- Student Login Card -->
        <div class="auth-card animate-slide-up mt-5 mt-md-0">
            <div class="auth-header-badge">
                <div class="student-avatar-box">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 class="fw-extrabold text-main mb-1" style="letter-spacing: -0.5px;">Student Portal</h3>
                <p class="text-muted small mb-0">Scan QR codes, enter security PINs & track attendance</p>
            </div>
            
            <div class="auth-body">
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 rounded-4 mb-4" role="alert">
                        <i class="fas fa-check-circle fa-lg"></i>
                        <div class="small fw-semibold"><?php echo htmlspecialchars($success_msg); ?></div>
                    </div>
                <?php endif; ?>

                <div id="alertContainer"></div>

                <form id="loginForm" autocomplete="off">
                    <input type="hidden" id="deviceId" name="device_id" value="">

                    <div class="mb-3">
                        <label for="username" class="form-label font-semibold text-main small">Username or Email</label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="e.g. student or student@gmail.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label font-semibold text-main small mb-0">Password</label>
                            <a href="#" class="small text-primary text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" style="font-size: 0.8rem;">Forgot Password?</a>
                        </div>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control form-control-with-btn" id="password" name="password" placeholder="Enter your password" required>
                            <button class="btn btn-outline-secondary btn-eye" type="button" id="togglePassword" title="Show/Hide Password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary-custom py-2.5 rounded-pill font-semibold shadow-sm" id="submitBtn">
                            <i class="fas fa-sign-in-alt me-2"></i> Log In to Dashboard
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-light-subtle">
                    <p class="small text-muted mb-2.5">New to the portal?</p>
                    <a href="register.php" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold">
                        <i class="fas fa-user-plus me-1.5"></i> Register New Student Account
                    </a>
                </div>

                <div class="text-center mt-3">
                    <a href="../index.php" class="small text-muted text-decoration-none font-semibold hover-primary">
                        <i class="fas fa-arrow-left me-1.5"></i> Back to Main Portal Gateway
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 p-3">
                <div class="modal-header border-bottom pb-2">
                    <h5 class="modal-title fw-bold text-main" id="forgotPasswordModalLabel">
                        <i class="fas fa-key text-primary me-2"></i> Account Recovery Help
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4 text-center">
                    <div class="brand-icon mx-auto mb-3" style="width: 64px; height: 64px; font-size: 2rem; background: var(--primary-light); color: var(--primary);">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h5 class="fw-bold mb-2">🔒 Secure Admin-Mediated Reset</h5>
                    <p class="text-muted small mb-0">
                        Since this attendance portal operates on a secure local intranet network without outbound internet access, automated email password reset is disabled.
                    </p>
                    <div class="bg-body p-3 rounded-4 border text-start mt-3">
                        <div class="fw-semibold text-main mb-1"><i class="fas fa-info-circle me-1 text-primary"></i> What should you do?</div>
                        <p class="small text-muted mb-0">
                            Please contact your <strong>Department Administrator</strong> or <strong>Course Instructor</strong>. They can instantly reset your password in the Admin Control Panel after verifying your Roll Number.
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-top pt-2">
                    <button type="button" class="btn btn-primary-custom btn-sm rounded-pill px-4 fw-bold w-100" data-bs-dismiss="modal">
                        Understood
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Generate or retrieve persistent Device Identifier
        function getDeviceId() {
            let deviceId = localStorage.getItem('student_device_id');
            if (!deviceId) {
                deviceId = 'DEV-' + Math.random().toString(36).substring(2, 11) + '-' + Date.now();
                localStorage.setItem('student_device_id', deviceId);
            }
            return deviceId;
        }
        document.getElementById('deviceId').value = getDeviceId();

        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });

        // AJAX Form Submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const alertBox = document.getElementById('alertContainer');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Authenticating...';
            alertBox.innerHTML = '';

            const formData = new FormData(this);

            fetch('login_process.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Login successful! Redirecting...', 'success');
                    window.location.href = data.redirect || 'dashboard.php';
                } else {
                    alertBox.innerHTML = `
                        <div class="alert alert-danger d-flex align-items-center gap-2 rounded-4 mb-3">
                            <i class="fas fa-exclamation-circle fa-lg"></i>
                            <div class="small fw-semibold">${data.message || 'Login failed. Please check credentials.'}</div>
                        </div>
                    `;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Log In to Dashboard';
                }
            })
            .catch(err => {
                alertBox.innerHTML = `
                    <div class="alert alert-danger rounded-4 mb-3 small fw-semibold">Connection error. Please try again.</div>
                `;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Log In to Dashboard';
            });
        });
    </script>
</body>
</html>