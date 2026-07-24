<?php
// student/scan_qr.php - Advanced Camera Scanner with Mobile HTTPS & Fallback Support
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    redirect('../index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scan QR Code - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
    <!-- Html5Qrcode Library (Modern standard for mobile camera scanning) -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        .scanner-container-card {
            max-width: 520px;
            margin: 0 auto;
            border-radius: 24px;
            overflow: hidden;
            background: #000;
            position: relative;
            box-shadow: var(--shadow-lg);
        }
        #reader {
            width: 100%;
            min-height: 360px;
            background: #000;
            border: none !important;
        }
        #reader video {
            object-fit: cover !important;
            border-radius: 20px;
        }
        .scan-overlay-reticle {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 240px;
            height: 240px;
            border: 3px solid #10b981;
            border-radius: 20px;
            box-shadow: 0 0 40px rgba(16, 185, 129, 0.4);
            pointer-events: none;
            z-index: 10;
            animation: pulseReticle 2s infinite ease-in-out;
        }
        @keyframes pulseReticle {
            0%, 100% { border-color: #10b981; box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); }
            50% { border-color: #38bdf8; box-shadow: 0 0 50px rgba(56, 189, 248, 0.6); }
        }
        .scan-line-laser {
            position: absolute;
            width: 90%;
            left: 5%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #10b981, transparent);
            box-shadow: 0 0 15px #10b981;
            top: 10%;
            animation: laserMove 2.2s infinite ease-in-out;
        }
        @keyframes laserMove {
            0% { top: 10%; opacity: 0.2; }
            50% { top: 85%; opacity: 1; }
            100% { top: 10%; opacity: 0.2; }
        }
    </style>
</head>
<body style="background: radial-gradient(circle at 50% 10%, rgba(79, 70, 229, 0.15), transparent 70%), var(--bg-body); min-height: 100vh;">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4" style="max-width: 1400px; margin: 0 auto;">
            <a class="navbar-brand d-flex align-items-center gap-2 me-3" href="dashboard.php">
                <div class="brand-icon" style="background: linear-gradient(135deg, #4f46e5, #0ea5e9); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(79,70,229,0.35);">
                    <i class="fas fa-qrcode"></i>
                </div>
                <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.95rem;">QR Scanner <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Student</span></span>
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

    <div class="container py-4" style="max-width: 600px;">
        
        <!-- Security & HTTP Wi-Fi Notice Banner -->
        <div id="insecureNotice" class="alert alert-warning border-0 rounded-4 mb-3 d-none" style="background: var(--warning-light); color: var(--text-main);">
            <div class="d-flex align-items-start gap-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning flex-shrink-0 mt-1"></i>
                <div>
                    <strong class="d-block mb-1">Mobile Browser Camera Security Notice</strong>
                    <span style="font-size:0.875rem;">
                        Mobile browsers (Chrome/Safari) block camera access over unencrypted HTTP local Wi-Fi. 
                        If the camera does not open, use the <strong>4-Digit Session Code</strong> option!
                    </span>
                    <div class="mt-2">
                        <a href="manual_entry.php" class="btn btn-warning btn-sm rounded-pill font-semibold">
                            <i class="fas fa-keyboard me-1"></i> Use 4-Digit Code Instead
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div id="statusAlert"></div>

        <!-- Scanner Card -->
        <div class="scanner-container-card p-3">
            <div class="position-relative overflow-hidden rounded-4">
                <div id="reader"></div>
                <div class="scan-overlay-reticle" id="reticle">
                    <div class="scan-line-laser"></div>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                <button id="startBtn" onclick="startScanner()" class="btn btn-success rounded-pill px-4">
                    <i class="fas fa-camera me-1"></i> Start Camera
                </button>
                <button id="stopBtn" onclick="stopScanner()" class="btn btn-outline-light rounded-pill px-3 d-none">
                    <i class="fas fa-pause me-1"></i> Stop Camera
                </button>
                <button id="switchBtn" onclick="switchCamera()" class="btn btn-outline-info rounded-pill px-3 d-none">
                    <i class="fas fa-sync me-1"></i> Flip Camera
                </button>
                <a href="manual_entry.php" class="btn btn-warning rounded-pill px-3">
                    <i class="fas fa-keyboard me-1"></i> 4-Digit Code
                </a>
            </div>
        </div>
    </div>

    <!-- Success Result Overlay Modal -->
    <div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card p-4 text-center">
                <div class="text-success fs-1 mb-2"><i class="fas fa-check-circle fa-2x"></i></div>
                <h4 class="fw-bold mb-1">Attendance Recorded!</h4>
                <div id="resultDetails" class="my-3 text-muted small"></div>
                <button onclick="window.location.href='dashboard.php'" class="btn btn-primary-custom px-4 rounded-pill">
                    <i class="fas fa-check me-1"></i> Return to Dashboard
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        let html5QrCode = null;
        let isScanning = false;
        let currentFacingMode = "environment"; // Default to back camera for mobile

        // Check HTTP vs HTTPS for mobile browsers
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            document.getElementById('insecureNotice').classList.remove('d-none');
        }

        function showStatus(msg, type = 'info') {
            const container = document.getElementById('statusAlert');
            container.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show rounded-4 mb-3">
                    <i class="fas fa-info-circle me-1"></i> ${msg}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }

        function startScanner() {
            if (isScanning) return;

            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 15, qrbox: { width: 240, height: 240 } };

            html5QrCode.start(
                { facingMode: currentFacingMode },
                config,
                onScanSuccess,
                onScanError
            )
            .then(() => {
                isScanning = true;
                document.getElementById('startBtn').classList.add('d-none');
                document.getElementById('stopBtn').classList.remove('d-none');
                document.getElementById('switchBtn').classList.remove('d-none');
                showStatus('Camera active! Point your camera at the teacher\'s QR code.', 'success');
            })
            .catch(err => {
                console.error("Camera start error:", err);
                showStatus("Camera activation error: " + err + "<br>If using phone over local Wi-Fi, modern mobile browsers require HTTPS or localhost. Try <strong>4-Digit Code</strong> entry instead.", "danger");
            });
        }

        function stopScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    document.getElementById('startBtn').classList.remove('d-none');
                    document.getElementById('stopBtn').classList.add('d-none');
                    document.getElementById('switchBtn').classList.add('d-none');
                    showStatus('Camera stopped.', 'info');
                });
            }
        }

        function switchCamera() {
            currentFacingMode = (currentFacingMode === "environment") ? "user" : "environment";
            if (isScanning) {
                stopScanner();
                setTimeout(startScanner, 400);
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            if (!isScanning) return;
            
            // Stop scanning immediately to prevent duplicate requests
            stopScanner();

            // Play chime sound
            if (window.playSuccessChime) window.playSuccessChime();

            // Parse session ID from QR data format: "session_id|timestamp" or "session_id"
            const parts = decodedText.split('|');
            const sessionId = parseInt(parts[0], 10);

            if (!sessionId || isNaN(sessionId)) {
                showStatus('Invalid QR Code scanned format.', 'danger');
                return;
            }

            showStatus('Valid QR Code scanned! Acquiring GPS Coordinates...', 'info');

            const deviceId = localStorage.getItem('student_device_id') || localStorage.getItem('system_device_token') || '';

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        submitAttendance(decodedText, deviceId, lat, lon);
                    },
                    (error) => {
                        console.warn("GPS Access Denied/Error:", error);
                        showStatus('GPS Access denied. Submitting baseline attendance...', 'warning');
                        submitAttendance(decodedText, deviceId, null, null);
                    },
                    { enableHighAccuracy: true, timeout: 6000 }
                );
            } else {
                submitAttendance(decodedText, deviceId, null, null);
            }
        }

        function submitAttendance(sessionData, deviceId, lat, lon) {
            fetch('save_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    session_id: sessionData, 
                    device_id: deviceId,
                    latitude: lat,
                    longitude: lon
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('resultDetails').innerHTML = `
                        <p class="mb-1">Subject: <strong>${data.subject || 'Class Session'}</strong></p>
                        <p class="mb-1">Teacher: ${data.teacher || 'Teacher'}</p>
                        <p class="mb-0">Time: ${data.time || new Date().toLocaleTimeString()}</p>
                    `;
                    const modal = new bootstrap.Modal(document.getElementById('resultModal'));
                    modal.show();
                } else {
                    showStatus('❌ ' + (data.message || 'Failed to record attendance.'), 'danger');
                }
            })
            .catch(err => {
                showStatus('Network error while processing attendance.', 'danger');
            });
        }

        function onScanError(errorMessage) {
            // Ignore frame parse errors
        }

        // Auto start on load
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(startScanner, 300);
        });
    </script>
</body>
</html>