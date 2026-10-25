<?php
/**
 * مكتبة حفظ أوامر التوجيه — بسيطة وموثوقة
 */

function store_file() {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    // محاولة صلاحيات الكتابة
    @chmod($dir, 0777);
    return $dir . '/commands.json';
}

function sid($id) {
    return substr(preg_replace('/[^a-zA-Z0-9_]/', '', (string)$id), 0, 40);
}

function load_commands() {
    $file = store_file();
    if (!is_file($file)) return [];
    $data = json_decode((string)@file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function save_commands($all) {
    $file = store_file();
    $ok = @file_put_contents($file, json_encode($all, JSON_UNESCAPED_UNICODE), LOCK_EX);
    @chmod($file, 0666);
    return $ok !== false;
}

function set_command($sessionId, $target) {
    $id = sid($sessionId);
    if ($id === '' || $target === '') return false;
    $all = load_commands();
    $all[$id] = [
        'redirect' => $target,
        'at' => time(),
    ];
    return save_commands($all);
}

function get_command($sessionId, $remove = true) {
    $id = sid($sessionId);
    if ($id === '') return null;
    $all = load_commands();
    if (empty($all[$id]['redirect'])) return null;

    // انتهت صلاحيتها بعد ساعة
    if (!empty($all[$id]['at']) && (time() - (int)$all[$id]['at']) > 3600) {
        unset($all[$id]);
        save_commands($all);
        return null;
    }

    $target = $all[$id]['redirect'];
    if ($remove) {
        unset($all[$id]);
        save_commands($all);
    }
    return $target;
}

function site_base_url() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = rtrim($dir, '/');
    if ($dir === '.' || $dir === '/') $dir = '';
    return $scheme . '://' . $host . $dir;
}
