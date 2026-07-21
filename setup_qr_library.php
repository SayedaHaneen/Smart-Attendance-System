<?php
// setup_qr_library.php - Check if QR Library is installed
require_once 'config.php';

echo "<h1>QR Code Library Setup Check</h1>";

// Check if QR library exists
if (file_exists('assets/phpqrcode/qrlib.php')) {
    echo "<p style='color:green;'>✅ PHP QR Code Library found at: assets/phpqrcode/qrlib.php</p>";
} else {
    echo "<p style='color:red;'>❌ PHP QR Code Library not found!</p>";
    echo "<p>Please download from: https://sourceforge.net/projects/phpqrcode/</p>";
    echo "<p>Extract and place in: assets/phpqrcode/</p>";
}

// Check if uploads directory exists
if (file_exists('uploads/qr_codes')) {
    echo "<p style='color:green;'>✅ Uploads directory exists: uploads/qr_codes/</p>";
    if (is_writable('uploads/qr_codes')) {
        echo "<p style='color:green;'>✅ Directory is writable</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Directory is not writable. Please set permissions: chmod 777 uploads/qr_codes/</p>";
    }
} else {
    echo "<p style='color:orange;'>⚠️ Creating uploads directory...</p>";
    mkdir('uploads/qr_codes', 0777, true);
    echo "<p style='color:green;'>✅ Uploads directory created</p>";
}

// Test QR generation
echo "<h2>Test QR Code Generation</h2>";
try {
    $testData = 'test_' . time();
    $qrFile = generateQRCode($testData);
    echo "<p>QR Code generated: <img src='$qrFile' alt='QR Code' style='max-width:200px;'></p>";
    echo "<p style='color:green;'>✅ QR Code generation working!</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ QR Code generation failed: " . $e->getMessage() . "</p>";
}

echo "<br><a href='index.php' class='btn btn-primary'>Go to Home</a>";
?>