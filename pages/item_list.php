<?php
require_once '../includes/functions.php';
requireLogin();
require_once '../config/db.php';

// Set timezone to Sri Lanka
date_default_timezone_set('Asia/Colombo');

// ===== EXPORT HANDLING - MUST BE BEFORE ANY HTML OUTPUT =====
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';

    // Build query
    $whereConditions = [];
    $params = [];

    if ($search !== '') {
        $whereConditions[] = "(name LIKE :search OR category LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if ($categoryFilter !== '') {
        $whereConditions[] = "category = :category";
        $params[':category'] = $categoryFilter;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get all items for export
    try {
        $exportStmt = $pdo->prepare("SELECT * FROM items $whereClause ORDER BY created_at DESC");
        $exportStmt->execute($params);
        $exportItems = $exportStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die("Error fetching data: " . $e->getMessage());
    }

    if ($format === 'csv') {
        // Clear any output buffers and error output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Suppress errors in CSV output
        ini_set('display_errors', 0);
        error_reporting(0);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="product_inventory_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Add CSV title row
        fputcsv($output, ['Product Inventory Report']);
        fputcsv($output, ['Generated on: ' . date('l, F j, Y - h:i A')]);
        fputcsv($output, ['Total Items: ' . count($exportItems)]);

        // Add filters info if applicable
        if ($search || $categoryFilter) {
            $filterInfo = 'Filters Applied: ';
            $filters = [];
            if ($search) $filters[] = 'Search="' . $search . '"';
            if ($categoryFilter) $filters[] = 'Category="' . $categoryFilter . '"';
            fputcsv($output, [$filterInfo . implode(', ', $filters)]);
        }

        // Calculate total revenue (price * stock)
        $totalRevenue = 0;
        foreach ($exportItems as $item) {
            $totalRevenue += (float)$item['price'] * (int)$item['stock'];
        }

        // Add empty row
        fputcsv($output, []);

        // Add CSV headers (removed Description column)
        fputcsv($output, ['ID', 'Name', 'Price (LKR)', 'Stock', 'Total Value (LKR)', 'Category', 'Created Date']);

        // Add data rows
        foreach ($exportItems as $item) {
            $itemValue = (float)$item['price'] * (int)$item['stock'];

            fputcsv($output, [
                $item['id'],
                $item['name'],
                // Format price without thousand separators
                number_format((float)$item['price'], 2, '.', ''),
                $item['stock'],
                number_format($itemValue, 2, '.', ''),
                isset($item['category']) && $item['category'] ? $item['category'] : 'Uncategorized',
                date('Y-m-d H:i:s', strtotime($item['created_at']))
            ]);
        }

        // Add summary section
        fputcsv($output, []);
        fputcsv($output, ['=== INVENTORY SUMMARY ===']);
        fputcsv($output, ['Total Items:', count($exportItems)]);
        fputcsv($output, ['TOTAL INVENTORY VALUE:', 'LKR ' . number_format($totalRevenue, 2)]);

        fclose($output);
        exit();
    }

    if ($format === 'pdf') {
        // Clear any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Product Inventory Report - <?= date('Y-m-d') ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Arial', sans-serif;
                    padding: 30px;
                    background: white;
                    color: #333;
                }

                .report-header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 3px solid #667eea;
                }

                .report-header h1 {
                    color: #667eea;
                    font-size: 32px;
                    margin-bottom: 10px;
                }

                .report-header .subtitle {
                    color: #666;
                    font-size: 16px;
                }

                .report-info {
                    background: #f8f9fa;
                    padding: 15px 20px;
                    margin-bottom: 25px;
                    border-radius: 8px;
                    border-left: 4px solid #667eea;
                }

                .report-info strong {
                    color: #667eea;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 30px;
                    background: white;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }

                thead {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }

                th {
                    padding: 15px 10px;
                    text-align: left;
                    font-weight: 600;
                    text-transform: uppercase;
                    font-size: 12px;
                    letter-spacing: 0.5px;
                }

                td {
                    padding: 12px 10px;
                    border-bottom: 1px solid #e0e0e0;
                    font-size: 14px;
                }

                tbody tr:nth-child(even) {
                    background-color: #f8f9fa;
                }

                tbody tr:hover {
                    background-color: #e9ecef;
                }

                .report-footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 2px solid #e0e0e0;
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                }

                .action-buttons {
                    text-align: center;
                    margin: 30px 0;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                }

                .btn {
                    padding: 12px 30px;
                    margin: 0 10px;
                    border: none;
                    border-radius: 6px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .btn-primary {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }

                .btn-secondary {
                    background: #6c757d;
                    color: white;
                }

                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }

                .total-row {
                    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                    color: white;
                    font-weight: bold;
                    border-top: 4px solid #11998e;
                }

                .total-row td {
                    padding: 18px 10px;
                    font-size: 18px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                @media print {
                    .action-buttons {
                        display: none;
                    }

                    body {
                        padding: 0;
                    }

                    table {
                        box-shadow: none;
                    }
                }

                @page {
                    margin: 20mm;
                }
            </style>
        </head>

        <body>
            <div class="report-header">
                <h1>📦 Product Inventory Report</h1>
                <div class="subtitle">Generated on <?= date('l, F j, Y - h:i A') ?></div>
            </div>

            <?php
            // Calculate total inventory value
            $totalInventoryValue = 0;
            foreach ($exportItems as $item) {
                $totalInventoryValue += (float)$item['price'] * (int)$item['stock'];
            }
            ?>

            <div class="report-info">
                <strong>Report Summary:</strong>
                Total Items: <?= count($exportItems) ?>
                <?php if ($search): ?>
                    | Search Filter: "<?= htmlspecialchars($search) ?>"
                <?php endif; ?>
                <?php if ($categoryFilter): ?>
                    | Category Filter: <?= htmlspecialchars($categoryFilter) ?>
                <?php endif; ?>
            </div>

            <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 25px; text-align: center; box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px;">Total Inventory Value</div>
                <div style="font-size: 32px; font-weight: 700;">LKR <?= number_format($totalInventoryValue, 2) ?></div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 25%;">Item Name</th>
                        <th style="width: 12%;">Price (LKR)</th>
                        <th style="width: 8%;">Stock</th>
                        <th style="width: 15%;">Total Value (LKR)</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 20%;">Created Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exportItems)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #999;">
                                No items found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exportItems as $item): ?>
                            <?php $itemValue = (float)$item['price'] * (int)$item['stock']; ?>
                            <tr>
                                <td><?= htmlspecialchars($item['id']) ?></td>
                                <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                <td>LKR <?= number_format((float)$item['price'], 2) ?></td>
                                <td><?= htmlspecialchars($item['stock']) ?></td>
                                <td>LKR <?= number_format($itemValue, 2) ?></td>
                                <td><?= htmlspecialchars($item['category'] ? $item['category'] : 'Uncategorized') ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($item['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="4" style="text-align: right;">TOTAL INVENTORY VALUE:</td>
                            <td colspan="3">LKR <?= number_format($totalInventoryValue, 2) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="report-footer">
                <p><strong>Inventory Management System</strong></p>
                <p>This is an automatically generated report</p>
            </div>

            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">
                    🖨️ Print / Save as PDF
                </button>
                <button onclick="window.close()" class="btn btn-secondary">
                    ✖️ Close Window
                </button>
            </div>

            <script>
                // Optional: Auto-trigger print dialog
                // window.onload = function() { window.print(); }
            </script>
        </body>

        </html>
<?php
        exit();
    }
}

