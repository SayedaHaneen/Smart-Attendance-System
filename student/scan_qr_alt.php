<?php
// student/scan_qr_alt.php - Alternative QR Scanner using html5-qrcode with better mobile support
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
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body {
            background: #000;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(0,0,0,0.9) !important;
        }
        .navbar-brand, .navbar .btn-light {
            color: #fff !important;
        }
        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: #000;
            min-height: 400px;
            border-radius: 15px;
            overflow: hidden;
        }
        #reader video {
            width: 100% !important;
            height: auto !important;
            min-height: 400px;
        }
        .controls {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 0 20px;
            flex-wrap: wrap;
        }
        .controls .btn {
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            min-width: 100px;
        }
        .status {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(0,0,0,0.85);
            color: white;
            max-width: 90%;
            text-align: center;
        }
        .status.success { border: 1px solid #00b894; color: #00b894; }
        .status.error { border: 1px solid #e17055; color: #e17055; }
        .status.info { border: 1px solid #6c5ce7; color: #6c5ce7; }
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
        }
        .result-overlay.show { display: flex; }
        .result-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .result-card .icon { font-size: 60px; margin-bottom: 15px; }
        .result-card .icon.success { color: #00b894; }
        .result-card .icon.error { color: #e17055; }
        .result-card .details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            text-align: left;
        }
        @media (max-width: 576px) {
            #reader { min-height: 300px; }
            #reader video { min-height: 300px; }
            .controls .btn { padding: 10px 18px; font-size: 13px; min-width: 80px; }
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
    
    <div id="status" class="status info"><i class="fas fa-info-circle"></i> Tap "Start" to scan</div>
    
    <div class="container mt-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-12">
                <div id="reader"></div>
            </div>
        </div>
    </div>
    
    <div class="controls">
        <button id="startBtn" class="btn btn-success" onclick="startScan()">
            <i class="fas fa-play"></i> Start
        </button>
        <button id="stopBtn" class="btn btn-danger d-none" onclick="stopScan()">
            <i class="fas fa-stop"></i> Stop
        </button>
        <button id="switchBtn" class="btn btn-info d-none" onclick="switchCamera()">
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
    
    <script>
        let html5QrCode = null;
        let isScanning = false;
        let cameras = [];
        let currentCameraIndex = 0;
        let isProcessing = false;
        
        function startScan() {
            if (isScanning) return;
            
            document.getElementById('startBtn').classList.add('d-none');
            document.getElementById('stopBtn').classList.remove('d-none');
            setStatus('Starting camera...', 'info');
            
            // Get cameras
            Html5Qrcode.getCameras().then(devices => {
                cameras = devices;
                if (cameras.length === 0) {
                    setStatus('No cameras found', 'error');
                    return;
                }
                
                // Try back camera first
                let cameraId = cameras[0].id;
                for (let i = 0; i < cameras.length; i++) {
                    if (cameras[i].label.toLowerCase().includes('back') || 
                        cameras[i].label.toLowerCase().includes('rear') ||
                        cameras[i].label.toLowerCase().includes('environment')) {
                        cameraId = cameras[i].id;
                        currentCameraIndex = i;
                        break;
                    }
                }
                
                if (cameras.length > 1) {
                    document.getElementById('switchBtn').classList.remove('d-none');
                }
                
                html5QrCode = new Html5Qrcode("reader");
                
                const config = {
                    fps: 20,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0
                };
                
                html5QrCode.start(
                    { deviceId: { exact: cameraId } },
                    config,
                    onScanSuccess,
                    onScanError
                ).then(() => {
                    isScanning = true;
                    setStatus('✅ Camera ready! Point at QR code.', 'success');
                }).catch(err => {
                    // Fallback to facing mode
                    html5QrCode.start(
                        { facingMode: "environment" },
                        config,
                        onScanSuccess,
                        onScanError
                    ).then(() => {
                        isScanning = true;
                        setStatus('✅ Camera ready! Point at QR code.', 'success');
                    }).catch(err2 => {
                        setStatus('Failed to start: ' + err2, 'error');
                        document.getElementById('startBtn').classList.remove('d-none');
                        document.getElementById('stopBtn').classList.add('d-none');
                    });
                });
            }).catch(err => {
                setStatus('Camera error: ' + err, 'error');
                document.getElementById('startBtn').classList.remove('d-none');
            });
        }
        
        function stopScan() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    document.getElementById('startBtn').classList.remove('d-none');
                    document.getElementById('stopBtn').classList.add('d-none');
                    document.getElementById('switchBtn').classList.add('d-none');
                    setStatus('Camera stopped.', 'info');
                });
            }
        }
        
        function switchCamera() {
            if (!isScanning) {
                currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                setStatus(`Switched to camera ${currentCameraIndex + 1}`, 'info');
                return;
            }
            
            stopScan();
            setTimeout(() => {
                currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
                startScan();
            }, 500);
        }
        
        function onScanSuccess(decodedText) {
            if (isProcessing) return;
            isProcessing = true;
            
            if (navigator.vibrate) navigator.vibrate(300);
            setStatus('Processing...', 'info');
            processQRCode(decodedText);
        }
        
        function onScanError(err) {
            // Silent
        }
        
        function processQRCode(qrData) {
            const parts = qrData.split('|');
            if (parts.length < 2) {
                setStatus('Invalid QR code', 'error');
                isProcessing = false;
                return;
            }
            
            const sessionId = parts[0];
            const timestamp = parts[1];
            
            const qrTime = parseInt(timestamp);
            const currentTime = Math.floor(Date.now() / 1000);
            
            if ((currentTime - qrTime) > 300) {
                setStatus('QR code expired!', 'error');
                isProcessing = false;
                return;
            }
            
            fetch('save_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: sessionId })
            })
            .then(response => response.json())
            .then(data => {
                isProcessing = false;
                if (data.success) {
                    showResult(true, data);
                } else {
                    setStatus('❌ ' + data.message, 'error');
                }
            })
            .catch(() => {
                isProcessing = false;
                setStatus('Network error', 'error');
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
        }
        
        function setStatus(msg, type) {
            const el = document.getElementById('status');
            el.className = `status ${type}`;
            el.innerHTML = msg;
            el.style.display = 'block';
        }
        
        // Auto-start
        setTimeout(startScan, 1500);
    </script>
</body>
</html>