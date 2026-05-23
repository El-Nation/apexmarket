<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');
if (!isLoggedIn()) jsonResponse(['success'=>false,'error'=>'Unauthorized'], 401);

$userId   = getUserId();
$balances = getAllBalances($userId);
jsonResponse(['success'=>true,'balances'=>$balances]);
