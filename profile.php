<?php
require_once __DIR__ . '/includes/helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = db_query(
    'SELECT id_user, username, nama_lengkap, email, no_hp,
            jenis_kelamin, tanggal_lahir, alamat_default
     FROM users
     WHERE id_user = ?
     LIMIT 1',
    [current_user_id()]
)->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

$html = file_get_contents(__DIR__ . '/templates/pages/profile.xhtml');

echo strtr($html, [
    '{{profile_username}}' => e($user['username'] ?? '-'),
    '{{profile_name}}' => e($user['nama_lengkap'] ?? '-'),
    '{{profile_email}}' => e($user['email'] ?? '-'),
    '{{profile_phone}}' => e($user['no_hp'] ?? '-'),
    '{{profile_gender}}' => e($user['jenis_kelamin'] ?? '-'),
    '{{profile_birthdate}}' => e($user['tanggal_lahir'] ?? '-'),
    '{{profile_address}}' => e($user['alamat_default'] ?? '-'),
    '{{csrf}}' => e(csrf_token()),
]);
