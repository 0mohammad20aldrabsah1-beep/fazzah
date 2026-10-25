<?php
/**
 * poll.php — العميل يسأل: هل في توجيه؟
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require __DIR__ . '/store.php';

$sessionId = isset($_GET['s']) ? $_GET['s'] : (isset($_GET['sessionId']) ? $_GET['sessionId'] : '');
$redirect = get_command($sessionId, true);

echo json_encode([
    'ok' => true,
    'redirect' => $redirect,
], JSON_UNESCAPED_UNICODE);
