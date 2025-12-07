<?php
// pages/profile.php
require_once '../includes/functions.php';
requireLogin();
require_once '../config/db.php';

$userId = $_SESSION['user_id'];

// Fetch current user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();
if (!$user) {
    flash('User not found.', 'danger');
    header('Location: login.php');
    exit;
}

$username = $user['username'];
$email = $user['email'];
$profilePic = $user['profile_pic'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // gather inputs
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($username === '') {
        flash('Username cannot be empty.', 'danger');
    } elseif ($newPassword !== '' && $newPassword !== $confirm) {
        flash('Passwords do not match.', 'danger');
    } else {
        // Build update query
        $fields = [];
        $params = [':id' => $userId];

        $fields[] = "username = :username";
        $params[':username'] = $username;

        $fields[] = "email = :email";
        $params[':email'] = $email;

        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/profiles/';

            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
            $fileName = $_FILES['profile_pic']['name'];
            $fileSize = $_FILES['profile_pic']['size'];
            $fileType = $_FILES['profile_pic']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            // Allowed extensions - all common image formats
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'tiff', 'tif', 'jfif', 'pjpeg', 'pjp', 'avif', 'apng'];

            if (in_array($fileExtension, $allowedExts)) {
                // Generate unique filename
                $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                    // Delete old profile picture if exists
                    if ($profilePic && file_exists($uploadDir . $profilePic)) {
                        unlink($uploadDir . $profilePic);
                    }

                    $fields[] = "profile_pic = :profile_pic";
                    $params[':profile_pic'] = $newFileName;
                } else {
                    flash('Error uploading profile picture.', 'danger');
                }
            } else {
                flash('Invalid file type. Only image files are allowed.', 'danger');
            }
        }

        if ($newPassword !== '') {
            // Hash the new password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $fields[] = "password = :password";
            $params[':password'] = $passwordHash;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        flash('Profile updated successfully.', 'success');
        // Optionally refresh the page to show updated data
        header('Location: profile.php');
        exit;
    }
}

include '../includes/header.php';
?>

<style>
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .profile-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2rem;
        text-align: center;
        color: white;
    }

    .profile-header h2 {
        margin: 0;
        font-weight: 700;
    }

    .profile-pic-section {
        text-align: center;
        margin: 2rem auto 0;
        position: relative;
        padding: 2rem 0;
    }

    .profile-pic-wrapper {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid white;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        margin: 0 auto 1rem;
        background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .profile-pic-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-pic-wrapper .no-pic {
        font-size: 4rem;
        color: #999;
    }

    .profile-pic-upload {
        margin-top: 0.5rem;
    }

    .profile-pic-upload label {
        cursor: pointer;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .profile-pic-upload label:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .profile-pic-upload input[type="file"] {
        display: none;
    }

    .profile-form-section {
        padding: 2rem;
    }

    .form-section-title {
        color: #333;
        font-weight: 700;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f0f0f0;
    }

    .form-label {
        font-weight: 600;
        color: #555;
        margin-bottom: 0.5rem;
    }

    .form-control {
        border-radius: 8px;
        border: 2px solid #e0e0e0;
        padding: 0.6rem 0.75rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 0.6rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background-color: #6c757d;
        border: none;
        padding: 0.6rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }

    .password-section {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 10px;
        margin-top: 1.5rem;
    }

    .password-section h5 {
        color: #333;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .profile-form-section {
            padding: 1.5rem;
        }

        .profile-pic-wrapper {
            width: 120px;
            height: 120px;
        }
    }
</style>

<div class="container mt-4">
    <div class="profile-container">
        <?php displayFlash(); ?>

        <div class="profile-card">
            <div class="profile-header">
                <h2><i class="fas fa-user-circle me-2"></i>My Profile</h2>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="profile-pic-section">
                    <div class="profile-pic-wrapper">
                        <?php if ($profilePic): ?>
                            <img src="../assets/uploads/profiles/<?= htmlspecialchars($profilePic) ?>"
                                alt="Profile Picture" id="profilePreview">
                        <?php else: ?>
                            <i class="fas fa-user no-pic" id="profilePreview"></i>
                        <?php endif; ?>
                    </div>
                    <div class="profile-pic-upload">
                        <label for="profile_pic">
                            <i class="fas fa-camera me-2"></i>Change Photo
                        </label>
                        <input type="file"
                            id="profile_pic"
                            name="profile_pic"
                            accept="image/*"
                            onchange="previewImage(event)">
                    </div>
                </div>

                <div class="profile-form-section">
                    <h4 class="form-section-title">
                        <i class="fas fa-info-circle me-2"></i>Personal Information
                    </h4>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-2"></i>Username
                        </label>
                        <input type="text"
                            name="username"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($username) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-2"></i>Email
                        </label>
                        <input type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($email) ?>">
                    </div>

                    <div class="password-section">
                        <h5>
                            <i class="fas fa-lock me-2"></i>Change Password
                        </h5>
                        <p class="text-muted small mb-3">Leave blank if you don't want to change your password</p>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter new password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password"
                                name="confirm_password"
                                class="form-control"
                                placeholder="Confirm new password">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="item_list.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function previewImage(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('profilePreview');
        const wrapper = preview.parentElement;

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Remove existing content
                wrapper.innerHTML = '';

                // Create new img element
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Profile Preview';
                img.id = 'profilePreview';

                wrapper.appendChild(img);
            }
            reader.readAsDataURL(file);
        }
    }
</script>

<?php include '../includes/footer.php'; ?>