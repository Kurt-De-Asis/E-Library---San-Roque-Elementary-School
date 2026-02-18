<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - San Roque Elementary School E-Library</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <div class="logos">
                <img src="assets/logos/deped-logo.png" alt="DepEd Logo" class="logo deped-logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%234169E1%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2214%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3EDepEd%3C/text%3E%3C/svg%3E'">
                <img src="assets/logos/school-logo.png" alt="San Roque ES Logo" class="logo school-logo" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23228B22%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2214%22 fill=%22white%22 text-anchor=%22middle%22 dy=%22.3em%22%3ESRES%3C/text%3E%3C/svg%3E'">
            </div>
            <h1>San Roque Elementary School</h1>
            <h2>E-Library System</h2>
            <p>Empowering Young Minds Through Digital Reading</p>
        </div>

        <div class="login-box">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="showTab('login')">Login</button>
                <button class="tab-btn" onclick="showTab('register')">Register</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="auth-form active">
                <h3><i class="fas fa-sign-in-alt"></i> Login to Your Account</h3>
                
                <div class="form-group">
                    <label for="login-email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="login-email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="login-password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="login-password" name="password" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>

                <div class="form-footer">
                    <a href="#" onclick="showForgotPassword()" class="forgot-password-link">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>

                <div id="login-message" class="message"></div>

            </form>

            <!-- Register Form -->
            <form id="registerForm" class="auth-form">
                <h3><i class="fas fa-user-plus"></i> Create New Account</h3>
                
                <div class="form-group">
                    <label for="reg-fullname"><i class="fas fa-id-card"></i> Full Name</label>
                    <input type="text" id="reg-fullname" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="reg-username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="reg-username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="reg-email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="reg-email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="reg-password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="reg-password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="reg-user-type"><i class="fas fa-users"></i> I am a...</label>
                    <select id="reg-user-type" name="user_type" required onchange="toggleGradeLevel()">
                        <option value="student">Student</option>
                        <option value="parent">Parent</option>
                    </select>
                </div>

                <div class="form-group" id="grade-level-group">
                    <label for="reg-grade-level"><i class="fas fa-graduation-cap"></i> Grade Level</label>
                    <select id="reg-grade-level" name="grade_level">
                        <option value="kindergarten">Kindergarten</option>
                        <option value="grade1">Grade 1</option>
                        <option value="grade2">Grade 2</option>
                        <option value="grade3">Grade 3</option>
                        <option value="grade4">Grade 4</option>
                        <option value="grade5">Grade 5</option>
                        <option value="grade6">Grade 6</option>
                    </select>
                </div>

                <div class="form-group" id="section-group">
                    <label for="reg-section"><i class="fas fa-chalkboard"></i> Section</label>
                    <select id="reg-section" name="section_id" required>
                        <option value="">Select Section</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                </div>

                <div id="register-message" class="message"></div>
            </form>
        </div>

        <div class="login-footer">
            <p>&copy; 2025 San Roque Elementary School. All Rights Reserved.</p>
            <p>Department of Education - Division of San Pedro, Laguna</p>
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

    <script src="js/auth.js"></script>
</body>
</html>
