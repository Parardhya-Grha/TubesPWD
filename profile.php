<?php
require_once __DIR__ . '/includes/view.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['id_user'];

$user = db_query(
    'SELECT id_user, nama_lengkap, email, no_hp, alamat_default, created_at
     FROM users
     WHERE id_user = ?',
    [$user_id]
)->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat_default'] ?? '');
    $token = $_POST['csrf'] ?? '';

    if (!csrf_valid($token)) {
        $error = 'Token tidak valid. Silakan refresh halaman.';
    } elseif ($nama === '' || $no_hp === '') {
        $error = 'Nama dan nomor HP wajib diisi.';
    } else {
        db_query(
            'UPDATE users
             SET nama_lengkap = ?, no_hp = ?, alamat_default = ?
             WHERE id_user = ?',
            [$nama, $no_hp, $alamat, $user_id]
        );

        $_SESSION['nama_lengkap'] = $nama;

        $success = 'Profil berhasil diperbarui.';

        $user = db_query(
            'SELECT id_user, nama_lengkap, email, no_hp, alamat_default, created_at
             FROM users
             WHERE id_user = ?',
            [$user_id]
        )->fetch();
    }
}

$data = [
    'nama_lengkap' => $user['nama_lengkap'],
    'email' => $user['email'],
    'no_hp' => $user['no_hp'],
    'alamat_default' => $user['alamat_default'] ?? '',
    'created_at' => indo_date($user['created_at']),
    'error' => $error,
    'success' => $success,
];

show_page('profile', $data, 'Profil Saya | CampRent', '', '');
?>
