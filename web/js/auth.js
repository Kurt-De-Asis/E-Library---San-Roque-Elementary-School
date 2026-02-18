// Authentication JavaScript for E-Library System
// San Roque Elementary School

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the login page
    if (document.querySelector('.login-page')) {
        initializeLoginPage();
    }
});

// Initialize login page
function initializeLoginPage() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.textContent.toLowerCase();
            showTab(tabName);
        });
    });

    // Form submissions
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    // Grade level toggle for registration
    const userTypeSelect = document.getElementById('reg-user-type');
    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', toggleGradeLevel);
    }

    // Grade level change for section loading
    const gradeLevelSelect = document.getElementById('reg-grade-level');
    if (gradeLevelSelect) {
        gradeLevelSelect.addEventListener('change', loadSections);
    }
}

// Show tab (login/register)
function showTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeTabBtn = document.querySelector(`[onclick="showTab('${tabName}')"]`);
    if (activeTabBtn) {
        activeTabBtn.classList.add('active');
    }

    // Update forms
    document.querySelectorAll('.auth-form').forEach(form => {
        form.classList.remove('active');
    });
    document.getElementById(`${tabName}Form`).classList.add('active');
}

// Handle login
async function handleLogin(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    const messageDiv = document.getElementById('login-message');

    // Show loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';

    try {
        const response = await fetch('api/auth.php?action=login', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage(messageDiv, 'Login successful! Redirecting...', 'success');

            // Redirect after short delay
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        } else {
            showMessage(messageDiv, result.message, 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
    }
}

// Handle registration
async function handleRegister(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('button[type="submit"]');
    const messageDiv = document.getElementById('register-message');

    // Validate passwords
    const password = formData.get('password');
    if (password.length < 6) {
        showMessage(messageDiv, 'Password must be at least 6 characters long.', 'error');
        return;
    }

    // Show loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';

    try {
        const response = await fetch('api/auth.php?action=register', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage(messageDiv, result.message, 'success');

            // Switch to login tab after short delay
            setTimeout(() => {
                showTab('login');
            }, 2000);
        } else {
            showMessage(messageDiv, result.message, 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-user-plus"></i> Register';
    }
}

// Toggle grade level and section fields based on user type
function toggleGradeLevel() {
    const userType = document.getElementById('reg-user-type').value;
    const gradeLevelGroup = document.getElementById('grade-level-group');
    const sectionGroup = document.getElementById('section-group');

    if (userType === 'student') {
        gradeLevelGroup.style.display = 'block';
        if (sectionGroup) sectionGroup.style.display = 'block';
    } else {
        gradeLevelGroup.style.display = 'none';
        if (sectionGroup) sectionGroup.style.display = 'none';
    }
}

// Load sections based on selected grade level
async function loadSections() {
    const gradeLevel = document.getElementById('reg-grade-level').value;
    const sectionSelect = document.getElementById('reg-section');

    if (!sectionSelect || !gradeLevel) {
        return;
    }

    // Reset section select
    sectionSelect.innerHTML = '<option value="">Loading sections...</option>';

    try {
        const response = await fetch(`api/admin.php?action=get_sections_by_grade&grade_level=${gradeLevel}`);
        const result = await response.json();

        sectionSelect.innerHTML = '<option value="">Select Section</option>';

        if (result.success && result.sections.length > 0) {
            result.sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section.section_id;
                option.textContent = section.section_name;
                sectionSelect.appendChild(option);
            });
        } else {
            sectionSelect.innerHTML = '<option value="">No sections available</option>';
        }
    } catch (error) {
        console.error('Error loading sections:', error);
        sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
    }
}

// Logout function
async function logout() {
    try {
        const response = await fetch('api/auth.php?action=logout');
        const result = await response.json();

        if (result.success) {
            // Clear any cached data
            localStorage.clear();
            sessionStorage.clear();

            // Redirect to login
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Logout error:', error);
        // Force redirect even if logout fails
        window.location.href = 'login.php';
    }
}

// Show message
function showMessage(element, message, type) {
    element.innerHTML = message;
    element.className = `message ${type}`;
    element.style.display = 'block';

    // Auto hide after 5 seconds for success messages
    if (type === 'success') {
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }
}

// Check session status
async function checkSession() {
    try {
        const response = await fetch('api/auth.php?action=check-session');
        const result = await response.json();

        return result;
    } catch (error) {
        console.error('Session check error:', error);
        return { success: true, logged_in: false };
    }
}

// Utility function to get form data as object
function getFormData(form) {
    const data = {};
    const formData = new FormData(form);

    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }

    return data;
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;

    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    return strength;
}

// Real-time password strength indicator
function initializePasswordStrength() {
    const passwordInput = document.getElementById('reg-password');
    if (!passwordInput) return;

    const strengthIndicator = document.createElement('div');
    strengthIndicator.id = 'password-strength';
    strengthIndicator.className = 'password-strength';
    passwordInput.parentNode.appendChild(strengthIndicator);

    passwordInput.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        const messages = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];

        strengthIndicator.textContent = this.value ? messages[strength] : '';
        strengthIndicator.className = `password-strength strength-${strength}`;
    });
}

// Add this to the DOMContentLoaded if on registration page
if (document.getElementById('registerForm')) {
    initializePasswordStrength();
}

