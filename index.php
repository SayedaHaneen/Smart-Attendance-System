<?php
// index.php - Main Landing Page & Portal Gateway
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Next-Gen Smart Attendance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/theme.css?v=2" rel="stylesheet">
    <style>
        .landing-wrapper {
            min-height: 100vh;
            background: radial-gradient(circle at 50% 10%, rgba(79, 70, 229, 0.12), transparent 70%), var(--bg-body);
            padding-bottom: 4rem;
        }

        .role-portal-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.25rem 1.5rem;
            text-align: center;
            text-decoration: none;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            height: 100%;
            box-shadow: var(--shadow-sm);
        }

        .role-portal-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            box-shadow: var(--shadow-lg), var(--shadow-primary);
            color: var(--text-main);
        }

        .role-icon-wrapper {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.25rem;
            transition: transform 0.3s ease;
        }

        .role-portal-card:hover .role-icon-wrapper {
            transform: scale(1.1);
        }

        .role-icon-student { background: rgba(79, 70, 229, 0.12); color: #4f46e5; }
        .role-icon-teacher { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .role-icon-admin { background: rgba(239, 68, 68, 0.12); color: #ef4444; }

        .feature-card {
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.25s ease;
            height: 100%;
        }

        .feature-card:hover {
            border-color: var(--primary);
            transform: translateY(-4px);
        }

        .feature-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .badge-live-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px var(--success);
            animation: pulseDot 1.5s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.5; }
        }

        .feature-chip {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* Developer Premium Showcase Card */
        .developer-showcase-card {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.06), rgba(239, 68, 68, 0.04)), var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 1.25rem 2rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .developer-showcase-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #0ea5e9, #ef4444);
        }

        .dev-avatar-glowing {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #4f46e5, #0ea5e9);
            color: #ffffff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.35rem;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
            flex-shrink: 0;
        }

        .dev-contact-btn {
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .dev-contact-btn:hover {
            background: var(--primary);
            color: #ffffff !important;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
        }

        .dev-linkedin-btn {
            background: #0077b5;
            color: #ffffff !important;
            border: none;
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 119, 181, 0.3);
        }

        .dev-linkedin-btn:hover {
            background: #005582;
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 119, 181, 0.4);
        }
    </style>
