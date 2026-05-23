<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
if (!isLoggedIn()) jsonResponse(['success'=>false,'error'=>'Unauthorized'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'error'=>'Method not allowed'], 405);
if (!verifyCsrf($_POST['csrf'] ?? '')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF token'], 403);

$userId  = getUserId();
$asset   = strtoupper(trim($_POST['asset'] ?? ''));
$amount  = (float)($_POST['amount'] ?? 0);

$allowed = ['USDT','BTC','ETH','BNB'];
if (!in_array($asset, $allowed)) jsonResponse(['success'=>false,'error'=>'Unsupported asset']);
if ($amount <= 0)                jsonResponse(['success'=>false,'error'=>'Amount must be positive']);

$maxDeposits = ['USDT'=>100000,'BTC'=>5,'ETH'=>50,'BNB'=>200];
if ($amount > ($maxDeposits[$asset] ?? 1000)) {
    jsonResponse(['success'=>false,'error'=>'Exceeds maximum demo deposit']);
}

$db = getDB();
$db->beginTransaction();
try {
    adjustBalance($db, $userId, $asset, $amount);

    $db->prepare("INSERT INTO transactions (user_id, type, asset, amount, status, notes)
                  VALUES (?,?,?,?,?,?)")
       ->execute([$userId, 'deposit', $asset, $amount, 'completed', 'Demo deposit']);

    $db->commit();
    $newBal = getBalance($userId, $asset);
    jsonResponse(['success'=>true,'new_balance'=>$newBal]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['success'=>false,'error'=>$e->getMessage()], 500);
}
