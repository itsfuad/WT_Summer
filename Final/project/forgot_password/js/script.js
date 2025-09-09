
function togglePasswords() {
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const toggleIcon = document.getElementById('password-toggle');
    
    if (newPasswordField.type === 'password') {
        newPasswordField.type = 'text';
        confirmPasswordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        newPasswordField.type = 'password';
        confirmPasswordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Form helpers
function showError(fieldId, message) {
    const errorElement = document.getElementById(fieldId + '-error');
    const inputElement = document.getElementById(fieldId);
    
    if (errorElement && inputElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        inputElement.classList.add('error');
    }
}

function clearError(fieldId) {
    const errorElement = document.getElementById(fieldId + '-error');
    const inputElement = document.getElementById(fieldId);
    
    if (errorElement && inputElement) {
        errorElement.style.display = 'none';
        inputElement.classList.remove('error');
    }
}

function setLoading(formId, isLoading) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const submitBtn = form.querySelector('.submit-btn');
    const btnText = submitBtn?.querySelector('.btn-text');
    const btnLoader = submitBtn?.querySelector('.btn-loader');
    const inputs = form.querySelectorAll('input');

    if (isLoading) {
        if (btnText) btnText.style.display = 'none';
        if (btnLoader) btnLoader.style.display = 'inline';
        if (submitBtn) submitBtn.disabled = true;
        inputs.forEach(input => input.disabled = true);
    } else {
        if (btnText) btnText.style.display = 'inline';
        if (btnLoader) btnLoader.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
        inputs.forEach(input => input.disabled = false);
    }
}

// Password validation
function validatePassword(password, confirmPassword = null) {
    if (!password) {
        return "Password is required";
    }
    if (password.length < 6) {
        return "Password must be at least 6 characters";
    }
    if (confirmPassword !== null && password !== confirmPassword) {
        return "Passwords do not match";
    }
    return null;
}

function showMessage(message, isSuccess = false) {
    const messageArea = document.getElementById('message-area');
    messageArea.innerHTML = `
        <div style="color: ${isSuccess ? '#2ed573' : '#ff4757'}; text-align: center; padding: 10px; background: ${isSuccess ? '#f0fff4' : '#fff5f5'}; border-radius: 5px;">
            <i class="fas fa-${isSuccess ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}
        </div>
    `;
}

function showStep(stepNumber) {
    document.querySelectorAll('.step-content').forEach(step => step.style.display = 'none');
    document.getElementById(`step${stepNumber}`).style.display = 'block';
    
    const descriptions = [
        'Enter your email address to receive an OTP',
        'Enter the 6-digit OTP sent to your email',
        'Create a new password for your account'
    ];
    document.getElementById('step-description').textContent = descriptions[stepNumber - 1];
}

// Step 1: Send OTP
document.getElementById('emailForm').onsubmit = function(e) {
    e.preventDefault();
    const email = document.getElementById('email').value;
    
    // Clear previous errors
    clearError('email');
    
    // Validate email
    if (!email || !email.includes('@')) {
        showError('email', 'Please enter a valid email address');
        return;
    }
    
    setLoading('emailForm', true);
    
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=send_otp&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        setLoading('emailForm', false);
        showMessage(data.message, data.success);
        if (data.success) {
            document.getElementById('user-email').textContent = email;
            showStep(2);
            startResendTimer(); // Start the 60-second timer
        }
    })
    .catch(() => {
        setLoading('emailForm', false);
        showMessage('Network error. Please try again.');
    });
};

