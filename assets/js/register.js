// register.js
$(document).ready(function() {
    // Generate device ID
    function generateDeviceId() {
        const userAgent = navigator.userAgent;
        const platform = navigator.platform;
        const screenResolution = `${screen.width}x${screen.height}`;
        const timestamp = Date.now();
        const random = Math.random().toString(36).substring(2, 10);
        
        const combined = `${userAgent}|${platform}|${screenResolution}|${timestamp}|${random}`;
        const hash = btoa(combined).substring(0, 32);
        
        $('#deviceId').val(hash);
    }
    
    generateDeviceId();
    
    // Password validation
    $('#password, #confirmPassword').on('input', function() {
        const password = $('#password').val();
        const confirm = $('#confirmPassword').val();
        
        if (password && confirm) {
            if (password === confirm) {
                $('#confirmPassword').removeClass('is-invalid').addClass('is-valid');
            } else {
                $('#confirmPassword').removeClass('is-valid').addClass('is-invalid');
            }
        }
    });
    
    // Form submission
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate passwords match
        const password = $('#password').val();
        const confirm = $('#confirmPassword').val();
        
        if (password !== confirm) {
            alert('Passwords do not match!');
            return;
        }
        
        if (password.length < 6) {
            alert('Password must be at least 6 characters long!');
            return;
        }
        
        // Show loading state
        $('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Registering...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    window.location.href = 'login.php';
                } else {
                    alert(response.message);
                    $('button[type="submit"]').prop('disabled', false).html('Register');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('button[type="submit"]').prop('disabled', false).html('Register');
            }
        });
    });
});