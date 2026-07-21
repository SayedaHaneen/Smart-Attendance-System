// login.js
$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        // Hide previous alert
        $('#formAlert').addClass('d-none').removeClass('alert-success alert-danger');
        
        // Show loading state
        $('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Logging in...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#formAlert').removeClass('d-none alert-danger').addClass('alert-success')
                        .text(response.message);
                    
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                } else {
                    $('#formAlert').removeClass('d-none alert-success').addClass('alert-danger')
                        .text(response.message);
                    $('button[type="submit"]').prop('disabled', false).html('Login');
                }
            },
            error: function() {
                $('#formAlert').removeClass('d-none alert-success').addClass('alert-danger')
                    .text('An error occurred. Please try again.');
                $('button[type="submit"]').prop('disabled', false).html('Login');
            }
        });
    });
    
    // Password show/hide toggle
    $('.toggle-password').on('click', function() {
        const input = $($(this).data('target'));
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });
});