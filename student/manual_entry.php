<?php
// student/manual_entry.php - Manual Entry with 4-Digit Code
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    redirect('../index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4-Digit Manual Code Entry - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <style>
        .code-input-large {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: 0.75rem;
            text-align: center;
            font-family: 'Courier New', monospace;
            border-radius: 16px;
            padding: 1rem;
            border: 2px solid var(--primary);
            background: var(--bg-surface);
            color: var(--text-main);
        }
        .code-input-large:focus {
            box-shadow: 0 0 0 5px var(--primary-light);
            border-color: var(--primary);
        }
    </style>
</head>
<body style="background: radial-gradient(circle at 50% 10%, rgba(79, 70, 229, 0.15), transparent 70%), var(--bg-body); min-height: 100vh;">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4" style="max-width: 1400px; margin: 0 auto;">
            <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="dashboard.php">
                <div class="brand-icon" style="background: linear-gradient(135deg, #4f46e5, #0ea5e9); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(79,70,229,0.35);">
                    <i class="fas fa-keyboard"></i>
                </div>
                <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.95rem;">Manual Entry <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Student</span></span>
            </a>
            
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Light/Dark Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 540px;">
        <div class="glass-card shadow-lg">
            <div class="glass-card-header text-center d-block py-3" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color: white; border-radius: 16px 16px 0 0;">
                <div class="brand-icon mx-auto mb-2" style="width:48px; height:48px; font-size:1.4rem; background:rgba(255,255,255,0.2);">
                    <i class="fas fa-keyboard"></i>
                </div>
                <h4 class="fw-bold mb-0">Manual Attendance Entry</h4>
                <small class="text-white-50">Enter the 4-digit code displayed on the teacher's screen</small>
            </div>

            <div class="glass-card-body p-4">
                <div class="alert alert-info border-0 rounded-4 d-flex align-items-center gap-3 mb-4" style="background: var(--info-light); color: var(--text-main);">
                    <i class="fas fa-lightbulb fa-2x text-info"></i>
                    <div style="font-size:0.875rem;">
                        <strong>Where is the code?</strong><br>
                        Look at the teacher's screen or projector. A 4-digit code (e.g. <code>0001</code>, <code>1234</code>) is displayed alongside the QR code.
                    </div>
                </div>

                <form id="manualForm" autocomplete="off">
                    <div class="mb-4 text-center">
                        <label for="session_code" class="form-label fw-bold mb-2 text-muted uppercase" style="font-size:0.8rem; letter-spacing:1px;">4-Digit Session Code</label>
                        <input type="text" class="form-control code-input-large" id="session_code" 
                               placeholder="1234" maxlength="4" required autofocus
                               pattern="[0-9]{4}">
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary-custom py-3 fs-6 rounded-3">
                            <i class="fas fa-check-circle me-2"></i> Submit Attendance Code
                        </button>
                    </div>
                </form>

                <div id="result" class="mt-3"></div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between gap-2">
                    <a href="scan_qr.php" class="btn btn-outline-primary btn-sm rounded-pill w-50">
                        <i class="fas fa-camera me-1"></i> Scan Camera QR
                    </a>
                    <a href="history.php" class="btn btn-outline-secondary btn-sm rounded-pill w-50">
                        <i class="fas fa-history me-1"></i> Attendance Log
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        $('#session_code').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
        });

        $('#manualForm').on('submit', function(e) {
            e.preventDefault();
            
            const sessionCode = $('#session_code').val();
            if (!sessionCode || sessionCode.length !== 4) {
                showToast('Please enter a valid 4-digit session code', 'warning');
                return;
            }
            
            const sessionId = parseInt(sessionCode, 10);
            
            $('#result').html(`
                <div class="alert alert-info d-flex align-items-center gap-2">
                    <span class="spinner-border spinner-border-sm"></span> Verifying code...
                </div>
            `);
            $('button[type="submit"]').prop('disabled', true);

            const deviceId = localStorage.getItem('student_device_id') || localStorage.getItem('system_device_token') || '';

            fetch('save_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: sessionCode, device_id: deviceId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.playSuccessChime) window.playSuccessChime();
                    showToast('Attendance recorded successfully!', 'success');
                    $('#result').html(`
                        <div class="p-3 rounded-4 text-center" style="background: var(--success-light); border: 1px solid var(--success);">
                            <div class="fs-1 text-success mb-2"><i class="fas fa-check-circle"></i></div>
                            <h5 class="fw-bold text-success mb-1">Attendance Marked!</h5>
                            <p class="mb-1 text-main">Subject: <strong>${data.subject || 'Session'}</strong></p>
                            <p class="mb-0 text-muted small">Teacher: ${data.teacher || 'Teacher'}</p>
                        </div>
                    `);
                    $('#session_code').val('');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php?attendance=success';
                    }, 2500);
                } else {
                    $('#result').html(`
                        <div class="alert alert-danger d-flex align-items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i> ${data.message || 'Validation failed.'}
                        </div>
                    `);
                    $('button[type="submit"]').prop('disabled', false);
                }
            })
            .catch(err => {
                $('#result').html(`
                    <div class="alert alert-danger">Network error. Please try again.</div>
                `);
                $('button[type="submit"]').prop('disabled', false);
            });
        });
    </script>
</body>
</html>