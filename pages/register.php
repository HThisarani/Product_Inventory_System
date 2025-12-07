<?php
// pages/register.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$username_value = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $username_value = $username;
    $email_value = $email;

    // Validation
    if ($username === '') {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    if ($confirm === '') {
        $errors['confirm'] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
        $errors['confirm'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        if ($stmt->fetch()) {
            $errors['username'] = 'Username already taken.';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :e LIMIT 1");
            $stmt->execute([':e' => $email]);
            if ($stmt->fetch()) {
                $errors['email'] = 'Email already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:u, :e, :p)");
                $stmt->execute([
                    ':u' => $username,
                    ':e' => $email,
                    ':p' => $hash
                ]);

                flash('Registration successful! You can now log in.', 'success');
                header('Location: login.php');
                exit;
            }
        }
    }
}

include '../includes/header.php';
?>

<style>
    /* Navbar button overrides for register page */
    .navbar .nav-link.btn-login,
    .navbar .nav-link.btn-register {
        padding: 8px 18px !important;
        border-radius: 20px;
        font-weight: 500;
        transition: all 0.3s ease;
        margin-left: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .navbar .nav-link.btn-login {
        background: transparent;
        border: 2px solid #667eea;
        color: #667eea !important;
    }

    .navbar .nav-link.btn-login:hover {
        background: #667eea;
        border-color: #667eea;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .navbar .nav-link.btn-register {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border: 2px solid transparent;
        color: white !important;
    }

    .navbar .nav-link.btn-register:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6a3f8f 100%) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    /* Register page specific styles */
    .register-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .register-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 450px;
    }

    .register-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .register-header h2 {
        color: #333;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .register-header p {
        color: #666;
        font-size: 14px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-control.is-invalid {
        border-color: #dc3545;
    }

    .form-control.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .invalid-feedback {
        display: block;
        margin-top: 6px;
        font-size: 13px;
        color: #dc3545;
    }

    .password-strength {
        margin-top: 8px;
        font-size: 13px;
    }

    .strength-bar {
        height: 4px;
        background: #e0e0e0;
        border-radius: 2px;
        margin-top: 6px;
        overflow: hidden;
    }

    .strength-fill {
        height: 100%;
        transition: all 0.3s ease;
        width: 0%;
    }

    .strength-weak {
        background: #dc3545;
        width: 33%;
    }

    .strength-medium {
        background: #ffc107;
        width: 66%;
    }

    .strength-strong {
        background: #667eea;
        width: 100%;
    }

    .btn-register-submit {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .navbar .nav-link.btn-register {
        background: #667eea;
        border: 2px solid #667eea;
        color: white !important;
    }

    .navbar .nav-link.btn-register:hover {
        background: #5a67d8;
        border-color: #5a67d8;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .divider {
        text-align: center;
        margin: 24px 0;
        position: relative;
    }

    .divider::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        width: 100%;
        height: 1px;
        background: #e0e0e0;
    }

    .divider span {
        background: white;
        padding: 0 16px;
        color: #666;
        font-size: 14px;
        position: relative;
    }

    .login-link {
        text-align: center;
        color: #666;
        font-size: 14px;
    }

    .login-link a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }

    .login-link a:hover {
        color: #5a67d8;
        text-decoration: underline;
    }
</style>

<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <h2>Create Account</h2>
            <p>Join us today</p>
        </div>

        <form method="post" id="registerForm" novalidate>
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input
                    type="text"
                    name="username"
                    id="username"
                    class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($username_value) ?>"
                    required
                    autofocus
                    autocomplete="username">
                <?php if (isset($errors['username'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($email_value) ?>"
                    required
                    autocomplete="email">
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    required
                    autocomplete="new-password">
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                <?php else: ?>
                    <div class="password-strength">
                        <span id="strengthText"></span>
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthBar"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                    required
                    autocomplete="new-password">
                <?php if (isset($errors['confirm'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-register-submit">Create Account</button>
        </form>

        <div class="divider">
            <span>OR</span>
        </div>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

<script>
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthText = document.getElementById('strengthText');
    const strengthBar = document.getElementById('strengthBar');

    if (passwordInput && strengthText && strengthBar) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);

            strengthBar.className = 'strength-fill';

            if (password.length === 0) {
                strengthText.textContent = '';
                strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
            } else if (strength < 3) {
                strengthText.textContent = 'Weak password';
                strengthBar.classList.add('strength-weak');
            } else if (strength < 4) {
                strengthText.textContent = 'Medium password';
                strengthBar.classList.add('strength-medium');
            } else {
                strengthText.textContent = 'Strong password';
                strengthBar.classList.add('strength-strong');
            }
        });
    }

    function calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.length >= 10) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        return strength;
    }

    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        let isValid = true;

        // Reset validation states
        document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            if (!el.closest('.password-strength')) {
                el.remove();
            }
        });

        if (username.length < 3) {
            showError('username', 'Username must be at least 3 characters');
            isValid = false;
        }

        if (!isValidEmail(email)) {
            showError('email', 'Please enter a valid email address');
            isValid = false;
        }

        if (password.length < 6) {
            showError('password', 'Password must be at least 6 characters');
            isValid = false;
        }

        if (password !== confirm) {
            showError('confirm_password', 'Passwords do not match');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        field.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        field.parentNode.appendChild(feedback);
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
</script>

<?php include '../includes/footer.php'; ?>