<?php
// teacher/login.php - Teacher Auth Screen
require_once '../config.php';

if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'teacher') {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Portal Login - <?php echo APP_NAME; ?></title>
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
            background: radial-gradient(circle at 50% 10%, rgba(16, 185, 129, 0.15), transparent 70%), var(--bg-body);
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
            box-shadow: var(--shadow-lg), 0 10px 30px rgba(16, 185, 129, 0.15);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .auth-header-badge {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(14, 165, 233, 0.08));
            padding: 2rem 1.75rem 1.25rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .teacher-avatar-box {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.35);
            margin: 0 auto 1.25rem;
            transition: transform 0.3s ease;
        }

        .auth-card:hover .teacher-avatar-box {
            transform: scale(1.08) rotate(3deg);
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

        .btn-success-custom {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            border: none;
            border-radius: 50rem;
            padding: 0.65rem 1.25rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }

        .btn-success-custom:hover {
            background: linear-gradient(135deg, #059669, #047857);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>
<body style="background: radial-gradient(circle at 50% 10%, rgba(16, 185, 129, 0.15), transparent 70%), var(--bg-body); min-height: 100vh;">
    <div class="auth-container">
        
        <!-- Top Sticky Header -->
        <header class="auth-nav-header">
            <a href="../index.php" class="d-flex align-items-center gap-2.5 text-decoration-none">
                <div class="brand-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; box-shadow: 0 4px 12px rgba(16,185,129,0.35);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <span class="fw-bold tracking-tight text-main" style="font-size: 0.95rem;"><?php echo APP_NAME; ?> <span class="badge bg-success-subtle text-success border border-success-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Faculty</span></span>
            </a>

            <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Light/Dark Theme">
                <i class="fas fa-moon"></i>
            </button>
        </header>

        <!-- Teacher Login Card -->
        <div class="auth-card animate-slide-up mt-5 mt-md-0">
            <div class="auth-header-badge">
                <div class="teacher-avatar-box" style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 8px 25px rgba(16, 185, 129, 0.35);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3 class="fw-extrabold text-main mb-1" style="letter-spacing: -0.5px;">Faculty Portal</h3>
                <p class="text-muted small mb-0">Broadcast QR sessions, track live check-ins & export rosters</p>
            </div>
            
            <div class="auth-body">
                <div id="alertContainer"></div>

                <form id="loginForm" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label font-semibold text-main small">Email or Username</label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="e.g. teacher@university.edu" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label font-semibold text-main small">Password</label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control form-control-with-btn" id="password" name="password" placeholder="Enter your password" required>
                            <button class="btn btn-outline-secondary btn-eye" type="button" id="togglePassword" title="Show/Hide Password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-success-custom py-2.5 rounded-pill font-semibold shadow-sm" id="submitBtn">
                            <i class="fas fa-sign-in-alt me-2"></i> Faculty Portal Login
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-light-subtle">
                    <a href="../index.php" class="small text-muted text-decoration-none font-semibold hover-primary">
                        <i class="fas fa-arrow-left me-1.5"></i> Back to Main Portal Gateway
                    </a>
                </div>
            </div>
        </div>

    </div>

    <script src="../assets/js/theme.js"></script>
    <script>
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
                    btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Faculty Portal Login';
                }
            })
            .catch(err => {
                alertBox.innerHTML = `
                    <div class="alert alert-danger rounded-4 mb-3 small fw-semibold">Connection error. Please try again.</div>
                `;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Faculty Portal Login';
            });
        });
    </script>
</body>
</html>