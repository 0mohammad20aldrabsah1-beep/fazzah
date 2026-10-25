<?php
/**
 * سجّل الويبهوك مرة واحدة بعد الرفع:
 * https://دومينك.com/set-webhook.php
 *
 * لازم عشان الزر يشتغل داخل تيليجرام بدون فتح متصفح
 */

$cfg = require __DIR__ . '/config.php';
require __DIR__ . '/store.php';

$webhookUrl = site_base_url() . '/webhook.php';
$token = $cfg['telegram_bot_token'];

// احذف القديم ثم سجّل من جديد
@file_get_contents("https://api.telegram.org/bot{$token}/deleteWebhook?drop_pending_updates=true");

$url = "https://api.telegram.org/bot{$token}/setWebhook?url=" . rawurlencode($webhookUrl);
$res = false;

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $res = curl_exec($ch);
    curl_close($ch);
}
if ($res === false) {
    $res = @file_get_contents($url);
}

$info = @file_get_contents("https://api.telegram.org/bot{$token}/getWebhookInfo");
$json = json_decode((string)$res, true);

header('Content-Type: text/html; charset=utf-8');
echo '<meta charset="utf-8">';
echo '<h2>تسجيل Webhook</h2>';
echo '<p><b>الرابط:</b> ' . htmlspecialchars($webhookUrl) . '</p>';

if (!empty($json['ok'])) {
    echo '<p style="color:green;font-size:20px">✅ تم — الأزرار داخل تيليجرام بدون فتح متصفح</p>';
} else {
    echo '<p style="color:red;font-size:20px">❌ فشل التسجيل</p>';
}

echo '<h3>نتيجة setWebhook</h3><pre style="direction:ltr;text-align:left">' . htmlspecialchars((string)$res) . '</pre>';
echo '<h3>getWebhookInfo</h3><pre style="direction:ltr;text-align:left">' . htmlspecialchars((string)$info) . '</pre>';
echo '<p>الآن جرّب إرسال بطاقة، واضغط الزر من تيليجرام — لازم تظهر رسالة صغيرة: تم التوجيه ✅</p>';
