// attendance.js
$(document).ready(function() {
    // Mark attendance with QR scan
    $('#scanQRBtn').on('click', function() {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            // Camera is available, redirect to scan page
            window.location.href = 'scan_qr.php';
        } else {
            alert('Camera is not available on this device.');
        }
    });
    
    // Generate QR code for session
    $('#generateQRBtn').on('click', function() {
        const sessionId = $(this).data('session-id');
        if (sessionId) {
            window.location.href = `generate_qr.php?session_id=${sessionId}`;
        }
    });
    
    // Auto-refresh live attendance
    if ($('#attendanceBody').length && $('#sessionId').length) {
        const sessionId = $('#sessionId').val();
        setInterval(function() {
            $.ajax({
                url: 'fetch_live_attendance.php',
                type: 'GET',
                data: { session_id: sessionId },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        let html = '';
                        data.attendance.forEach(function(item) {
                            const badgeClass = item.status === 'Present' ? 'success' : 
                                             (item.status === 'Late' ? 'warning' : 'danger');
                            html += `<tr>
                                <td>${item.roll_number}</td>
                                <td>${item.student_name}</td>
                                <td><span class="badge bg-${badgeClass}">${item.status}</span></td>
                                <td>${item.time}</td>
                            </tr>`;
                        });
                        $('#attendanceBody').html(html);
                    }
                }
            });
        }, 5000);
    }
});