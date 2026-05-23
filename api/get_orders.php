<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
if (!isLoggedIn()) jsonResponse(['success'=>false,'error'=>'Unauthorized'], 401);

$userId = getUserId();
$status = $_GET['status'] ?? 'all';
$db     = getDB();

if ($status === 'open') {
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 100");
} else {
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 100");
}
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Calculate fill percentage
foreach ($orders as &$o) {
    $o['filled'] = $o['amount'] > 0 ? round(($o['filled'] / $o['amount']) * 100, 2) : 0;
}

jsonResponse(['success'=>true,'orders'=>$orders]);
