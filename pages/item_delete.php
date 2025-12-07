<?php
// pages/item_delete.php
require_once '../includes/functions.php';
requireLogin();
require_once '../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    flash('Item ID missing.', 'danger');
    header('Location: item_list.php');
    exit;
}

// Fetch item so we can delete image file if needed
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();
if (!$item) {
    flash('Item not found.', 'danger');
    header('Location: item_list.php');
    exit;
}

// Delete DB record
$stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
$stmt->execute([':id' => $id]);

// Delete image file if exists
if ($item['image']) {
    $path = __DIR__ . '/../assets/uploads/' . $item['image'];
    if (file_exists($path)) {
        unlink($path);
    }
}

flash('Item deleted successfully.', 'success');
header('Location: item_list.php');
exit;
