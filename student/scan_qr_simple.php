<?php
// student/scan_qr_simple.php - Super Simple QR Scanner
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
    <title>Scan QR - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #000;
            min-height: 100vh;
            color: white;
        }
        .navbar {
            background: rgba(0,0,0,0.9) !important;
        }
        .navbar-brand, .navbar .btn-light {
            color: #fff !important;
        }
        .scanner-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            min-height: 400px;
        }
        #video {
            width: 100%;
            height: auto;
            min-height: 400px;
            background: #000;
            object-fit: cover;
        }
        .scan-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }
        .scan-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 220px;
            height: 220px;
            border: 3px solid #00ff00;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0,255,0,0.3);
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 30px rgba(0,255,0,0.3); }
            50% { box-shadow: 0 0 60px rgba(0,255,0,0.6); }
        }
        .scan-corner {
            position: absolute;
            width: 25px;
            height: 25px;
            border-color: #00ff00;
            border-style: solid;
            border-width: 0;
        }
        .scan-corner.tl { top: -3px; left: -3px; border-top-width: 4px; border-left-width: 4px; border-radius: 5px 0 0 0; }
        .scan-corner.tr { top: -3px; right: -3px; border-top-width: 4px; border-right-width: 4px; border-radius: 0 5px 0 0; }
        .scan-corner.bl { bottom: -3px; left: -3px; border-bottom-width: 4px; border-left-width: 4px; border-radius: 0 0 0 5px; }
        .scan-corner.br { bottom: -3px; right: -3px; border-bottom-width: 4px; border-right-width: 4px; border-radius: 0 0 5px 0; }
        .scan-line {
            position: absolute;
            top: 10%;
            left: 15%;
            right: 15%;
            height: 2px;
            background: linear-gradient(to right, transparent, #00ff00, transparent);
            animation: scanMove 2s ease-in-out infinite;
        }
        @keyframes scanMove {
            0% { top: 10%; opacity: 0; }
            50% { top: 90%; opacity: 1; }
            100% { top: 10%; opacity: 0; }
        }
        .controls {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 0 15px;
            flex-wrap: wrap;
        }
        .controls .btn {
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            min-width: 90px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .btn-start { background: linear-gradient(135deg, #00b894, #00cec9); color: white; }
        .btn-stop { background: linear-gradient(135deg, #e17055, #d63031); color: white; }
        .btn-switch { background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: white; }
        .btn-manual { background: linear-gradient(135deg, #fdcb6e, #f39c12); color: #2d3436; }
        .status-msg {
            position: fixed;
            top: 75px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(0,0,0,0.85);
            color: white;
            max-width: 90%;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .status-msg.success { border-color: #00b894; color: #00b894; }
        .status-msg.error { border-color: #e17055; color: #e17055; }
        .status-msg.info { border-color: #6c5ce7; color: #6c5ce7; }
        .status-msg.warning { border-color: #fdcb6e; color: #fdcb6e; }
        .result-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 200;
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(10px);
        }
        .result-overlay.show { display: flex; }
        .result-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            color: #2d3436;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .result-card .icon { font-size: 50px; margin-bottom: 10px; }
        .result-card .icon.success { color: #00b894; }
        .result-card .icon.error { color: #e17055; }
        .result-card .details {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 10px;
            margin: 12px 0;
            text-align: left;
        }
        .result-card .details p { margin: 4px 0; font-size: 14px; }
        .manual-entry-box {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
        }
        .manual-entry-box input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            text-align: center;
        }
        .manual-entry-box .btn {
            margin-top: 10px;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            border: none;
            background: #00b894;
            color: white;
            font-weight: 600;
        }
        @media (max-width: 576px) {
            .scanner-container { min-height: 300px; }
            #video { min-height: 300px; }
            .scan-box { width: 180px; height: 180px; }
            .controls .btn { padding: 10px 15px; font-size: 13px; min-width: 70px; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-qrcode"></i> Scan QR</a>
            <a href="dashboard.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </nav>

    <div id="statusMsg" class="status-msg info">
        <i class="fas fa-info-circle"></i> Tap "Start Camera" to scan
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="scanner-container">
                    <video id="video" autoplay playsinline></video>
                    <div class="scan-overlay">
                        <div class="scan-box">
                            <div class="scan-corner tl"></div>
                            <div class="scan-corner tr"></div>
                            <div class="scan-corner bl"></div>
                            <div class="scan-corner br"></div>
                        </div>
                        <div class="scan-line"></div>
                    </div>
                </div>
                
                <!-- Manual Entry -->
                <div class="manual-entry-box">
                    <p class="text-center mb-2"><i class="fas fa-keyboard"></i> Or enter Session ID manually:</p>
                    <div class="row g-2">
                        <div class="col-8">
                            <input type="number" id="manualSessionId" placeholder="Enter Session ID" class="form-control">
                        </div>
                        <div class="col-4">
                            <button onclick="manualSubmit()" class="btn btn-success w-100">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="controls">
        <button id="startBtn" class="btn btn-start" onclick="startCamera()">
            <i class="fas fa-play"></i> Start
        </button>
        <button id="stopBtn" class="btn btn-stop d-none" onclick="stopCamera()">
            <i class="fas fa-stop"></i> Stop
        </button>
        <button id="switchBtn" class="btn btn-switch d-none" onclick="switchCamera()">
            <i class="fas fa-sync"></i> Switch
        </button>
    </div>

    <div class="result-overlay" id="resultOverlay">
        <div class="result-card">
            <div id="resultIcon" class="icon success"><i class="fas fa-check-circle"></i></div>
            <h4 id="resultTitle">Success!</h4>
            <div id="resultDetails" class="details"></div>
            <button onclick="closeResult()" class="btn btn-primary w-100">OK</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let stream = null;
        let isRunning = false;
        let currentFacing = 'environment';
        let isProcessing = false;
        let scanInterval = null;
        const video = document.getElementById('video');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        function setStatus(msg, type) {
            const el = document.getElementById('statusMsg');
            el.className = `status-msg ${type}`;
            el.innerHTML = msg;
            el.style.display = 'block';
        }

        function startCamera() {
            if (isRunning) return;

            setStatus('Requesting camera...', 'info');
            
            const constraints = {
                video: {
                    facingMode: currentFacing,
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            };

            navigator.mediaDevices.getUserMedia(constraints)
                .then(s => {
                    stream = s;
                    video.srcObject = s;
                    video.play();
                    isRunning = true;
                    
                    document.getElementById('startBtn').classList.add('d-none');
                    document.getElementById('stopBtn').classList.remove('d-none');
                    document.getElementById('switchBtn').classList.remove('d-none');
                    
                    setStatus('✅ Camera ready! Point at QR code.', 'success');
                    
                    // Start scanning
                    startScanning();
                })
                .catch(err => {
                    console.error('Camera error:', err);
                    // Try without facing mode
                    navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                        .then(s => {
                            stream = s;
                            video.srcObject = s;
                            video.play();
                            isRunning = true;
                            
                            document.getElementById('startBtn').classList.add('d-none');
                            document.getElementById('stopBtn').classList.remove('d-none');
                            document.getElementById('switchBtn').classList.remove('d-none');
                            
                            setStatus('✅ Camera ready! (Default mode)', 'success');
                            startScanning();
                        })
                        .catch(err2 => {
                            setStatus('❌ Camera error: ' + err2.message, 'error');
                            document.getElementById('startBtn').classList.remove('d-none');
                        });
                });
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
                stream = null;
                video.srcObject = null;
                isRunning = false;
                
                clearInterval(scanInterval);
                
                document.getElementById('startBtn').classList.remove('d-none');
                document.getElementById('stopBtn').classList.add('d-none');
                document.getElementById('switchBtn').classList.add('d-none');
                setStatus('Camera stopped.', 'info');
            }
        }

        function switchCamera() {
            if (isRunning) {
                currentFacing = currentFacing === 'environment' ? 'user' : 'environment';
                stopCamera();
                setTimeout(startCamera, 500);
                setStatus('Switching camera...', 'info');
            }
        }

        function startScanning() {
            clearInterval(scanInterval);
            scanInterval = setInterval(() => {
                if (isRunning && !isProcessing) {
                    scanQRCode();
                }
            }, 500);
        }

        function scanQRCode() {
            if (!isRunning || !video.videoWidth) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const imageData = canvas.toDataURL('image/png');
            
            // Use an external QR decoding service
            fetch('https://api.qrserver.com/v1/read-qr-code/', {
                method: 'POST',
                body: new URLSearchParams({
                    fileurl: imageData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data && data[0] && data[0].symbol && data[0].symbol[0] && data[0].symbol[0].data) {
                    const result = data[0].symbol[0].data;
                    console.log('QR detected:', result);
                    if (navigator.vibrate) navigator.vibrate(200);
                    clearInterval(scanInterval);
                    processQRCode(result);
                }
            })
            .catch(err => {
                // Silent fail - continue scanning
            });
        }

        function processQRCode(qrData) {
            if (isProcessing) return;
            isProcessing = true;

            const parts = qrData.split('|');
            if (parts.length < 2) {
                setStatus('Invalid QR code format', 'error');
                isProcessing = false;
                return;
            }

            const sessionId = parts[0];
            const timestamp = parseInt(parts[1]);
            const currentTime = Math.floor(Date.now() / 1000);

            if ((currentTime - timestamp) > 300) {
                setStatus('QR code expired! Please get a new one.', 'error');
                isProcessing = false;
                return;
            }

            setStatus('Processing attendance...', 'info');

            $.ajax({
                url: 'save_attendance.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ session_id: sessionId }),
                dataType: 'json',
                success: function(data) {
                    isProcessing = false;
                    if (data.success) {
                        showResult(true, data);
                    } else {
                        setStatus('❌ ' + data.message, 'error');
                    }
                },
                error: function() {
                    isProcessing = false;
                    setStatus('Network error. Please try again.', 'error');
                }
            });
        }

        function manualSubmit() {
            const sessionId = document.getElementById('manualSessionId').value;
            if (!sessionId) {
                setStatus('Please enter a Session ID', 'warning');
                return;
            }

            setStatus('Processing...', 'info');
            isProcessing = true;

            $.ajax({
                url: 'save_attendance.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ session_id: sessionId }),
                dataType: 'json',
                success: function(data) {
                    isProcessing = false;
                    if (data.success) {
                        showResult(true, data);
                        document.getElementById('manualSessionId').value = '';
                    } else {
                        setStatus('❌ ' + data.message, 'error');
                    }
                },
                error: function() {
                    isProcessing = false;
                    setStatus('Network error. Please try again.', 'error');
                }
            });
        }

        function showResult(success, data) {
            const overlay = document.getElementById('resultOverlay');
            const icon = document.getElementById('resultIcon');
            const title = document.getElementById('resultTitle');
            const details = document.getElementById('resultDetails');

            if (success) {
                icon.className = 'icon success';
                icon.innerHTML = '<i class="fas fa-check-circle"></i>';
                title.textContent = '✅ Attendance Recorded!';
                details.innerHTML = `
                    <p><strong>Subject:</strong> ${data.subject || 'N/A'}</p>
                    <p><strong>Teacher:</strong> ${data.teacher || 'N/A'}</p>
                    <p><strong>Time:</strong> ${data.time || new Date().toLocaleTimeString()}</p>
                    <p><strong>Status:</strong> <span class="badge bg-success">Present</span></p>
                `;
            } else {
                icon.className = 'icon error';
                icon.innerHTML = '<i class="fas fa-times-circle"></i>';
                title.textContent = '❌ Failed';
                details.innerHTML = `<p>${data.message || 'Unknown error'}</p>`;
            }

            overlay.classList.add('show');
            
            if (success) {
                setTimeout(() => {
                    closeResult();
                    window.location.href = 'dashboard.php?attendance=success';
                }, 3000);
            }
        }

        function closeResult() {
            document.getElementById('resultOverlay').classList.remove('show');
            isProcessing = false;
            if (isRunning) {
                startScanning();
            }
        }

        // Auto-start on load
        setTimeout(startCamera, 1000);

        // Cleanup
        window.addEventListener('beforeunload', function() {
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
            }
            clearInterval(scanInterval);
        });
    </script>
</body>
</html>