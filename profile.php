<?php
require_once __DIR__ . '/includes/view.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = current_user_id();

$user = db_query(
    'SELECT id_user, nama_lengkap, email, no_hp,
            alamat_default, created_at
     FROM users
     WHERE id_user = ?
     LIMIT 1',
    [$user_id]
)->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

$data = [
    'nama_lengkap' => $user['nama_lengkap'],
    'email' => $user['email'],
    'no_hp' => $user['no_hp'],
    'alamat' => $user['alamat_default'] ?? '',
    'created_at' => indo_date($user['created_at']),
    'initials' => user_initials(),
];

show_page('profile', $data, 'Profil Saya | CampRent', '', '');
?>