<?php
// admin/login.php - Admin Login Page
require_once '../config.php';

// If logout requested, clear session
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    session_start();
}

$already_logged_in = isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'admin';
if ($already_logged_in && !isset($_GET['reauth'])) {
    // If already logged in, redirect unless reauth requested
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 10% 90%, rgba(239, 68, 68, 0.12), transparent 50%), var(--bg-body);
            padding: 2rem 1rem;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #ffffff;
            padding: 2.25rem 1.5rem;
            text-align: center;
            position: relative;
        }
        .auth-body {
            padding: 2rem 1.75rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <button class="btn-theme-toggle position-absolute top-0 end-0 m-3" onclick="toggleAppTheme()" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="brand-icon mx-auto mb-3" style="width: 54px; height: 54px; font-size: 1.6rem; background: rgba(255,255,255,0.2);">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="fw-bold mb-1">Admin Portal</h3>
                <p class="mb-0 text-white-50 small">System Control & Administration</p>
            </div>
            
            <div class="auth-body">
                <div id="alertContainer"></div>

                <form id="loginForm" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label font-semibold">Email or Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary border-end-0 text-muted"><i class="fas fa-user-shield"></i></span>
                            <input type="text" class="form-control border-start-0" id="username" name="username" placeholder="e.g. admin@university.edu" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label font-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" placeholder="Enter password" required>
                            <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-danger py-2 rounded-3 fw-bold" id="submitBtn">
                            <i class="fas fa-sign-in-alt me-2"></i> Admin Login
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <a href="../index.php" class="small text-muted text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Back to Main Menu
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
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
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>${data.message || 'Login failed. Please check credentials.'}</div>
                        </div>
                    `;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Admin Login';
                }
            })
            .catch(err => {
                alertBox.innerHTML = `<div class="alert alert-danger mb-3">Connection error.</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Admin Login';
            });
        });
    </script>
</body>
</html>