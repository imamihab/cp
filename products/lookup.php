<?php
require_once __DIR__ . '/../config/app.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesi login telah berakhir.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function product_stock_status(int $stock, int $minimum): string
{
    if ($stock <= 0) return 'Habis';
    if ($stock <= $minimum) return 'Menipis';
    return 'Normal';
}

$product_id = (int) ($_GET['id'] ?? 0);
if ($product_id > 0) {
    $stmt = $pdo->prepare('SELECT p.id, p.code, p.name, p.unit, p.stock, p.minimum_stock, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? LIMIT 1');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Barang tidak ditemukan.']);
        exit;
    }

    $history = $pdo->prepare('SELECT sm.type, sm.quantity, sm.description, sm.created_at, u.name AS user_name FROM stock_movements sm LEFT JOIN users u ON u.id = sm.user_id WHERE sm.product_id = ? ORDER BY sm.created_at DESC, sm.id DESC LIMIT 6');
    $history->execute([$product_id]);
    $product['status'] = product_stock_status((int) $product['stock'], (int) $product['minimum_stock']);
    $product['movements'] = $history->fetchAll();
    echo json_encode($product, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$keyword = trim($_GET['q'] ?? '');
if (strlen($keyword) < 1) {
    echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$like = '%' . $keyword . '%';
$stmt = $pdo->prepare('SELECT p.id, p.code, p.name, p.stock, p.minimum_stock, p.unit, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.code LIKE ? OR p.name LIKE ? ORDER BY CASE WHEN p.code = ? THEN 0 WHEN p.code LIKE ? THEN 1 ELSE 2 END, p.name ASC LIMIT 8');
$stmt->execute([$like, $like, $keyword, $like]);
$items = $stmt->fetchAll();
foreach ($items as &$item) {
    $item['status'] = product_stock_status((int) $item['stock'], (int) $item['minimum_stock']);
}
unset($item);
echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