// Step 2: Verify OTP
document.getElementById('otpForm').onsubmit = function(e) {
    e.preventDefault();
    const otp = document.getElementById('otp').value;
    
    // Clear previous errors
    clearError('otp');
    
    // Validate OTP
    if (!otp || otp.length !== 6 || !/^\d{6}$/.test(otp)) {
        showError('otp', 'Please enter a valid 6-digit OTP');
        return;
    }
    
    setLoading('otpForm', true);
    
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=verify_otp&otp=${encodeURIComponent(otp)}`
    })
    .then(response => response.json())
    .then(data => {
        setLoading('otpForm', false);
        showMessage(data.message, data.success);
        if (data.success) {
            showStep(3);
        }
    })
    .catch(() => {
        setLoading('otpForm', false);
        showMessage('Network error. Please try again.');
    });
};

// Step 3: Reset Password
document.getElementById('passwordForm').onsubmit = function(e) {
    e.preventDefault();
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Clear previous errors
    clearError('new_password');
    clearError('confirm_password');
    
    let hasError = false;
    
    // Validate password
    const passwordError = validatePassword(newPassword);
    if (passwordError) {
        showError('new_password', passwordError);
        hasError = true;
    }
    
    // Validate confirm password
    const confirmError = validatePassword(newPassword, confirmPassword);
    if (confirmError && confirmError.includes('match')) {
        showError('confirm_password', confirmError);
        hasError = true;
    }
    
    if (hasError) {
        return;
    }
    
    setLoading('passwordForm', true);
    
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=reset_password&new_password=${encodeURIComponent(newPassword)}&confirm_password=${encodeURIComponent(confirmPassword)}`
    })
    .then(response => response.json())
    .then(data => {
        setLoading('passwordForm', false);
        showMessage(data.message, data.success);
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        }
    })
    .catch(() => {
        setLoading('passwordForm', false);
        showMessage('Network error. Please try again.');
    });
};

// Timer variables
let resendTimer = null;
let countdownSeconds = 60;

// Start countdown timer
function startResendTimer() {
    const resendBtn = document.getElementById('resendBtn');
    const timerDiv = document.getElementById('resendTimer');
    const countdownSpan = document.getElementById('countdown');
    
    countdownSeconds = 60;
    resendBtn.style.display = 'none';
    timerDiv.style.display = 'block';
    
    resendTimer = setInterval(() => {
        countdownSeconds--;
        countdownSpan.textContent = countdownSeconds;
        
        if (countdownSeconds <= 0) {
            clearInterval(resendTimer);
            resendBtn.style.display = 'inline-block';
            timerDiv.style.display = 'none';
            resendBtn.disabled = false;
            resendBtn.innerHTML = '<i class="fas fa-redo"></i> Resend OTP';
        }
    }, 1000);
}

// Resend OTP
function resendOTP() {
    const email = document.getElementById('user-email').textContent;
    const resendBtn = document.getElementById('resendBtn');
    
    // Disable resend button and show loading
    resendBtn.disabled = true;
    resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    // Disable OTP form during resend
    setLoading('otpForm', true);
    
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=send_otp&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        setLoading('otpForm', false);
        showMessage(data.message, data.success);
        
        if (data.success) {
            // Clear the OTP field and start timer
            document.getElementById('otp').value = '';
            startResendTimer();
        } else {
            // Re-enable button if failed
            resendBtn.disabled = false;
            resendBtn.innerHTML = '<i class="fas fa-redo"></i> Resend OTP';
        }
    })
    .catch(() => {
        setLoading('otpForm', false);
        showMessage('Network error. Please try again.');
        resendBtn.disabled = false;
        resendBtn.innerHTML = '<i class="fas fa-redo"></i> Resend OTP';
    });
}

// Cleanup timer when page unloads
window.addEventListener('beforeunload', function() {
    if (resendTimer) {
        clearInterval(resendTimer);
    }
});

// Real-time validation for password fields
document.addEventListener('DOMContentLoaded', function() {
    // Password field validation
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (newPasswordField) {
        newPasswordField.addEventListener('input', function() {
            clearError('new_password');
            const error = validatePassword(this.value);
            if (error && this.value.length > 0) {
                showError('new_password', error);
            }
        });
    }
    
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            clearError('confirm_password');
            const password = newPasswordField.value;
            const error = validatePassword(password, this.value);
            if (error && error.includes('match') && this.value.length > 0) {
                showError('confirm_password', error);
            }
        });
    }
    
    // Email field validation
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.addEventListener('blur', function() {
            clearError('email');
            if (this.value && !this.value.includes('@')) {
                showError('email', 'Please enter a valid email address');
            }
        });
    }
    
    // OTP field validation
    const otpField = document.getElementById('otp');
    if (otpField) {
        otpField.addEventListener('input', function() {
            // Only allow digits
            this.value = this.value.replace(/[^0-9]/g, '');
            clearError('otp');
        });
    }
});