</head>
<body>
    <div class="landing-wrapper">
        <div class="container" style="max-width: 1200px;">
            
            <!-- Landing Header -->
            <header class="d-flex justify-content-between align-items-center py-4 border-bottom border-light-subtle mb-4">
                <a href="index.php" class="d-flex align-items-center gap-3 text-decoration-none">
                    <div class="brand-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; box-shadow: 0 4px 14px rgba(239,68,68,0.35);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-main" style="letter-spacing:-0.5px;"><?php echo APP_NAME; ?></h4>
                        <span class="small text-muted font-semibold">Next-Gen Academic Attendance Management</span>
                    </div>
                </a>

                <div class="d-flex align-items-center gap-3">
                    <button class="btn-theme-toggle" onclick="toggleAppTheme()" title="Toggle Light/Dark Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </header>

            <!-- Hero Section -->
            <div class="text-center my-5 py-3">
                <div class="d-inline-flex flex-wrap justify-content-center gap-2 mb-3">
                    <span class="feature-chip"><span class="badge-live-dot"></span> Offline Dynamic QR Generation</span>
                    <span class="feature-chip"><i class="fas fa-lock text-danger"></i> Hardware Device Fingerprinting</span>
                    <span class="feature-chip"><i class="fas fa-building text-warning"></i> 7 Departments & 300+ Courses</span>
                </div>
                <h1 class="display-4 fw-extrabold mb-3 text-main" style="letter-spacing: -1px;">Smart Attendance, Simplified.</h1>
                <p class="lead text-muted mx-auto" style="max-width: 720px; font-size: 1.15rem;">
                    Eliminate proxy attendance using offline dynamic QR codes & 4-digit security codes. Fast, secure, and intuitive for students, faculty, and administrators.
                </p>
            </div>

            <!-- Portal Selection Cards -->
            <div class="row g-4 justify-content-center mb-5">
                <!-- Student Portal Card -->
                <div class="col-md-4">
                    <a href="student/login.php" class="role-portal-card">
                        <div class="role-icon-wrapper role-icon-student">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Student Portal</h4>
                        <p class="text-muted small mb-4">Scan session QR codes, enter 4-digit security codes, track personal attendance percentages & view history.</p>
                        <div class="mt-auto w-100 btn btn-primary-custom">
                            Student Portal <i class="fas fa-arrow-right ms-2"></i>
                        </div>
                    </a>
                </div>

                <!-- Teacher Portal Card -->
                <div class="col-md-4">
                    <a href="teacher/login.php" class="role-portal-card">
                        <div class="role-icon-wrapper role-icon-teacher">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Faculty Portal</h4>
                        <p class="text-muted small mb-4">Initiate attendance sessions, broadcast dynamic QR & 4-digit codes, track live check-ins & export rosters.</p>
                        <div class="mt-auto w-100 btn btn-outline-success border-2 rounded-3 fw-semibold py-2">
                            Faculty Portal <i class="fas fa-arrow-right ms-2"></i>
                        </div>
                    </a>
                </div>

                <!-- Admin Portal Card -->
                <div class="col-md-4">
                    <a href="admin/login.php?logout=1" class="role-portal-card">
                        <div class="role-icon-wrapper role-icon-admin">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Admin Portal</h4>
                        <p class="text-muted small mb-4">Review student registration queues, manage faculty members, unlock single-device locks & oversee metrics.</p>
                        <div class="mt-auto w-100 btn btn-outline-danger border-2 rounded-3 fw-semibold py-2">
                            Admin Portal <i class="fas fa-arrow-right ms-2"></i>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Key Features Showcase -->
            <div class="my-5 pt-3">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-main">🚀 System Capabilities & Architecture</h3>
                    <p class="text-muted small">Designed for university-grade attendance compliance and security.</p>
                </div>
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="feature-card">
                            <div class="feature-icon-box bg-danger-subtle text-danger">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <h6 class="fw-bold text-main mb-1">Dynamic QR Refresh</h6>
                            <p class="text-muted small mb-0">QR code refreshes automatically during live sessions to block screenshot sharing.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="feature-card">
                            <div class="feature-icon-box bg-primary-subtle text-primary">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h6 class="fw-bold text-main mb-1">Single-Device Lock</h6>
                            <p class="text-muted small mb-0">Locks student account to 1 registered hardware device to eliminate proxy check-ins.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="feature-card">
                            <div class="feature-icon-box bg-success-subtle text-success">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h6 class="fw-bold text-main mb-1">Live Analytics</h6>
                            <p class="text-muted small mb-0">Instant percentage calculation with automated alerts for low-attendance thresholds.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="feature-card">
                            <div class="feature-icon-box bg-warning-subtle text-warning">
                                <i class="fas fa-sitemap"></i>
                            </div>
                            <h6 class="fw-bold text-main mb-1">4-Stage Cohorts</h6>
                            <p class="text-muted small mb-0">Organized structure: Department &rarr; Batch &rarr; Semester &rarr; Section.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Premium Developer Showcase Banner -->
            <div class="mt-5 pt-5 mb-5">
                <div class="developer-showcase-card animate-slide-up">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
                        
                        <!-- Left: Avatar & Bio -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="dev-avatar-glowing" style="padding: 0; overflow: hidden; display: flex; align-items: center; justify-content: center; background: none; box-shadow: 0 4px 14px rgba(79,70,229,0.25);">
                                <img src="assets/images/sayeda_haneen.png" alt="Sayeda Haneen" style="width: 100%; height: 100%; object-fit: cover; border-radius: 16px;">
                            </div>
                            <div class="d-flex flex-column justify-content-center" style="margin-top: 4px;">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1.5">
                                    <h4 class="fw-extrabold text-main mb-0" style="letter-spacing:-0.5px; line-height: 1.2;">Sayeda Haneen</h4>
                                    <span class="badge bg-primary text-white rounded-pill px-3 py-1" style="font-size:0.7rem; font-weight:700; align-self: center;">Web Developer</span>
                                </div>
                                <div class="text-muted font-semibold small" style="line-height: 1.2;">
                                    <i class="fas fa-university text-warning me-1.5"></i> Computer Science &bull; <span class="text-main">Sukkur IBA University</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Interactive Action Pills -->
                        <div class="d-flex align-items-center gap-2.5 flex-wrap">
                            <a href="mailto:sayedahaneenhussain@gmail.com" class="dev-contact-btn" title="Send Email">
                                <i class="fas fa-envelope text-danger"></i> sayedahaneenhussain@gmail.com
                            </a>
                            <a href="https://www.linkedin.com/in/sayedahaneenhussain/" target="_blank" rel="noopener noreferrer" class="dev-linkedin-btn" title="Connect on LinkedIn">
                                <i class="fab fa-linkedin fa-lg"></i> Connect on LinkedIn
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- System Footer -->
            <footer class="mt-4 pt-3 border-top border-light-subtle text-center text-muted small">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Built with modern security & responsive architecture.
            </footer>

        </div>
    </div>

    <script src="assets/js/theme.js"></script>
</body>
</html>