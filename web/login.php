<?php
require_once 'api/config.php';

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <script>
        if ('serviceWorker' in navigator && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }
    </script>
    <title>Login - San Roque Elementary School E-Library</title>
    <link rel="icon" type="image/png" href="assets/logos/school-logo.png?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="shortcut icon" href="assets/logos/school-logo.png?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="stylesheet" href="css/style.css?v=<?php echo CACHE_BUSTER; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <!-- Brand Section -->
            <div class="login-brand">
                <div class="brand-content">
                    <div class="logos">
                        <img src="assets/logos/deped-logo.png" alt="DepEd Logo" class="logo deped-logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%234169E1%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2214%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3EDepEd%3C/text%3E%3C/svg%3E'">
                        <img src="assets/logos/school-logo.png" alt="San Roque ES Logo" class="logo school-logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23228B22%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2214%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3ESRES%3C/text%3E%3C/svg%3E'">
                    </div>
                    <h1>San Roque Elementary School</h1>
                    <h2>E-Library System</h2>
                    <p>Empowering Young Minds Through Digital Learning</p>
                    
                    <div class="brand-footer">
                        <p>&copy; 2026 San Roque ES. All Rights Reserved.</p>
                    </div>
                </div>
            </div>

            <!-- Form Section -->
            <div class="login-main">
                <div class="login-box">
                    <div class="tab-buttons">
                        <button class="tab-btn active" onclick="showTab('login')">Login</button>
                        <button class="tab-btn" onclick="showTab('register')">Register</button>
                    </div>

                    <div class="form-container">
                        <!-- Login Form -->
                        <form id="loginForm" class="auth-form active">
                            <h3><i class="fas fa-sign-in-alt"></i> Welcome Back</h3>
                            <p class="form-subtitle">Please login to your account</p>
                            
                            <div class="form-group">
                                <label for="login-email">Email</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="login-email" name="email" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="login-password">Password</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" id="login-password" name="password" required>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <span>Login</span> <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>

                            <div class="form-footer-links">
                                <a href="#" onclick="showForgotPassword()" class="forgot-password-link">Forgot Password?</a>
                            </div>

                            <div id="login-message" class="message"></div>
                        </form>

                        <!-- Register Form -->
                        <form id="registerForm" class="auth-form" novalidate>
                            <h3><i class="fas fa-user-plus"></i> Create Account</h3>
                            <p class="form-subtitle">Join our digital library today</p>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="reg-fullname">Full Name</label>
                                    <input type="text" id="reg-fullname" name="full_name" placeholder="" required>
                                </div>

                                <div class="form-group">
                                    <label for="reg-username">Username</label>
                                    <input type="text" id="reg-username" name="username" placeholder="" required>
                                </div>

                                <div class="form-group">
                                    <label for="reg-email">Email</label>
                                    <input type="email" id="reg-email" name="email" placeholder="" required>
                                </div>

                                <div class="form-group">
                                    <label for="reg-password">Password</label>
                                    <input type="password" id="reg-password" name="password" placeholder="" required>
                                </div>

                                <div class="form-group">
                                    <label for="reg-user-type">I am a...</label>
                                    <select id="reg-user-type" name="user_type" onchange="toggleGradeLevel()">
                                        <option value="student">Student</option>
                                        <option value="parent">Parent</option>
                                    </select>
                                </div>

                                <div class="form-group" id="grade-level-group">
                                    <label for="reg-grade-level">Grade Level</label>
                                    <select id="reg-grade-level" name="grade_level">
                                        <option value="Kindergarten">Kindergarten</option>
                                        <option value="Grade 1">Grade 1</option>
                                        <option value="Grade 2">Grade 2</option>
                                        <option value="Grade 3">Grade 3</option>
                                        <option value="Grade 4">Grade 4</option>
                                        <option value="Grade 5">Grade 5</option>
                                        <option value="Grade 6">Grade 6</option>
                                    </select>
                                </div>

                                <div class="form-group full-width" id="section-group">
                                    <label for="reg-section">Section (Optional)</label>
                                    <select id="reg-section" name="section_id">
                                        <option value="none">No Section</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-success btn-block">
                                    <span>Register Now</span> <i class="fas fa-user-plus"></i>
                                </button>
                            </div>

                            <div id="register-message" class="message"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeForgotPasswordModal()">&times;</span>
            
            <!-- Step 1: Enter Email -->
            <div id="forgotStep1" class="forgot-step active">
                <h3><i class="fas fa-envelope"></i> Forgot Password</h3>
                <p>Enter your email address and we'll send you an OTP to reset your password.</p>
                
                <div class="form-group">
                    <label for="forgot-email">Email Address</label>
                    <input type="email" id="forgot-email" required>
                </div>
                
                <button onclick="sendOTP()" class="btn btn-primary" id="sendOtpBtn">
                    <i class="fas fa-paper-plane"></i> Send OTP
                </button>
                
                <div id="forgot-message-1" class="message"></div>
            </div>
            
            <!-- Step 2: Enter OTP -->
            <div id="forgotStep2" class="forgot-step">
                <h3><i class="fas fa-shield-alt"></i> Enter OTP</h3>
                <p>We've sent a 6-digit code to your email. Enter it below.</p>
                
                <div class="form-group">
                    <label for="otp-code">OTP Code</label>
                    <input type="text" id="otp-code" maxlength="6" placeholder="000000" required>
                </div>
                
                <button onclick="verifyOTP()" class="btn btn-primary" id="verifyOtpBtn">
                    <i class="fas fa-check"></i> Verify OTP
                </button>
                
                <div class="form-footer">
                    <a href="#" onclick="resendOTP()">Didn't receive code? Resend</a>
                </div>
                
                <div id="forgot-message-2" class="message"></div>
            </div>
            
            <!-- Step 3: Reset Password -->
            <div id="forgotStep3" class="forgot-step">
                <h3><i class="fas fa-lock"></i> Reset Password</h3>
                <p>Enter your new password below.</p>
                
                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" required>
                </div>
                
                <button onclick="resetPassword()" class="btn btn-success" id="resetPasswordBtn">
                    <i class="fas fa-save"></i> Reset Password
                </button>
                
                <div id="forgot-message-3" class="message"></div>
            </div>
        </div>
    </div>

    <script src="js/auth.js?v=<?php echo CACHE_BUSTER; ?>"></script>
</body>
</html>
