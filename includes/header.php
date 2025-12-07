<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

// AUTO DETECT BASE URL — THIS IS THE KEY FIX
$base_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/pages');

// Fetch user profile picture for navbar
$navProfilePic = '';
if (isLoggedIn()) {
    require_once __DIR__ . '/../config/db.php';
    $userStmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = :id");
    $userStmt->execute([':id' => $_SESSION['user_id']]);
    $userData = $userStmt->fetch();
    $navProfilePic = $userData['profile_pic'] ?? '';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/navbar.css">
    <style>
        /* Custom Navbar Styling */
        .navbar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            color: white !important;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            transform: translateY(-2px);
            color: rgba(255, 255, 255, 0.9) !important;
        }

        .navbar-brand i {
            margin-right: 0.5rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 0.2rem;
        }

        .nav-link:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .nav-link i {
            margin-right: 0.4rem;
        }

        /* Profile picture styles for navbar */
        .nav-profile-pic {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }

        .user-badge:hover .nav-profile-pic {
            border-color: white;
            transform: scale(1.05);
        }

        .nav-profile-icon {
            font-size: 35px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .user-badge {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: white !important;
            font-weight: 600;
        }

        .user-badge:hover {
            background: rgba(255, 255, 255, 0.15) !important;
        }

        /* Dropdown Menu */
        .dropdown-menu {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            margin-top: 0.5rem;
        }

        .dropdown-item {
            padding: 0.6rem 1.2rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }

        .dropdown-item i {
            margin-right: 0.5rem;
            width: 20px;
        }

        .dropdown-divider {
            margin: 0.5rem 0;
        }

        /* Login/Register Buttons */
        .btn-login,
        .btn-register {
            color: white !important;
            font-weight: 600;
            padding: 0.5rem 1.2rem !important;
            border-radius: 25px;
            transition: all 0.3s ease;
            margin: 0 0.3rem;
        }

        .btn-login {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .btn-register {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-register:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Mobile responsive */
        @media (max-width: 991px) {
            .nav-link {
                margin: 0.3rem 0;
            }

            .btn-login,
            .btn-register {
                margin: 0.3rem 0;
                display: inline-block;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $base_url ?>/pages/item_list.php">
                <i class="fas fa-boxes"></i>TrackZone
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>/pages/dashboard.php">
                                <i class="fas fa-chart-line"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>/pages/item_list.php">
                                <i class="fas fa-list"></i>Items
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_url ?>/pages/item_add.php">
                                <i class="fas fa-plus-circle"></i>Add Item
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-badge" href="#" role="button" data-bs-toggle="dropdown">
                                <?php if ($navProfilePic): ?>
                                    <img src="<?= $base_url ?>/assets/uploads/profiles/<?= htmlspecialchars($navProfilePic) ?>"
                                        alt="Profile"
                                        class="nav-profile-pic">
                                <?php else: ?>
                                    <i class="fas fa-user-circle nav-profile-icon"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?= $base_url ?>/pages/profile.php">
                                        <i class="fas fa-user"></i>Profile
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= $base_url ?>/pages/logout.php">
                                        <i class="fas fa-sign-out-alt"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn-login" href="<?= $base_url ?>/pages/login.php">
                                <i class="fas fa-sign-in-alt"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn-register" href="<?= $base_url ?>/pages/register.php">
                                <i class="fas fa-user-plus"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php displayFlash(); ?>