<?php
// pages/item_add.php
require_once '../includes/functions.php';
requireLogin();
require_once '../config/db.php';

// Initialize variables
$name = '';
$price = '';
$stock = '';
$category = '';
$errors = [];

// Get categories from database
$categoriesStmt = $pdo->query("SELECT name FROM categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $name = trim($_POST['name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $categoryType = trim($_POST['category_type'] ?? '');

    // Determine which category to use
    if ($categoryType === 'new') {
        $category = trim($_POST['new_category'] ?? '');

        // Add new category to database
        if ($category !== '') {
            try {
                $insertCatStmt = $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (:name)");
                $insertCatStmt->execute([':name' => $category]);
            } catch (PDOException $e) {
                // Category might already exist, which is fine
            }
        }
    } else {
        $category = trim($_POST['existing_category'] ?? '');
    }

    $imageFilenames = [];

    // Validation - NAME (REQUIRED)
    if ($name === '') {
        $errors['name'] = 'Item name is required.';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Item name must be at least 3 characters.';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Item name must not exceed 100 characters.';
    }

    // Validation - PRICE (REQUIRED)
    if ($price === '') {
        $errors['price'] = 'Price is required.';
    } elseif (!is_numeric($price) || $price < 0) {
        $errors['price'] = 'Price must be a valid positive number.';
    }

    // Validation - STOCK (REQUIRED)
    if ($stock === '') {
        $errors['stock'] = 'Stock quantity is required.';
    } elseif (!is_numeric($stock) || $stock < 0 || floor($stock) != $stock) {
        $errors['stock'] = 'Stock must be a valid positive whole number.';
    }

    // Validation - CATEGORY (REQUIRED)
    if ($category === '') {
        $errors['category'] = 'Category is required.';
    } elseif (strlen($category) > 50) {
        $errors['category'] = 'Category must not exceed 50 characters.';
    }

    // Validation - MULTIPLE IMAGES (At least one required)
    if (empty($_FILES['images']['name'][0])) {
        $errors['images'] = 'At least one product image is required.';
    } else {
        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $maxSize = 5 * 1024 * 1024;
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'tiff', 'tif', 'jfif', 'pjpeg', 'pjp', 'avif', 'apng'];
        $maxImages = 5;

        $totalFiles = count($_FILES['images']['name']);
        if ($totalFiles > $maxImages) {
            $errors['images'] = "Maximum $maxImages images allowed.";
        } else {
            foreach ($_FILES['images']['name'] as $key => $filename) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['images']['tmp_name'][$key];
                    $size = $_FILES['images']['size'][$key];

                    if ($size > $maxSize) {
                        $errors['images'] = 'Each image must not exceed 5MB.';
                        break;
                    }

                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed)) {
                        $errors['images'] = 'Invalid image format. Only image files are allowed.';
                        break;
                    }

                    $imageInfo = getimagesize($tmpName);
                    if ($imageInfo === false) {
                        $errors['images'] = 'One or more uploaded files are not valid images.';
                        break;
                    }

                    $uniqueFilename = uniqid('img_', true) . '.' . $ext;
                    $dest = $uploadDir . $uniqueFilename;

                    if (move_uploaded_file($tmpName, $dest)) {
                        $imageFilenames[] = $uniqueFilename;
                    } else {
                        $errors['images'] = 'Failed to save one or more images.';
                        break;
                    }
                }
            }
        }
    }

    // Check if any images were successfully uploaded
    if (empty($errors['images']) && empty($imageFilenames)) {
        $errors['images'] = 'No images were successfully uploaded. Please try again.';
    }

    if (empty($errors)) {
        try {
            // Store multiple images as comma-separated string
            $imagesString = implode(',', $imageFilenames);

            $stmt = $pdo->prepare("INSERT INTO items 
                (name, price, stock, image, category, created_by) 
                VALUES (:name, :price, :stock, :image, :category, :created_by)");
            $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':stock' => $stock,
                ':image' => $imagesString,
                ':category' => $category,
                ':created_by' => $_SESSION['user_id']
            ]);

            flash('Item added successfully!', 'success');
            header('Location: item_list.php');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = 'Database error: Unable to add item.';
            error_log($e->getMessage());
        }
    }
}

include '../includes/header.php';
?>

