<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
if (!isLoggedIn()) jsonResponse(['success'=>false,'error'=>'Unauthorized'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'error'=>'Method not allowed'], 405);
if (!verifyCsrf($_POST['csrf'] ?? '')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF token'], 403);

$userId = getUserId();
$db     = getDB();

if (!empty($_POST['all'])) {
    // Cancel all open orders
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id=? AND status IN ('open','partial','active')");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
    $count  = 0;

    $db->beginTransaction();
    try {
        foreach ($orders as $o) {
            cancelAndUnlock($db, $o, $userId);
            $count++;
        }
        $db->commit();
        jsonResponse(['success'=>true,'count'=>$count]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success'=>false,'error'=>$e->getMessage()]);
    }
} else {
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId <= 0) jsonResponse(['success'=>false,'error'=>'Invalid order ID']);

    $stmt = $db->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) jsonResponse(['success'=>false,'error'=>'Order not found'], 404);
    if (!in_array($order['status'], ['open','partial','active'])) {
        jsonResponse(['success'=>false,'error'=>'Order cannot be cancelled (status: '.$order['status'].')']);
    }

    $db->beginTransaction();
    try {
        cancelAndUnlock($db, $order, $userId);
        $db->commit();
        jsonResponse(['success'=>true]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success'=>false,'error'=>$e->getMessage()]);
    }
}

function cancelAndUnlock(PDO $db, array $order, int $userId): void {
    $db->prepare("UPDATE orders SET status='cancelled', updated_at=NOW() WHERE id=?")
       ->execute([$order['id']]);

    $unfilled = (float)$order['amount'] - (float)$order['filled'];

    if ($order['side'] === 'buy') {
        // Unlock USDT
        $lockedUsdt = $unfilled * (float)$order['price'];
        unlockBalance($db, $userId, 'USDT', $lockedUsdt);
    } else {
        // Unlock BTC
        unlockBalance($db, $userId, 'BTC', $unfilled);
    }
}
