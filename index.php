<?php
require_once __DIR__ . '/config/session.php';
if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
