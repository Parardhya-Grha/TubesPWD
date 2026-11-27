<?php
require_once __DIR__ . '/includes/view.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = current_user_id();

$user = db_query(
    'SELECT id_user, nama_lengkap, email, no_hp, alamat_default
     FROM users
     WHERE id_user = ?
     LIMIT 1',
    [$user_id]
)->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat_default'] ?? '');

    if (!csrf_valid($token)) {
        $error = 'Formulir tidak valid. Silakan muat ulang halaman.';
    } elseif ($nama === '' || $no_hp === '') {
        $error = 'Nama lengkap dan nomor WhatsApp wajib diisi.';
    } elseif (strlen($nama) > 100) {
        $error = 'Nama maksimal 100 karakter.';
    } elseif (strlen($no_hp) > 20) {
        $error = 'Nomor WhatsApp terlalu panjang.';
    } else {
        db_query(
            'UPDATE users
             SET nama_lengkap = ?, no_hp = ?, alamat_default = ?
             WHERE id_user = ?',
            [$nama, $no_hp, $alamat, $user_id]
        );

        $_SESSION['nama_lengkap'] = $nama;

        header('Location: profile.php');
        exit;
    }

    $user['nama_lengkap'] = $nama;
    $user['no_hp'] = $no_hp;
    $user['alamat_default'] = $alamat;
}

$data = [
    'nama_lengkap' => $user['nama_lengkap'],
    'email' => $user['email'],
    'no_hp' => $user['no_hp'],
    'alamat' => $user['alamat_default'] ?? '',
    'initials' => user_initials(),
    'error' => $error,
];

show_page('profile_edit', $data, 'Edit Profil | CampRent', '', '');
?>