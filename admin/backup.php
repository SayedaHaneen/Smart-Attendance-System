<?php
// admin/backup.php - Database Backup & Restore Utility
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$db = getDB();
$message = '';
$message_type = '';

// Handle Download Backup
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $tables = [];
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    $sqlDump = "-- Database Backup for " . DB_NAME . "\n";
    $sqlDump .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        $res = $db->query("SHOW CREATE TABLE `$table`");
        $showRow = $res->fetch_row();
        $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlDump .= $showRow[1] . ";\n\n";
        
        $dataRes = $db->query("SELECT * FROM `$table`");
        $numFields = $dataRes->field_count;
        
        while ($row = $dataRes->fetch_row()) {
            $sqlDump .= "INSERT INTO `$table` VALUES(";
            for ($j=0; $j<$numFields; $j++) {
                if (isset($row[$j])) {
                    $val = $db->real_escape_string($row[$j]);
                    $sqlDump .= '"' . $val . '"';
                } else {
                    $sqlDump .= 'NULL';
                }
                if ($j < ($numFields-1)) {
                    $sqlDump .= ',';
                }
            }
            $sqlDump .= ");\n";
        }
        $sqlDump .= "\n\n";
    }
    
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=database_backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql');
    echo $sqlDump;
    exit;
}

// Handle SQL Restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_db'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
        
        if (!empty($sql)) {
            // Re-establish connection
            $restoreDb = getDB();
            if ($restoreDb->multi_query($sql)) {
                do {
                    if ($res = $restoreDb->store_result()) {
                        $res->free();
                    }
                } while ($restoreDb->more_results() && $restoreDb->next_result());
                
                $message = "Database backup restored successfully! All tables and schema updated.";
                $message_type = "success";
            } else {
                $message = "Failed to run restore script: " . $restoreDb->error;
                $message_type = "danger";
            }
        } else {
            $message = "The uploaded SQL backup file is empty.";
            $message_type = "danger";
        }
    } else {
        $message = "Please upload a valid SQL file to restore.";
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup & Restore - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/theme.css" rel="stylesheet">
</head>
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="container py-5 animate-slide-up" style="max-width: 800px;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show rounded-4 mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Database Information Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 card-glow">
            <div class="card-header bg-gradient-primary-glow border-0 py-3 text-white">
                <h5 class="fw-bold mb-0"><i class="fas fa-database me-2"></i> Local Database Engine Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="row text-center">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <span class="text-muted small d-block">DB ENGINE</span>
                        <strong class="text-main">MySQL / MariaDB</strong>
                    </div>
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <span class="text-muted small d-block">HOST</span>
                        <strong class="text-main"><?php echo DB_HOST; ?></strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">DATABASE NAME</span>
                        <strong class="text-main text-primary"><?php echo DB_NAME; ?></strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">CHARSET</span>
                        <strong class="text-main">utf8mb4</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- 1. Download Backup -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                    <div class="card-body d-flex flex-column align-items-center text-center">
                        <div class="rounded-circle bg-success-subtle text-success p-3 mb-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-download fa-2x"></i>
                        </div>
                        <h5 class="fw-bold text-main">Download SQL Backup</h5>
                        <p class="text-muted small flex-grow-1">Generates a complete schema structure and raw insert statement backup of the database to download instantly.</p>
                        <a href="backup.php?action=download" class="btn btn-success rounded-pill px-4 fw-bold w-100">
                            <i class="fas fa-download me-1"></i> Export SQL Database
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. Restore Backup -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex flex-column align-items-center text-center mb-3">
                            <div class="rounded-circle bg-warning-subtle text-warning p-3 mb-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-file-upload fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-main">Restore Database</h5>
                            <p class="text-muted small">Upload a previously exported <code>.sql</code> backup script to overwrite schema data.</p>
                        </div>
                        
                        <form method="POST" action="backup.php" enctype="multipart/form-data" class="w-100 mt-auto">
                            <input type="hidden" name="restore_db" value="1">
                            <div class="mb-3">
                                <input type="file" name="backup_file" class="form-control form-control-sm rounded-3" accept=".sql" required>
                            </div>
                            <button type="submit" onclick="return confirm('WARNING: This will drop current tables and rewrite database. Continue?')" class="btn btn-warning rounded-pill px-4 fw-bold w-100 text-white">
                                <i class="fas fa-upload me-1"></i> Import SQL Script
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
