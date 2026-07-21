<?php
// student/camera_test.php - Test Camera
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
    <title>Camera Test</title>
    <style>
        body {
            background: #1a1a2e;
            color: white;
            font-family: Arial, sans-serif;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 500px;
            width: 100%;
        }
        h2 { text-align: center; margin-bottom: 20px; color: #00b894; }
        .video-wrapper {
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            border: 3px solid #00b894;
            margin-bottom: 20px;
        }
        video {
            width: 100%;
            display: block;
            background: #000;
            min-height: 300px;
        }
        .status {
            padding: 12px 20px;
            border-radius: 10px;
            margin: 10px 0;
            text-align: center;
            font-weight: 500;
        }
        .status.success { background: #00b894; color: white; }
        .status.error { background: #e17055; color: white; }
        .status.info { background: #6c5ce7; color: white; }
        .status.loading { background: #fdcb6e; color: #2d3436; }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 120px;
        }
        .btn:active { transform: scale(0.95); }
        .btn-back { background: #00b894; color: white; }
        .btn-front { background: #6c5ce7; color: white; }
        .btn-stop { background: #e17055; color: white; }
        .btn-home { background: #fdcb6e; color: #2d3436; }
        .btn-scanner { background: #0984e3; color: white; }
        .btn:hover { opacity: 0.85; }
        .device-info {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 14px;
        }
        .device-info p { margin: 5px 0; }
        .device-info strong { color: #00b894; }
        @media (max-width: 576px) {
            .btn { padding: 10px 15px; font-size: 14px; min-width: 80px; }
            video { min-height: 200px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-camera"></i> Camera Test</h2>
        
        <div class="video-wrapper">
            <video id="video" autoplay playsinline></video>
        </div>
        
        <div id="status" class="status info">Click "Start Back Camera"</div>
        
        <div class="btn-group">
            <button class="btn btn-back" onclick="startBackCamera()">📷 Back Camera</button>
            <button class="btn btn-front" onclick="startFrontCamera()">🤳 Front Camera</button>
            <button class="btn btn-stop" onclick="stopCamera()">⏹️ Stop</button>
        </div>
        
        <div class="btn-group" style="margin-top:10px;">
            <button class="btn btn-scanner" onclick="location.href='scan_qr_simple.php'">📱 Scan QR</button>
            <button class="btn btn-home" onclick="location.href='dashboard.php'">🏠 Dashboard</button>
        </div>
        
        <div class="device-info" id="deviceInfo">
            <p><strong>Status:</strong> <span id="statusText">Not started</span></p>
            <p><strong>Device:</strong> <span id="deviceText">Unknown</span></p>
        </div>
    </div>

    <script>
        let stream = null;
        const video = document.getElementById('video');
        const status = document.getElementById('status');
        const statusText = document.getElementById('statusText');
        const deviceText = document.getElementById('deviceText');

        function setStatus(msg, type = 'info') {
            status.className = `status ${type}`;
            status.textContent = msg;
            statusText.textContent = msg;
        }

        function startCamera(constraints) {
            stopCamera();
            setStatus('Requesting camera...', 'loading');
            deviceText.textContent = 'Requesting...';
            
            navigator.mediaDevices.getUserMedia(constraints)
                .then(s => {
                    stream = s;
                    video.srcObject = s;
                    video.play();
                    setStatus('✅ Camera working!', 'success');
                    deviceText.textContent = constraints.video.facingMode.exact || 'Default';
                })
                .catch(err => {
                    console.error('Error:', err);
                    setStatus('❌ Error: ' + err.message, 'error');
                    deviceText.textContent = 'Failed: ' + err.message;
                    
                    // Try default camera
                    if (constraints.video.facingMode && constraints.video.facingMode.exact) {
                        setStatus('Trying default camera...', 'loading');
                        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                            .then(s => {
                                stream = s;
                                video.srcObject = s;
                                video.play();
                                setStatus('✅ Camera working (default mode)', 'success');
                                deviceText.textContent = 'Default Camera';
                            })
                            .catch(err2 => {
                                setStatus('❌ No camera found', 'error');
                                deviceText.textContent = 'No camera available';
                            });
                    }
                });
        }

        function startBackCamera() {
            startCamera({
                video: { facingMode: { exact: "environment" } },
                audio: false
            });
        }

        function startFrontCamera() {
            startCamera({
                video: { facingMode: { exact: "user" } },
                audio: false
            });
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
                stream = null;
                video.srcObject = null;
                setStatus('Camera stopped', 'info');
                deviceText.textContent = 'Stopped';
            }
        }

        // Auto test on load
        setTimeout(startBackCamera, 1000);

        // Cleanup
        window.addEventListener('beforeunload', stopCamera);
    </script>
</body>
</html>