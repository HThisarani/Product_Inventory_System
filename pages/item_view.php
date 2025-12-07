<?php
// pages/item_view.php
require_once '../includes/functions.php';
requireLogin();
require_once '../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    flash('Item ID missing.', 'danger');
    header('Location: item_list.php');
    exit;
}

$stmt = $pdo->prepare("SELECT items.*, users.username AS creator 
    FROM items LEFT JOIN users ON items.created_by = users.id
    WHERE items.id = :id");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();
if (!$item) {
    flash('Item not found.', 'danger');
    header('Location: item_list.php');
    exit;
}

// Get all images if stored as comma-separated
$images = [];
if ($item['image']) {
    $images = explode(',', $item['image']);
}

include '../includes/header.php';
?>

<style>
    .item-view-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .page-header {
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-header h2 {
        color: #333;
        font-weight: 700;
        margin: 0;
    }

    .btn-back {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
        color: white;
    }

    .item-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .item-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
    }

    .item-header h3 {
        margin: 0;
        font-weight: 700;
        font-size: 2rem;
    }

    .item-header .item-id {
        opacity: 0.9;
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    .item-content {
        display: flex;
        flex-wrap: wrap;
    }

    .item-images {
        flex: 1;
        min-width: 300px;
        padding: 2rem;
        background-color: #f8f9fa;
    }

    .carousel {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .carousel-inner img {
        width: 100%;
        height: 400px;
        object-fit: cover;
    }

    .carousel-control-prev,
    .carousel-control-next {
        width: 50px;
        height: 50px;
        background-color: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
    }

    .carousel-control-prev {
        left: 10px;
    }

    .carousel-control-next {
        right: 10px;
    }

    .no-image-placeholder {
        width: 100%;
        height: 400px;
        background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 5rem;
    }

    .item-details {
        flex: 1;
        min-width: 300px;
        padding: 2rem;
    }

    .detail-group {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-group:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detail-value {
        font-size: 1.2rem;
        color: #333;
        font-weight: 600;
    }

    .price-badge {
        display: inline-block;
        color: #11998e;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .stock-badge {
        display: inline-block;
        font-size: 1.2rem;
        font-weight: 700;
    }

    .stock-high {
        color: #155724;
    }

    .stock-medium {
        color: #856404;
    }

    .stock-low {
        color: #721c24;
    }

    .category-badge {
        display: inline-block;
        color: #0066cc;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .creator-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .creator-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.2rem;
    }

    .action-buttons {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px solid #f0f0f0;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .btn-edit {
        background-color: #ffc107;
        color: #000;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-edit:hover {
        background-color: #e0a800;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        color: #000;
    }

    .btn-delete {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-delete:hover {
        background-color: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        color: white;
    }

    @media (max-width: 768px) {
        .item-content {
            flex-direction: column;
        }

        .item-header h3 {
            font-size: 1.5rem;
        }

        .carousel-inner img,
        .no-image-placeholder {
            height: 300px;
        }

        .action-buttons {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
        }
    }
</style>

<div class="container mt-4">
    <div class="item-view-container">
        <?php displayFlash(); ?>

        <div class="page-header">
            <h2><i class="fas fa-info-circle me-2"></i>Item Details</h2>
            <a href="item_list.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>

        <div class="item-card">
            <div class="item-header">
                <h3><?= htmlspecialchars($item['name']) ?></h3>
                <div class="item-id">
                    <i class="fas fa-hashtag"></i> ID: <?= htmlspecialchars($item['id']) ?>
                </div>
            </div>

            <div class="item-content">
                <!-- Images Section -->
                <div class="item-images">
                    <?php if (!empty($images) && $images[0] !== ''): ?>
                        <?php if (count($images) > 1): ?>
                            <!-- Bootstrap Carousel for multiple images -->
                            <div id="itemCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <?php foreach ($images as $index => $img): ?>
                                        <button type="button"
                                            data-bs-target="#itemCarousel"
                                            data-bs-slide-to="<?= $index ?>"
                                            class="<?= $index === 0 ? 'active' : '' ?>">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="carousel-inner">
                                    <?php foreach ($images as $index => $img): ?>
                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                            <img src="../assets/uploads/<?= htmlspecialchars(trim($img)) ?>"
                                                alt="<?= htmlspecialchars($item['name']) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#itemCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#itemCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- Single image -->
                            <img src="../assets/uploads/<?= htmlspecialchars(trim($images[0])) ?>"
                                alt="<?= htmlspecialchars($item['name']) ?>"
                                style="width: 100%; height: 400px; object-fit: cover; border-radius: 12px;">
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Details Section -->
                <div class="item-details">
                    <div class="detail-group">
                        <div class="detail-label">
                            <i class="fas fa-dollar-sign"></i> Price
                        </div>
                        <div class="detail-value">
                            <?php if ($item['price']): ?>
                                <span class="price-badge">Rs. <?= number_format($item['price'], 2) ?></span>
                            <?php else: ?>
                                <span class="text-muted">Not specified</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">
                            <i class="fas fa-boxes"></i> Stock Quantity
                        </div>
                        <div class="detail-value">
                            <?php
                            $stock = $item['stock'] ?? 0;
                            $stockClass = 'stock-high';
                            if ($stock < 10) $stockClass = 'stock-low';
                            elseif ($stock < 30) $stockClass = 'stock-medium';
                            ?>
                            <span class="stock-badge <?= $stockClass ?>">
                                <?= number_format($stock) ?> units
                                <?php if ($stock < 10): ?>
                                    <i class="fas fa-exclamation-triangle ms-2"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">
                            <i class="fas fa-tag"></i> Category
                        </div>
                        <div class="detail-value">
                            <?php if ($item['category']): ?>
                                <span class="category-badge">
                                    <?= htmlspecialchars($item['category']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Uncategorized</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">
                            <i class="fas fa-user"></i> Added By
                        </div>
                        <div class="detail-value">
                            <div class="creator-info">
                                <div class="creator-avatar">
                                    <?= strtoupper(substr($item['creator'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span><?= htmlspecialchars($item['creator'] ?? 'Unknown') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">
                            <i class="fas fa-calendar-alt"></i> Created At
                        </div>
                        <div class="detail-value">
                            <?= date('F j, Y \a\t g:i A', strtotime($item['created_at'])) ?>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="item_edit.php?id=<?= $item['id'] ?>" class="btn btn-edit">
                            <i class="fas fa-edit me-2"></i>Edit Item
                        </a>
                        <a href="item_delete.php?id=<?= $item['id'] ?>"
                            class="btn btn-delete"
                            onclick="return confirm('Are you sure you want to delete this item?');">
                            <i class="fas fa-trash me-2"></i>Delete Item
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>