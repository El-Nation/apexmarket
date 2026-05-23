<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { jsonResponse(['success'=>false,'error'=>'Unauthorized'], 401); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false,'error'=>'Method not allowed'], 405); }

// Validate CSRF
if (!verifyCsrf($_POST['csrf'] ?? '')) { jsonResponse(['success'=>false,'error'=>'Invalid CSRF token'], 403); }

$userId    = getUserId();
$side      = strtolower(trim($_POST['side']       ?? ''));
$orderType = strtolower(trim($_POST['order_type'] ?? 'limit'));
$symbol    = trim($_POST['symbol'] ?? 'BTC/USDT');
$price     = (float)($_POST['price']      ?? 0);
$stopPrice = (float)($_POST['stop_price'] ?? 0);
$amount    = (float)($_POST['amount']     ?? 0);

// Validate
if (!in_array($side, ['buy','sell']))                          jsonResponse(['success'=>false,'error'=>'Invalid side']);
if (!in_array($orderType, ['limit','market','stop']))          jsonResponse(['success'=>false,'error'=>'Invalid order type']);
if ($amount <= 0)                                              jsonResponse(['success'=>false,'error'=>'Amount must be positive']);
if ($orderType === 'limit'  && $price <= 0)                   jsonResponse(['success'=>false,'error'=>'Price must be positive for limit orders']);
if ($orderType === 'stop'   && $stopPrice <= 0)               jsonResponse(['success'=>false,'error'=>'Stop price required']);
if ($amount > 10)                                             jsonResponse(['success'=>false,'error'=>'Max single order: 10 BTC']);

$db = getDB();
$db->beginTransaction();

try {
    // For market orders, use current mid price (approximated from order book)
    if ($orderType === 'market') {
        $row = $db->prepare("SELECT AVG(price) as p FROM orders WHERE symbol=? AND side=? AND status='open' LIMIT 5");
        $row->execute([$symbol, $side === 'buy' ? 'sell' : 'buy']);
        $r = $row->fetch();
        $price = $r['p'] ? (float)$r['p'] : $price;
        if ($price <= 0) $price = 64000; // fallback
    }

    // Determine which asset to lock
    if ($side === 'buy') {
        // Buying BTC, need USDT: price * amount + fee buffer
        $requiredUsdt = $price * $amount * 1.002;
        $available    = getBalance($userId, 'USDT');
        if ($available < $requiredUsdt) {
            jsonResponse(['success'=>false,'error'=>sprintf('Insufficient USDT balance. Need %.2f, have %.2f', $requiredUsdt, $available)]);
        }
        $locked = lockBalance($db, $userId, 'USDT', $price * $amount);
        if (!$locked) jsonResponse(['success'=>false,'error'=>'Failed to lock USDT balance']);
    } else {
        // Selling BTC
        $available = getBalance($userId, 'BTC');
        if ($available < $amount) {
            jsonResponse(['success'=>false,'error'=>sprintf('Insufficient BTC balance. Need %.6f, have %.6f', $amount, $available)]);
        }
        $locked = lockBalance($db, $userId, 'BTC', $amount);
        if (!$locked) jsonResponse(['success'=>false,'error'=>'Failed to lock BTC balance']);
    }

    // Insert order
    $status = ($orderType === 'stop') ? 'active' : 'open';
    $stmt = $db->prepare("
        INSERT INTO orders (user_id, symbol, side, type, price, stop_price, amount, status)
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([$userId, $symbol, $side, $orderType, $price, $stopPrice, $amount, $status]);
    $orderId = (int)$db->lastInsertId();

    $db->commit();

    // Try to match if limit/market order
    if ($orderType !== 'stop') {
        matchOrders($orderId, $db);
    }

    jsonResponse([
        'success'  => true,
        'order_id' => $orderId,
        'message'  => 'Order placed successfully',
    ]);

} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['success'=>false,'error'=>'Order failed: ' . $e->getMessage()], 500);
}

