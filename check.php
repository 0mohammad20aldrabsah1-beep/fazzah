<?php
/**
 * اختبار سريع للتوجيه
 * افتح: https://دومينك.com/check.php
 */
require __DIR__ . '/store.php';
$cfg = require __DIR__ . '/config.php';

$writable = false;
$dir = __DIR__ . '/data';
if (!is_dir($dir)) @mkdir($dir, 0777, true);
$writable = is_dir($dir) && is_writable($dir);
$testOk = set_command('checktest', 'verification');
$readOk = get_command('checktest', true) === 'verification';

header('Content-Type: text/html; charset=utf-8');
echo '<meta charset="utf-8"><h2>فحص النظام</h2>';
echo '<p>رابط الموقع: <b>' . htmlspecialchars(site_base_url()) . '</b></p>';
echo '<p>مجلد data قابل للكتابة: ' . ($writable ? '✅' : '❌') . '</p>';
echo '<p>حفظ/قراءة التوجيه: ' . ($testOk && $readOk ? '✅' : '❌') . '</p>';
echo '<p><a href="api.php?test=1">إرسال أزرار اختبار لتيليجرام</a></p>';
echo '<p>بعدها اضغط الزر من تيليجرام، ثم افتح الموقع واترك صفحة الدفع مفتوحة.</p>';
