// dashboard.js
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Auto-refresh dashboard data every 30 seconds (if on dashboard page)
    if ($('#dashboardStats').length) {
        setInterval(function() {
            $.ajax({
                url: 'get_stats.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        // Update stats
                        if ($('#studentCount')) $('#studentCount').text(data.students);
                        if ($('#teacherCount')) $('#teacherCount').text(data.teachers);
                        if ($('#todayAttendance')) $('#todayAttendance').text(data.today_attendance);
                    }
                }
            });
        }, 30000);
    }
    
    // Confirm delete actions
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});