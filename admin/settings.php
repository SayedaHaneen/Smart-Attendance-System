<?php
// admin/settings.php - System Configuration Settings
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();
$message = '';
$message_type = '';

// Handle save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $school_name = sanitize($_POST['school_name'] ?? 'Smart Attendance System');
    $qr_validity = intval($_POST['qr_validity'] ?? 30);
    $session_limit = intval($_POST['session_limit'] ?? 15);
    $attendance_radius = intval($_POST['attendance_radius'] ?? 50);

    setSetting('school_name', $school_name);
    setSetting('qr_validity', $qr_validity);
    setSetting('session_limit', $session_limit);
    setSetting('attendance_radius', $attendance_radius);

    $message = "System settings updated successfully! Please refresh to see changes.";
    $message_type = "success";
    
    header("Location: settings.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit;
}

if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = sanitize($_GET['type'] ?? 'info');
}

$school_name = getSetting('school_name', 'Smart Attendance System');
$qr_validity = getSetting('qr_validity', '30');
$session_limit = getSetting('session_limit', '15');
$attendance_radius = getSetting('attendance_radius', '50');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global System Settings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="container py-5 animate-slide-up" style="max-width: 800px;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 card-glow">
            <div class="card-header bg-gradient-primary-glow border-0 py-3 text-white">
                <h5 class="fw-bold mb-0"><i class="fas fa-sliders-h me-2"></i> Global System Configuration</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="settings.php">
                    <input type="hidden" name="save_settings" value="1">
                    
                    <div class="mb-4">
                        <label class="form-label font-semibold text-muted mb-1"><i class="fas fa-university me-1 text-primary"></i> School/Institution Name</label>
                        <input type="text" name="school_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($school_name); ?>" required>
                        <div class="form-text small">This name is displayed across the navigation bars and portal branding headers.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label font-semibold text-muted mb-1"><i class="fas fa-qrcode me-1 text-success"></i> QR Code Lifetime (Seconds)</label>
                            <input type="number" name="qr_validity" class="form-control rounded-3" value="<?php echo htmlspecialchars($qr_validity); ?>" min="10" max="300" required>
                            <div class="form-text small">Duration before the dynamic attendance QR code expires and refreshes (Recommended: 30s).</div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label font-semibold text-muted mb-1"><i class="fas fa-clock me-1 text-warning"></i> Attendance Session Limit (Minutes)</label>
                            <input type="number" name="session_limit" class="form-control rounded-3" value="<?php echo htmlspecialchars($session_limit); ?>" min="1" max="180" required>
                            <div class="form-text small">Maximum duration of a single generated class attendance slot (Default: 15 mins).</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-semibold text-muted mb-1"><i class="fas fa-map-marker-alt me-1 text-danger"></i> Geofence Radius limit (Meters - Optional)</label>
                        <input type="number" name="attendance_radius" class="form-control rounded-3" value="<?php echo htmlspecialchars($attendance_radius); ?>" min="10" max="1000" required>
                        <div class="form-text small">Radius boundary limit for tracking local attendance connections (Default: 50m).</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button type="submit" class="btn btn-primary-custom rounded-pill px-4 fw-bold">
                            <i class="fas fa-save me-1"></i> Save Configurations
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
