<?php
// pages/dashboard.php
require_once '../includes/functions.php';
requireLogin();
require_once '../config/db.php';

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1) Total items
$stmt = $pdo->query("SELECT COUNT(*) AS total_items FROM items");
$totalItemsRow = $stmt->fetch();
$totalItems = $totalItemsRow['total_items'] ?? 0;

// 2) Total categories
$stmt = $pdo->query("SELECT COUNT(DISTINCT category) AS total_categories FROM items WHERE category IS NOT NULL AND category != ''");
$totalCategoriesRow = $stmt->fetch();
$totalCategories = $totalCategoriesRow['total_categories'] ?? 0;

// 3) Low stock items (stock < 10)
$stmt = $pdo->query("SELECT COUNT(*) AS low_stock FROM items WHERE stock < 10");
$lowStockRow = $stmt->fetch();
$lowStock = $lowStockRow['low_stock'] ?? 0;

// 4) Total stock value
$stmt = $pdo->query("SELECT SUM(price * stock) AS total_value FROM items WHERE price IS NOT NULL");
$totalValueRow = $stmt->fetch();
$totalValue = $totalValueRow['total_value'] ?? 0;

// 5) Recent items (last 5)
$stmt = $pdo->query("SELECT * FROM items ORDER BY created_at DESC LIMIT 5");
$recentItems = $stmt->fetchAll();

// 6) Items count per category for chart
$stmt = $pdo->query("SELECT category, COUNT(*) AS cnt 
                     FROM items 
                     GROUP BY category
                     ORDER BY cnt DESC");
$categoryRows = $stmt->fetchAll();

// Prepare data for chart
$labels = [];
$counts = [];
foreach ($categoryRows as $row) {
    $labels[] = $row['category'] ?: 'Uncategorized';
    $counts[] = $row['cnt'];
}

include '../includes/header.php';
?>

<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .welcome-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .welcome-section h2 {
        margin: 0;
        font-weight: 700;
        font-size: 2rem;
    }

    .welcome-section p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: none;
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .stat-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
    }

    .stat-icon.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-icon.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .stat-icon.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-icon.info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-content h6 {
        margin: 0;
        color: #6c757d;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-content .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
        margin: 0.5rem 0 0 0;
    }

    .stat-badge {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    .badge-success {
        background-color: #d4edda;
        color: #155724;
    }

    .badge-warning {
        background-color: #fff3cd;
        color: #856404;
    }

    .chart-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .chart-card h4 {
        color: #333;
        font-weight: 700;
        margin-bottom: 1.5rem;
    }

    .recent-items-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .recent-items-card h4 {
        color: #333;
        font-weight: 700;
        margin-bottom: 1.5rem;
    }

    .recent-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s ease;
    }

    .recent-item:last-child {
        border-bottom: none;
    }

    .recent-item:hover {
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    .recent-item-image {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        margin-right: 1rem;
    }

    .recent-item-no-image {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        background: linear-gradient(135deg, #e0e0e0 0%, #f0f0f0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        color: #999;
    }

    .recent-item-info {
        flex: 1;
    }

    .recent-item-name {
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .recent-item-category {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .recent-item-price {
        font-weight: 600;
        color: #11998e;
        margin-left: auto;
    }

    @media (max-width: 768px) {
        .welcome-section h2 {
            font-size: 1.5rem;
        }

        .stat-number {
            font-size: 1.5rem !important;
        }
    }
</style>

<div class="container mt-4">
    <div class="dashboard-container">
        <?php displayFlash(); ?>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2><i class="fas fa-chart-line me-2"></i>Dashboard</h2>
            <p>Welcome back, <strong><?= htmlspecialchars($username) ?></strong>! Here's your inventory overview.</p>
        </div>

        <!-- Stat Cards -->
        <div class="row mb-4">
            <!-- Total Items -->
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-content">
                            <h6>Total Items</h6>
                            <p class="stat-number"><?= number_format($totalItems) ?></p>
                            <?php if ($totalItems > 0): ?>
                                <span class="stat-badge badge-success">Active</span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-icon primary">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Categories -->
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-content">
                            <h6>Categories</h6>
                            <p class="stat-number"><?= number_format($totalCategories) ?></p>
                            <span class="stat-badge badge-success">
                                <?= $totalCategories ?> types
                            </span>
                        </div>
                        <div class="stat-icon success">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-content">
                            <h6>Low Stock</h6>
                            <p class="stat-number"><?= number_format($lowStock) ?></p>
                            <?php if ($lowStock > 0): ?>
                                <span class="stat-badge badge-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Alert
                                </span>
                            <?php else: ?>
                                <span class="stat-badge badge-success">All Good</span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-icon warning">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Value -->
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-content">
                            <h6>Inventory Value</h6>
                            <p class="stat-number">LKR <?= number_format($totalValue, 2) ?></p>
                            <span class="stat-badge badge-success">Stock Worth</span>
                        </div>
                        <div class="stat-icon info">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Chart Section -->
            <?php if (!empty($categoryRows)): ?>
                <div class="col-lg-7 mb-4">
                    <div class="chart-card">
                        <h4><i class="fas fa-chart-bar me-2"></i>Items by Category</h4>
                        <canvas id="categoryChart" height="280"></canvas>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Items -->
            <div class="col-lg-5 mb-4">
                <div class="recent-items-card">
                    <h4><i class="fas fa-clock me-2"></i>Recent Items</h4>
                    <?php if (!empty($recentItems)): ?>
                        <?php foreach ($recentItems as $item): ?>
                            <div class="recent-item">
                                <?php
                                // Handle multiple images - get the first one
                                if ($item['image']) {
                                    $images = explode(',', $item['image']);
                                    $firstImage = trim($images[0]);
                                ?>
                                    <img src="../assets/uploads/<?= htmlspecialchars($firstImage) ?>"
                                        class="recent-item-image"
                                        alt="<?= htmlspecialchars($item['name']) ?>">
                                <?php } else { ?>
                                    <div class="recent-item-no-image">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php } ?>
                                <div class="recent-item-info">
                                    <p class="recent-item-name"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="recent-item-category">
                                        <?= $item['category'] ? htmlspecialchars($item['category']) : 'Uncategorized' ?>
                                    </p>
                                </div>
                                <div class="recent-item-price">
                                    <?php if ($item['price']): ?>
                                        LKR <?= number_format($item['price'], 2) ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="item_list.php" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i>View All Items
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No items yet. Start adding items to your inventory!</p>
                        <div class="text-center">
                            <a href="item_add.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Add First Item
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = <?= json_encode($labels) ?>;
    const counts = <?= json_encode($counts) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('categoryChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Items',
                    data: counts,
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(17, 153, 142, 0.8)',
                        'rgba(240, 147, 251, 0.8)',
                        'rgba(79, 172, 254, 0.8)',
                        'rgba(245, 87, 108, 0.8)',
                        'rgba(56, 239, 125, 0.8)'
                    ],
                    borderColor: [
                        'rgba(102, 126, 234, 1)',
                        'rgba(17, 153, 142, 1)',
                        'rgba(240, 147, 251, 1)',
                        'rgba(79, 172, 254, 1)',
                        'rgba(245, 87, 108, 1)',
                        'rgba(56, 239, 125, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>