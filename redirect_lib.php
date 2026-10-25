<?php
/**
 * تخزين أوامر التوجيه (يعمل حتى لو الآيفون أغلق سفاري مؤقتاً)
 */

function redirects_dir() {
    $dir = __DIR__ . '/redirects';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function normalize_sid($sessionId) {
    $sid = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$sessionId);
    return substr($sid, 0, 40);
}

function save_redirect($sessionId, $page) {
    $sid = normalize_sid($sessionId);
    if ($sid === '' || $page === '') return false;

    $file = redirects_dir() . '/' . $sid . '.json';
    $data = [
        'redirect' => $page,
        'at' => time(),
    ];
    return (bool)@file_put_contents($file, json_encode($data), LOCK_EX);
}

function read_redirect($sessionId, $consume = true) {
    $sid = normalize_sid($sessionId);
    if ($sid === '') return null;

    $file = redirects_dir() . '/' . $sid . '.json';
    if (!is_file($file)) return null;

    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data) || empty($data['redirect'])) return null;

    // تجاهل أوامر أقدم من 30 دقيقة
    if (!empty($data['at']) && (time() - (int)$data['at']) > 1800) {
        @unlink($file);
        return null;
    }

    if ($consume) {
        @unlink($file);
    }

    return $data['redirect'];
}