<style>
    .add-item-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .page-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid #f0f0f0;
    }

    .page-header h2 {
        font-weight: 700;
        color: #333;
        margin: 0;
    }

    .page-header p {
        color: #6c757d;
        margin: 0.5rem 0 0 0;
    }

    /* Multi-step Form Styles */
    .form-wizard {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .wizard-steps {
        display: flex;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 0;
        margin: 0;
    }

    .wizard-step {
        flex: 1;
        padding: 1.5rem;
        text-align: center;
        color: rgba(255, 255, 255, 0.6);
        position: relative;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .wizard-step.active {
        color: white;
        background: rgba(255, 255, 255, 0.1);
    }

    .wizard-step.completed {
        color: white;
    }

    .wizard-step.completed .step-number {
        background-color: #38ef7d;
    }

    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        font-weight: 700;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .wizard-step.active .step-number {
        background-color: white;
        color: #667eea;
        transform: scale(1.1);
    }

    .step-title {
        font-size: 0.9rem;
        font-weight: 600;
    }

    .wizard-content {
        padding: 2rem;
    }

    .step-content {
        display: none;
    }

    .step-content.active {
        display: block;
        animation: fadeIn 0.4s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .form-label .required {
        color: #dc3545;
        margin-left: 2px;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        border: 2px solid #e0e0e0;
        padding: 0.6rem 0.75rem;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #dc3545 !important;
    }

    .input-group .form-control.is-invalid {
        border-color: #dc3545 !important;
        border-left: 2px solid #dc3545 !important;
    }

    .input-group-text {
        border-radius: 8px 0 0 8px;
        border: 2px solid #e0e0e0;
        border-right: none;
        background-color: #f8f9fa;
        color: #6c757d;
        font-weight: 600;
    }

    .input-group .form-control {
        border-left: none;
        border-radius: 0 8px 8px 0;
    }

    .error-message {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }

    .form-text {
        color: #6c757d;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .category-options {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1rem;
    }

    .category-radio {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .category-radio input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #667eea;
    }

    .category-radio label {
        margin: 0;
        cursor: pointer;
        font-weight: 500;
        color: #555;
    }

    .category-input-group {
        display: none;
    }

    .category-input-group.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    /* Multiple Image Upload */
    .image-upload-area {
        border: 3px dashed #e0e0e0;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background-color: #f8f9fa;
    }

    .image-upload-area:hover {
        border-color: #667eea;
        background-color: #f0f2ff;
    }

    .image-upload-area.dragover {
        border-color: #667eea;
        background-color: #e7eaff;
        transform: scale(1.02);
    }

    .upload-icon {
        font-size: 3rem;
        color: #667eea;
        margin-bottom: 1rem;
    }

    .upload-text {
        color: #6c757d;
        margin: 0;
    }

    .upload-text strong {
        color: #667eea;
        cursor: pointer;
    }

    .file-input {
        display: none;
    }

    .image-preview-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .image-preview-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .image-preview-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }

    .remove-image {
        position: absolute;
        top: 5px;
        right: 5px;
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .remove-image:hover {
        background-color: #c82333;
        transform: scale(1.1);
    }

    .wizard-buttons {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 2px solid #f0f0f0;
    }

    .btn-wizard {
        padding: 0.6rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }

    .btn-prev {
        background-color: #6c757d;
        color: white;
    }

    .btn-prev:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
        color: white;
    }

    .btn-next {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-next:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-submit {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(17, 153, 142, 0.4);
        color: white;
    }

    @media (max-width: 768px) {
        .wizard-steps {
            flex-direction: column;
        }

        .wizard-content {
            padding: 1.5rem;
        }

        .wizard-buttons {
            flex-direction: column;
        }

        .btn-wizard {
            width: 100%;
        }

        .category-options {
            flex-direction: column;
            gap: 0.5rem;
        }
    }
</style>

<div class="container mt-4">
    <div class="add-item-container">
        <?php displayFlash(); ?>

        <div class="page-header">
            <h2><i class="fas fa-plus-circle me-2"></i>Add New Item</h2>
            <p>Complete the steps below to add a new item to your inventory</p>
        </div>

        <form method="post" enctype="multipart/form-data" id="addItemForm">
            <div class="form-wizard">
                <!-- Wizard Steps Header -->
                <div class="wizard-steps">
                    <div class="wizard-step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-title">Basic Info</div>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-title">Pricing & Stock</div>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-title">Images</div>
                    </div>
                </div>

                <!-- Wizard Content -->
                <div class="wizard-content">
                    <!-- Step 1: Basic Info -->
                    <div class="step-content active" data-step="1">
                        <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i>Basic Information</h4>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-tag me-2"></i>Item Name<span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                maxlength="100"
                                placeholder="Enter item name"
                                value="<?= htmlspecialchars($name) ?>">
                            <?php if (isset($errors['name'])): ?>
                                <span class="error-message"><i class="fas fa-exclamation-circle me-1"></i><?= $errors['name'] ?></span>
                            <?php endif; ?>
                            <div class="form-text">Minimum 3 characters, maximum 100 characters</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-folder me-2"></i>Category<span class="required">*</span>
                            </label>

                            <div class="category-options">
                                <div class="category-radio">
                                    <input type="radio" name="category_type" id="existingCategory" value="existing"
                                        <?= empty($categories) ? 'disabled' : 'checked' ?>>
                                    <label for="existingCategory">Select Category</label>
                                </div>
                                <div class="category-radio">
                                    <input type="radio" name="category_type" id="newCategory" value="new"
                                        <?= empty($categories) ? 'checked' : '' ?>>
                                    <label for="newCategory">Create New Category</label>
                                </div>
                            </div>

                            <?php if (!empty($categories)): ?>
                                <div class="category-input-group active" id="existingCategoryGroup">
                                    <select name="existing_category" class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>">
                                        <option value="">-- Select a category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>"
                                                <?= $category === $cat ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="category-input-group <?= empty($categories) ? 'active' : '' ?>" id="newCategoryGroup">
                                <input
                                    type="text"
                                    name="new_category"
                                    class="form-control <?= isset($errors['category']) ? 'is-invalid' : '' ?>"
                                    placeholder="Enter new category name"
                                    maxlength="50"
                                    value="<?= htmlspecialchars($category) ?>">
                            </div>

                            <?php if (isset($errors['category'])): ?>
                                <span class="error-message"><i class="fas fa-exclamation-circle me-1"></i><?= $errors['category'] ?></span>
                            <?php endif; ?>
                            <div class="form-text">Choose from existing categories or create your own</div>
                        </div>
                    </div>

                    <!-- Step 2: Pricing & Stock -->
                    <div class="step-content" data-step="2">
                        <h4 class="mb-4"><i class="fas fa-dollar-sign me-2"></i>Pricing & Stock</h4>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-rupee-sign me-2"></i>Price (LKR)<span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="price"
                                    class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                                    placeholder="0.00"
                                    min="0"
                                    value="<?= htmlspecialchars($price) ?>">
                            </div>
                            <?php if (isset($errors['price'])): ?>
                                <span class="error-message"><i class="fas fa-exclamation-circle me-1"></i><?= $errors['price'] ?></span>
                            <?php endif; ?>
                            <div class="form-text">Enter the item price in Sri Lankan Rupees</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-boxes me-2"></i>Stock Quantity<span class="required">*</span>
                            </label>
                            <input
                                type="number"
                                name="stock"
                                class="form-control <?= isset($errors['stock']) ? 'is-invalid' : '' ?>"
                                placeholder="0"
                                min="0"
                                step="1"
                                value="<?= htmlspecialchars($stock) ?>">
                            <?php if (isset($errors['stock'])): ?>
                                <span class="error-message"><i class="fas fa-exclamation-circle me-1"></i><?= $errors['stock'] ?></span>
                            <?php endif; ?>
                            <div class="form-text">Enter the number of items in stock</div>
                        </div>
                    </div>

                    <!-- Step 3: Images -->
                    <div class="step-content" data-step="3">
                        <h4 class="mb-4"><i class="fas fa-images me-2"></i>Product Images</h4>

                        <div class="image-upload-area" id="uploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <p class="upload-text">
                                <strong>Click to upload</strong> or drag and drop<br>
                                <small>All image formats supported (Max 5MB each, up to 5 images)</small>
                            </p>
                            <input
                                type="file"
                                name="images[]"
                                id="imageUpload"
                                class="file-input"
                                accept="image/*"
                                multiple>
                        </div>

                        <?php if (isset($errors['images'])): ?>
                            <span class="error-message"><i class="fas fa-exclamation-circle me-1"></i><?= $errors['images'] ?></span>
                        <?php endif; ?>
                        <?php if (isset($errors['database'])): ?>
                            <span class="error-message"><i class="fas fa-exclamation-circle me-1"></i><?= $errors['database'] ?></span>
                        <?php endif; ?>

                        <div class="image-preview-container" id="imagePreview"></div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="wizard-buttons">
                        <button type="button" class="btn btn-wizard btn-prev" id="prevBtn" style="display: none;">
                            <i class="fas fa-arrow-left me-2"></i>Previous
                        </button>
                        <div style="flex: 1;"></div>
                        <a href="item_list.php" class="btn btn-wizard btn-prev">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="button" class="btn btn-wizard btn-next" id="nextBtn">
                            Next<i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        <button type="submit" class="btn btn-wizard btn-submit" id="submitBtn" style="display: none;">
                            <i class="fas fa-save me-2"></i>Save Item
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let currentStep = 1;
    const totalSteps = 3;
    let selectedFiles = [];

    // Category toggle
    const existingRadio = document.getElementById('existingCategory');
    const newRadio = document.getElementById('newCategory');
    const existingGroup = document.getElementById('existingCategoryGroup');
    const newGroup = document.getElementById('newCategoryGroup');

    function toggleCategoryInput() {
        if (existingRadio && existingRadio.checked) {
            existingGroup?.classList.add('active');
            newGroup?.classList.remove('active');
        } else {
            existingGroup?.classList.remove('active');
            newGroup?.classList.add('active');
        }
    }

    if (existingRadio) existingRadio.addEventListener('change', toggleCategoryInput);
    if (newRadio) newRadio.addEventListener('change', toggleCategoryInput);

    // Multi-step navigation
    function showStep(step) {
        document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));

        document.querySelector(`.step-content[data-step="${step}"]`).classList.add('active');
        document.querySelector(`.wizard-step[data-step="${step}"]`).classList.add('active');

        // Mark previous steps as completed
        document.querySelectorAll('.wizard-step').forEach(el => {
            const stepNum = parseInt(el.dataset.step);
            if (stepNum < step) {
                el.classList.add('completed');
            } else {
                el.classList.remove('completed');
            }
        });

        // Button visibility
        document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'inline-block';
        document.getElementById('nextBtn').style.display = step === totalSteps ? 'none' : 'inline-block';
        document.getElementById('submitBtn').style.display = step === totalSteps ? 'inline-block' : 'none';

        currentStep = step;
    }

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (validateStep(currentStep)) {
            showStep(currentStep + 1);
        }
    });

    document.getElementById('prevBtn').addEventListener('click', () => {
        showStep(currentStep - 1);
    });

    function validateStep(step) {
        if (step === 1) {
            const name = document.querySelector('[name="name"]').value.trim();
            const categoryType = document.querySelector('[name="category_type"]:checked')?.value;
            let category = '';

            if (categoryType === 'existing') {
                category = document.querySelector('[name="existing_category"]')?.value || '';
            } else {
                category = document.querySelector('[name="new_category"]')?.value.trim() || '';
            }

            if (name.length < 3) {
                alert('Item name must be at least 3 characters long.');
                return false;
            }
            if (name.length > 100) {
                alert('Item name must not exceed 100 characters.');
                return false;
            }
            if (category === '') {
                alert('Please select or enter a category.');
                return false;
            }
        } else if (step === 2) {
            const price = document.querySelector('[name="price"]').value;
            const stock = document.querySelector('[name="stock"]').value;

            if (!price || parseFloat(price) < 0) {
                alert('Please enter a valid price.');
                return false;
            }
            if (!stock || parseInt(stock) < 0 || !Number.isInteger(parseFloat(stock))) {
                alert('Please enter a valid whole number for stock quantity.');
                return false;
            }
        } else if (step === 3) {
            if (selectedFiles.length === 0) {
                alert('Please upload at least one product image.');
                return false;
            }

            const maxSize = 5 * 1024 * 1024;
            // Accept all image types
            const allowedTypes = null; // Set to null to accept all image types

            for (let file of selectedFiles) {
                if (file.size > maxSize) {
                    alert(`Image "${file.name}" exceeds 5MB limit.`);
                    return false;
                }
                // Check if it's an image file
                if (!file.type.startsWith('image/')) {
                    alert(`"${file.name}" is not a valid image file.`);
                    return false;
                }
            }
        }
        return true;
    }

    // Validate before form submission
    document.getElementById('addItemForm').addEventListener('submit', function(e) {
        if (!validateStep(3)) {
            e.preventDefault();
            return false;
        }
    });

    // Multiple image upload with preview
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('imageUpload');
    const previewContainer = document.getElementById('imagePreview');

    uploadArea.addEventListener('click', () => imageInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const files = Array.from(e.dataTransfer.files);
        handleFiles(files);
    });

    imageInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        handleFiles(files);
    });

    function handleFiles(files) {
        if (selectedFiles.length + files.length > 5) {
            alert('Maximum 5 images allowed.');
            return;
        }

        files.forEach(file => {
            if (file.type.startsWith('image/')) {
                selectedFiles.push(file);
                displayPreview(file, selectedFiles.length - 1);
            }
        });

        updateFileInput();
    }

    function displayPreview(file, index) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'image-preview-item';
            div.dataset.index = index;
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-image" onclick="removeImage(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    }

    function removeImage(index) {
        selectedFiles.splice(index, 1);
        updateFileInput();
        renderPreviews();
    }

    function renderPreviews() {
        previewContainer.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            displayPreview(file, index);
        });
    }

    function updateFileInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        imageInput.files = dt.files;
    }

    window.removeImage = removeImage;

    // Handle PHP errors - stay on correct step
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($errors)): ?>
            <?php if (isset($errors['name']) || isset($errors['category'])): ?>
                showStep(1);
            <?php elseif (isset($errors['price']) || isset($errors['stock'])): ?>
                showStep(2);
            <?php elseif (isset($errors['images']) || isset($errors['database'])): ?>
                showStep(3);
            <?php endif; ?>

            setTimeout(() => {
                const firstError = document.querySelector('.error-message');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }, 100);
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>