// ===== NORMAL PAGE DISPLAY =====
include '../includes/header.php';

// Pagination settings
$itemsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Sorting functionality
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'asc' : 'desc';

// Validate sort column
$allowedSortColumns = ['name', 'price', 'stock', 'category', 'created_at'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_at';
}

// Build query
$whereConditions = [];
$params = [];

if ($search !== '') {
    $whereConditions[] = "(name LIKE :search OR category LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($categoryFilter !== '') {
    $whereConditions[] = "category = :category";
    $params[':category'] = $categoryFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Get items with sorting
$orderClause = "ORDER BY $sortBy $sortOrder";
$stmt = $pdo->prepare("SELECT * FROM items $whereClause $orderClause LIMIT $itemsPerPage OFFSET $offset");
$stmt->execute($params);
$items = $stmt->fetchAll();

// Get all categories from the categories table
$categoriesStmt = $pdo->query("SELECT name FROM categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

// Build export URL params
$exportParams = 'action=export';
if ($search) $exportParams .= '&search=' . urlencode($search);
if ($categoryFilter) $exportParams .= '&category=' . urlencode($categoryFilter);
?>

<!-- Link to external CSS file -->
<link rel="stylesheet" href="../assets/css/item_list.css">

<div class="container mt-4">
    <div class="item-list-container">
        <?php displayFlash(); ?>

        <div class="page-header">
            <div>
                <h2><i class="fas fa-list me-2"></i>Item Inventory</h2>
            </div>
            <div class="header-actions">
                <div class="export-dropdown">
                    <button class="btn btn-export" onclick="toggleExportMenu(event)">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <div class="export-menu" id="exportMenu">
                        <a href="?<?= $exportParams ?>&format=csv" class="export-option">
                            <i class="fas fa-file-csv"></i> Export as CSV
                        </a>
                        <a href="?<?= $exportParams ?>&format=pdf" class="export-option" target="_blank">
                            <i class="fas fa-file-pdf"></i> Export as PDF
                        </a>
                    </div>
                </div>
                <a href="item_add.php" class="btn btn-add-item">
                    <i class="fas fa-plus-circle me-2"></i>Add New Item
                </a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-icon primary">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-content">
                    <h6>Total Items</h6>
                    <p><?= $totalItems ?></p>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon success">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-content">
                    <h6>Categories</h6>
                    <p><?= count($categories) ?></p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search by name or category..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"
                                <?= $categoryFilter === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <?php if (empty($items)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h4>No Items Found</h4>
                <p>
                    <?php if ($search || $categoryFilter): ?>
                        No items match your search criteria. Try adjusting your filters.
                    <?php else: ?>
                        You haven't added any items yet. Start building your inventory!
                    <?php endif; ?>
                </p>
                <?php if ($search || $categoryFilter): ?>
                    <a href="item_list.php" class="btn btn-primary">Clear Filters</a>
                <?php else: ?>
                    <a href="item_add.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Add Your First Item
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Items Table -->
            <div class="items-table-card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th class="sortable <?= $sortBy === 'name' ? 'sorted-' . $sortOrder : '' ?>"
                                    onclick="sortTable('name')">Name</th>
                                <th class="sortable <?= $sortBy === 'price' ? 'sorted-' . $sortOrder : '' ?>"
                                    onclick="sortTable('price')">Price</th>
                                <th class="sortable <?= $sortBy === 'stock' ? 'sorted-' . $sortOrder : '' ?>"
                                    onclick="sortTable('stock')">Stock</th>
                                <th class="sortable <?= $sortBy === 'category' ? 'sorted-' . $sortOrder : '' ?>"
                                    onclick="sortTable('category')">Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if ($item['image']): ?>
                                            <?php
                                            $images = explode(',', $item['image']);
                                            $firstImage = trim($images[0]);
                                            ?>
                                            <img src="../assets/uploads/<?= htmlspecialchars($firstImage) ?>"
                                                class="item-image" alt="<?= htmlspecialchars($item['name']) ?>">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="item-name"><?= htmlspecialchars($item['name']) ?></td>
                                    <td>
                                        <?php if ($item['price']): ?>
                                            <span class="price-badge">LKR <?= number_format($item['price'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $stock = $item['stock'];
                                        $stockClass = 'stock-high';
                                        if ($stock < 10) $stockClass = 'stock-low';
                                        elseif ($stock < 30) $stockClass = 'stock-medium';
                                        ?>
                                        <span class="stock-badge <?= $stockClass ?>">
                                            <?= htmlspecialchars($stock ?? 0) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item['category']): ?>
                                            <span class="category-badge">
                                                <?= htmlspecialchars($item['category']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Uncategorized</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="item_view.php?id=<?= $item['id'] ?>" class="btn action-btn btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="item_edit.php?id=<?= $item['id'] ?>" class="btn action-btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="item_delete.php?id=<?= $item['id'] ?>"
                                            class="btn action-btn btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this item?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Card View for Mobile -->
            <div class="items-cards-view">
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div class="item-card-header">
                            <?php if ($item['image']): ?>
                                <?php
                                $images = explode(',', $item['image']);
                                $firstImage = trim($images[0]);
                                ?>
                                <img src="../assets/uploads/<?= htmlspecialchars($firstImage) ?>"
                                    class="item-card-image" alt="<?= htmlspecialchars($item['name']) ?>">
                            <?php else: ?>
                                <div class="item-card-no-image">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                            <div class="item-card-info">
                                <div class="item-card-name"><?= htmlspecialchars($item['name']) ?></div>
                                <?php if ($item['category']): ?>
                                    <span class="category-badge">
                                        <?= htmlspecialchars($item['category']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="item-card-details">
                            <div class="item-card-detail">
                                <strong>Price</strong>
                                <?php if ($item['price']): ?>
                                    <span class="price-badge">LKR <?= number_format($item['price'], 2) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </div>
                            <div class="item-card-detail">
                                <strong>Stock</strong>
                                <?php
                                $stock = $item['stock'];
                                $stockClass = 'stock-high';
                                if ($stock < 10) $stockClass = 'stock-low';
                                elseif ($stock < 30) $stockClass = 'stock-medium';
                                ?>
                                <span class="stock-badge <?= $stockClass ?>">
                                    <?= htmlspecialchars($stock ?? 0) ?>
                                </span>
                            </div>
                        </div>
                        <div class="item-card-actions">
                            <a href="item_view.php?id=<?= $item['id'] ?>" class="btn action-btn btn-view flex-fill">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="item_edit.php?id=<?= $item['id'] ?>" class="btn action-btn btn-edit flex-fill">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="item_delete.php?id=<?= $item['id'] ?>"
                                class="btn action-btn btn-delete flex-fill"
                                onclick="return confirm('Are you sure you want to delete this item?');">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <nav>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // Export dropdown toggle
    function toggleExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenu');
        menu.classList.toggle('show');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.querySelector('.export-dropdown');
        const menu = document.getElementById('exportMenu');
        if (dropdown && !dropdown.contains(event.target)) {
            menu.classList.remove('show');
        }
    });

    // Table sorting
    function sortTable(column) {
        const currentSort = '<?= $sortBy ?>';
        const currentOrder = '<?= $sortOrder ?>';
        let newOrder = 'desc';
        if (currentSort === column && currentOrder === 'desc') {
            newOrder = 'asc';
        }
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', column);
        urlParams.set('order', newOrder);
        window.location.href = '?' + urlParams.toString();
    }
</script>

<?php include '../includes/footer.php'; ?>