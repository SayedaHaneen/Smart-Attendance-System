<?php
// admin/navbar.php - Clean Top Navbar with Theme Toggle to the Left of Logout
if (!defined('APP_NAME')) {
    require_once '../config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Desktop & Tablet Top Sticky Navbar -->
<nav class="navbar navbar-expand-lg app-navbar sticky-top shadow-sm py-2">
    <div class="container-fluid px-3 px-md-4" style="max-width: 1400px; margin: 0 auto;">
        
        <!-- Left: Logo & Admin Badge -->
        <a class="navbar-brand d-flex align-items-center gap-2 me-2" href="dashboard.php">
            <div class="brand-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0; box-shadow: 0 4px 10px rgba(239,68,68,0.3);">
                <i class="fas fa-shield-alt"></i>
            </div>
            <span class="fw-bold tracking-tight text-main text-nowrap" style="font-size: 0.9rem;"><?php echo APP_NAME; ?> <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1 text-uppercase" style="font-size:0.6rem;">Admin</span></span>
        </a>

        <!-- Mobile Trigger & Theme Toggle -->
        <div class="d-flex align-items-center gap-2 d-lg-none ms-auto">
            <button class="btn-theme-toggle border-0 p-1.5 rounded-circle text-main" onclick="toggleAppTheme()" title="Toggle Theme">
                <i class="fas fa-moon"></i>
            </button>
            <button class="navbar-toggler text-main border-0 p-1.5" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminNavbarOffcanvas" aria-controls="adminNavbarOffcanvas" aria-label="Toggle navigation">
                <i class="fas fa-bars fa-lg"></i>
            </button>
        </div>

        <!-- Desktop Navigation Items -->
        <div class="collapse navbar-collapse d-none d-lg-flex align-items-center justify-content-between" id="adminDesktopNav">
            
            <!-- Left-Middle Navigation Links -->
            <ul class="navbar-nav me-auto mb-0 gap-1 align-items-center ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1 rounded-pill text-nowrap <?php echo $current_page === 'dashboard.php' ? 'active fw-bold' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-chart-pie me-1 text-danger"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1 rounded-pill text-nowrap <?php echo $current_page === 'manage_students.php' ? 'active fw-bold' : ''; ?>" href="manage_students.php">
                        <i class="fas fa-users-cog me-1 text-primary"></i> Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1 rounded-pill text-nowrap <?php echo $current_page === 'manage_teachers.php' ? 'active fw-bold' : ''; ?>" href="manage_teachers.php">
                        <i class="fas fa-chalkboard-teacher me-1 text-success"></i> Faculty
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1 rounded-pill text-nowrap <?php echo $current_page === 'manage_courses.php' ? 'active fw-bold' : ''; ?>" href="manage_courses.php">
                        <i class="fas fa-book-open me-1 text-info"></i> Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1 rounded-pill text-nowrap <?php echo $current_page === 'manage_departments.php' ? 'active fw-bold' : ''; ?>" href="manage_departments.php">
                        <i class="fas fa-building me-1 text-warning"></i> Departments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1 rounded-pill text-nowrap <?php echo $current_page === 'manage_batches.php' ? 'active fw-bold' : ''; ?>" href="manage_batches.php">
                        <i class="fas fa-layer-group me-1 text-purple"></i> Batches
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2.5 py-1 rounded-pill text-nowrap <?php echo $current_page === 'approve_students.php' ? 'active fw-bold' : ''; ?>" href="approve_students.php">
                        <i class="fas fa-user-check me-1 text-warning"></i> Approvals
                    </a>
                </li>
            </ul>

            <!-- Far-Right Utilities: Theme Toggle to the LEFT of Logout -->
            <div class="d-flex align-items-center gap-2.5 ms-auto">
                <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Light/Dark Theme">
                    <i class="fas fa-moon"></i>
                </button>

                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap" style="font-size: 0.8rem;">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>

        </div>
    </div>
</nav>

<!-- Mobile Bootstrap Offcanvas Menu (Width 280px, Slides from Left) -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="adminNavbarOffcanvas" aria-labelledby="offcanvasLabel" style="width: 280px;">
    <div class="offcanvas-header border-bottom py-3">
        <div class="d-flex align-items-center gap-2">
            <div class="brand-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <span class="fw-bold tracking-tight text-main" id="offcanvasLabel"><?php echo APP_NAME; ?></span>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3 d-flex flex-column">
        <ul class="nav nav-pills flex-column gap-1 mb-auto">
            <li class="nav-item">
                <a class="nav-link px-3 py-2 rounded-3 <?php echo $current_page === 'dashboard.php' ? 'active fw-bold' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-chart-pie fa-fw me-2 text-danger"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 rounded-3 <?php echo $current_page === 'manage_students.php' ? 'active fw-bold' : ''; ?>" href="manage_students.php">
                    <i class="fas fa-users-cog fa-fw me-2 text-primary"></i> Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 rounded-3 <?php echo $current_page === 'manage_teachers.php' ? 'active fw-bold' : ''; ?>" href="manage_teachers.php">
                    <i class="fas fa-chalkboard-teacher fa-fw me-2 text-success"></i> Faculty
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 rounded-3 <?php echo $current_page === 'manage_courses.php' ? 'active fw-bold' : ''; ?>" href="manage_courses.php">
                    <i class="fas fa-book-open fa-fw me-2 text-info"></i> Courses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 rounded-3 <?php echo $current_page === 'manage_departments.php' ? 'active fw-bold' : ''; ?>" href="manage_departments.php">
                    <i class="fas fa-building fa-fw me-2 text-warning"></i> Departments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 rounded-3 <?php echo $current_page === 'manage_batches.php' ? 'active fw-bold' : ''; ?>" href="manage_batches.php">
                    <i class="fas fa-layer-group fa-fw me-2 text-purple"></i> Batches
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 rounded-3 <?php echo $current_page === 'approve_students.php' ? 'active fw-bold' : ''; ?>" href="approve_students.php">
                    <i class="fas fa-user-check fa-fw me-2 text-warning"></i> Approvals
                </a>
            </li>
        </ul>

        <div class="border-top pt-3 mt-3 d-flex align-items-center justify-content-between">
            <button class="btn-theme-toggle border-0 p-2 rounded-circle text-main" onclick="toggleAppTheme()" title="Toggle Theme" style="background: var(--bg-body);">
                <i class="fas fa-moon"></i>
            </button>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>
</div>