// ─── Order Matching Engine ────────────────────────────────────────────────────
function matchOrders(int $newOrderId, PDO $db): void {
    $newOrder = $db->query("SELECT * FROM orders WHERE id = $newOrderId")->fetch();
    if (!$newOrder || $newOrder['status'] !== 'open') return;

    $db->beginTransaction();
    try {
        $opposite = $newOrder['side'] === 'buy' ? 'sell' : 'buy';

        if ($newOrder['side'] === 'buy') {
            // Find cheapest sell orders with price <= our buy price
            $stmt = $db->prepare("
                SELECT * FROM orders
                WHERE symbol=? AND side='sell' AND status IN ('open','partial')
                  AND price <= ? AND user_id != ?
                ORDER BY price ASC, created_at ASC
                LIMIT 10
            ");
            $stmt->execute([$newOrder['symbol'], $newOrder['price'], $newOrder['user_id']]);
        } else {
            // Find most expensive buy orders with price >= our sell price
            $stmt = $db->prepare("
                SELECT * FROM orders
                WHERE symbol=? AND side='buy' AND status IN ('open','partial')
                  AND price >= ? AND user_id != ?
                ORDER BY price DESC, created_at ASC
                LIMIT 10
            ");
            $stmt->execute([$newOrder['symbol'], $newOrder['price'], $newOrder['user_id']]);
        }

        $counterOrders = $stmt->fetchAll();
        $remaining     = (float)$newOrder['amount'] - (float)$newOrder['filled'];

        foreach ($counterOrders as $counter) {
            if ($remaining <= 0.000001) break;

            $counterRemaining = (float)$counter['amount'] - (float)$counter['filled'];
            $matchAmount      = min($remaining, $counterRemaining);
            $matchPrice       = (float)$counter['price'];

            if ($matchAmount < 0.000001) continue;

            $buyerId  = $newOrder['side'] === 'buy' ? $newOrder['user_id'] : $counter['user_id'];
            $sellerId = $newOrder['side'] === 'sell' ? $newOrder['user_id'] : $counter['user_id'];
            $buyOrderId  = $newOrder['side'] === 'buy' ? $newOrder['id'] : $counter['id'];
            $sellOrderId = $newOrder['side'] === 'sell' ? $newOrder['id'] : $counter['id'];

            $tradeValue  = $matchAmount * $matchPrice;
            $buyerFee    = $tradeValue  * FEE_RATE;
            $sellerFee   = $tradeValue  * FEE_RATE;

            // Record trade
            $db->prepare("
                INSERT INTO trades (buy_order_id, sell_order_id, buyer_id, seller_id, symbol, price, amount, buyer_fee, seller_fee)
                VALUES (?,?,?,?,?,?,?,?,?)
            ")->execute([$buyOrderId, $sellOrderId, $buyerId, $sellerId, $newOrder['symbol'], $matchPrice, $matchAmount, $buyerFee, $sellerFee]);

            // Update buyer: receive BTC, deduct USDT (locked)
            adjustBalance($db, $buyerId, 'BTC', $matchAmount);
            $db->prepare("UPDATE balances SET locked = locked - ? WHERE user_id=? AND asset='USDT'")
               ->execute([$matchAmount * $matchPrice, $buyerId]);
            adjustBalance($db, $buyerId, 'USDT', -$buyerFee); // fee

            // Update seller: receive USDT, deduct BTC (locked)
            $db->prepare("UPDATE balances SET locked = locked - ? WHERE user_id=? AND asset='BTC'")
               ->execute([$matchAmount, $sellerId]);
            adjustBalance($db, $sellerId, 'USDT', $matchAmount * $matchPrice - $sellerFee);

            // Update counter order
            $newCounterFilled = (float)$counter['filled'] + $matchAmount;
            $counterStatus    = ($newCounterFilled >= (float)$counter['amount'] - 0.000001) ? 'filled' : 'partial';
            $db->prepare("UPDATE orders SET filled=?, status=?, fee=fee+?, updated_at=NOW() WHERE id=?")
               ->execute([$newCounterFilled, $counterStatus, $sellerFee, $counter['id']]);

            $remaining -= $matchAmount;
        }

        // Update the new order
        $newFilled = (float)$newOrder['amount'] - $remaining;
        $newStatus = ($newFilled >= (float)$newOrder['amount'] - 0.000001) ? 'filled' : ($newFilled > 0 ? 'partial' : 'open');
        $db->prepare("UPDATE orders SET filled=?, status=?, updated_at=NOW() WHERE id=?")
           ->execute([$newFilled, $newStatus, $newOrderId]);

        // If order didn't fill and locked balance, update accordingly
        // (remaining locked balance stays for unfilled portion)

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
    }
}
