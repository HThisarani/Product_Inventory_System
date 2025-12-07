<?php
// pages/login.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$username_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $username_value = $username;

    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            flash('Welcome back, ' . htmlspecialchars($user['username']) . '!', 'success');
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}

include '../includes/header.php';
?>

<style>
    /* Navbar button overrides for login page */
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

    

    /* Login page specific styles */
    .login-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 450px;
    }

    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .login-header h2 {
        color: #333;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .login-header p {
        color: #666;
        font-size: 14px;
    }

    .error-box {
        background: #f8d7da;
        border: 1px solid #f5c2c7;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
    }

    .error-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .error-list li {
        padding: 4px 0;
        color: #842029;
        font-size: 14px;
        display: flex;
        align-items: center;
    }

    .error-list li::before {
        content: '✕';
        margin-right: 8px;
        font-weight: bold;
        color: #dc3545;
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

    .btn-login-submit {
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

    .btn-login-submit:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6a3f8f 100%);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        transform: translateY(-2px);
    }

    .btn-login-submit:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .btn-login-submit:disabled {
        background: #6c757d;
        cursor: not-allowed;
        box-shadow: none;
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

    .register-link {
        text-align: center;
        color: #666;
        font-size: 14px;
    }

    .register-link a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }

    .register-link a:hover {
        color: #5a67d8;
        text-decoration: underline;
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Please login to your account</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" id="loginForm" novalidate>
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input
                    type="text"
                    name="username"
                    id="username"
                    class="form-control <?= !empty($errors) && $username_value !== '' ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($username_value) ?>"
                    required
                    autofocus
                    autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control <?= !empty($errors) ? 'is-invalid' : '' ?>"
                    required
                    autocomplete="current-password">
            </div>

            <button type="submit" class="btn-login-submit">Login</button>
        </form>

        <div class="divider">
            <span>OR</span>
        </div>

        <div class="register-link">
            Don't have an account? <a href="register.php">Create one now</a>
        </div>
    </div>
</div>

<script>
    // Client-side form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        let isValid = true;

        // Reset validation states
        document.getElementById('username').classList.remove('is-invalid');
        document.getElementById('password').classList.remove('is-invalid');

        if (username === '') {
            document.getElementById('username').classList.add('is-invalid');
            isValid = false;
        }

        if (password === '') {
            document.getElementById('password').classList.add('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>