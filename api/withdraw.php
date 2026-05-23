<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
if (!isLoggedIn()) jsonResponse(['success'=>false,'error'=>'Unauthorized'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'error'=>'Method not allowed'], 405);
if (!verifyCsrf($_POST['csrf'] ?? '')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF token'], 403);

$userId  = getUserId();
$asset   = strtoupper(trim($_POST['asset']   ?? ''));
$amount  = (float)($_POST['amount']  ?? 0);
$address = trim($_POST['address'] ?? '');

$allowed = ['USDT','BTC','ETH','BNB'];
if (!in_array($asset, $allowed))   jsonResponse(['success'=>false,'error'=>'Unsupported asset']);
if ($amount <= 0)                  jsonResponse(['success'=>false,'error'=>'Amount must be positive']);
if (empty($address))               jsonResponse(['success'=>false,'error'=>'Withdrawal address required']);

$db       = getDB();
$available = getBalance($userId, $asset);
if ($available < $amount) {
    jsonResponse(['success'=>false,'error'=>sprintf('Insufficient balance. Available: %.8f %s', $available, $asset)]);
}

$db->beginTransaction();
try {
    adjustBalance($db, $userId, $asset, -$amount);

    $db->prepare("INSERT INTO transactions (user_id, type, asset, amount, address, status, notes)
                  VALUES (?,?,?,?,?,?,?)")
       ->execute([$userId, 'withdrawal', $asset, $amount, $address, 'pending', 'User withdrawal']);

    $db->commit();
    jsonResponse(['success'=>true,'message'=>'Withdrawal submitted. Processing within 24h (demo).']);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['success'=>false,'error'=>$e->getMessage()], 500);
}