// =====================================================
// FORGOT PASSWORD FUNCTIONS
// =====================================================

let forgotPasswordEmail = '';

// Show forgot password modal
function showForgotPassword() {
    const modal = document.getElementById('forgotPasswordModal');
    modal.style.display = 'block';
    
    // Reset to step 1
    showForgotStep(1);
    
    // Clear all inputs
    document.getElementById('forgot-email').value = '';
    document.getElementById('otp-code').value = '';
    document.getElementById('new-password').value = '';
    document.getElementById('confirm-password').value = '';
    
    // Clear messages
    clearForgotMessages();
}

// Close forgot password modal
function closeForgotPasswordModal() {
    const modal = document.getElementById('forgotPasswordModal');
    modal.style.display = 'none';
    forgotPasswordEmail = '';
}

// Show specific step
function showForgotStep(stepNumber) {
    document.querySelectorAll('.forgot-step').forEach(step => {
        step.classList.remove('active');
    });
    document.getElementById(`forgotStep${stepNumber}`).classList.add('active');
}

// Clear all forgot password messages
function clearForgotMessages() {
    document.getElementById('forgot-message-1').style.display = 'none';
    document.getElementById('forgot-message-2').style.display = 'none';
    document.getElementById('forgot-message-3').style.display = 'none';
}

// Send OTP to email
async function sendOTP() {
    const emailInput = document.getElementById('forgot-email');
    const email = emailInput.value.trim();
    const btn = document.getElementById('sendOtpBtn');
    const messageDiv = document.getElementById('forgot-message-1');
    
    if (!email) {
        showMessage(messageDiv, 'Please enter your email address', 'error');
        return;
    }
    
    // Validate email format
    if (!isValidEmail(email)) {
        showMessage(messageDiv, 'Please enter a valid email address', 'error');
        return;
    }
    
    // Show loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    try {
        const response = await fetch('api/auth.php?action=send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        });
        
        const result = await response.json();
        
        if (result.success) {
            forgotPasswordEmail = email;
            
            // For development: show OTP if email couldn't be sent
            if (result.debug_otp) {
                showMessage(messageDiv, `Your OTP code is: <strong>${result.debug_otp}</strong><br><small>(Email not configured on this server)</small>`, 'success');
            } else {
                showMessage(messageDiv, 'OTP sent to your email!', 'success');
            }
            
            // Move to step 2 after short delay
            setTimeout(() => {
                showForgotStep(2);
            }, 2500);
        } else {
            showMessage(messageDiv, result.message || 'Failed to send OTP', 'error');
        }
    } catch (error) {
        console.error('Send OTP error:', error);
        showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send OTP';
    }
}

// Resend OTP
async function resendOTP() {
    if (!forgotPasswordEmail) {
        showForgotStep(1);
        return;
    }
    
    const messageDiv = document.getElementById('forgot-message-2');
    
    try {
        const response = await fetch('api/auth.php?action=send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: forgotPasswordEmail })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(messageDiv, 'New OTP sent to your email!', 'success');
        } else {
            showMessage(messageDiv, result.message || 'Failed to resend OTP', 'error');
        }
    } catch (error) {
        console.error('Resend OTP error:', error);
        showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
    }
}

// Verify OTP
async function verifyOTP() {
    const otpInput = document.getElementById('otp-code');
    const otp = otpInput.value.trim();
    const btn = document.getElementById('verifyOtpBtn');
    const messageDiv = document.getElementById('forgot-message-2');
    
    if (!otp || otp.length !== 6) {
        showMessage(messageDiv, 'Please enter the 6-digit OTP code', 'error');
        return;
    }
    
    // Show loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    
    try {
        const response = await fetch('api/auth.php?action=verify-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                email: forgotPasswordEmail,
                otp: otp 
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(messageDiv, 'OTP verified!', 'success');
            
            // Move to step 3 after short delay
            setTimeout(() => {
                showForgotStep(3);
            }, 1000);
        } else {
            showMessage(messageDiv, result.message || 'Invalid OTP', 'error');
        }
    } catch (error) {
        console.error('Verify OTP error:', error);
        showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Verify OTP';
    }
}

// Reset password
async function resetPassword() {
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const btn = document.getElementById('resetPasswordBtn');
    const messageDiv = document.getElementById('forgot-message-3');
    
    // Validate passwords
    if (!newPassword || newPassword.length < 6) {
        showMessage(messageDiv, 'Password must be at least 6 characters', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showMessage(messageDiv, 'Passwords do not match', 'error');
        return;
    }
    
    // Show loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
    
    try {
        const response = await fetch('api/auth.php?action=reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                email: forgotPasswordEmail,
                password: newPassword 
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(messageDiv, 'Password reset successfully!', 'success');
            
            // Close modal and show login after delay
            setTimeout(() => {
                closeForgotPasswordModal();
                const loginMessage = document.getElementById('login-message');
                showMessage(loginMessage, 'Password reset! Please login with your new password.', 'success');
            }, 1500);
        } else {
            showMessage(messageDiv, result.message || 'Failed to reset password', 'error');
        }
    } catch (error) {
        console.error('Reset password error:', error);
        showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Reset Password';
    }
}

// Validate email format
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('forgotPasswordModal');
    if (event.target === modal) {
        closeForgotPasswordModal();
    }
});
