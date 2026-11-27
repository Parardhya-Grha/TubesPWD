
<?php
require_once __DIR__ . '/includes/helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$userId = current_user_id();

$user = db_query(
    'SELECT id_user, username, nama_lengkap, email, no_hp,
            jenis_kelamin, tanggal_lahir, alamat_default
     FROM users
     WHERE id_user = ?
     LIMIT 1',
    [$userId]
)->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid($_POST['csrf'] ?? '')) {
        $error = 'Permintaan tidak valid. Silakan coba lagi.';
    } else {

        $username = trim($_POST['username'] ?? '');
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $noHp = trim($_POST['no_hp'] ?? '');
        $gender = trim($_POST['jenis_kelamin'] ?? '');
        $tanggalLahir = trim($_POST['tanggal_lahir'] ?? '');
        $alamat = trim($_POST['alamat_default'] ?? '');

        if (
            $username === '' ||
            $nama === '' ||
            $email === '' ||
            $noHp === ''
        ) {
            $error = 'Username, nama, email, dan nomor WhatsApp wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $error = 'Username harus 3-50 karakter, hanya huruf, angka, dan underscore.';
        } elseif (!in_array($gender, ['Laki-laki', 'Perempuan', ''], true)) {
            $error = 'Jenis kelamin tidak valid.';
        } else {

            $tanggalLahirDB = null;

            if ($tanggalLahir !== '') {
                $date = DateTime::createFromFormat('Y-m-d', $tanggalLahir);

                if (!$date || $date->format('Y-m-d') !== $tanggalLahir) {
                    $error = 'Tanggal lahir tidak valid.';
                } else {
                    $tanggalLahirDB = $tanggalLahir;
                }
            }

            if ($error === '') {

                $usernameCheck = db_query(
                    'SELECT id_user FROM users
                     WHERE username = ? AND id_user != ?
                     LIMIT 1',
                    [$username, $userId]
                )->fetch();

                $emailCheck = db_query(
                    'SELECT id_user FROM users
                     WHERE email = ? AND id_user != ?
                     LIMIT 1',
                    [$email, $userId]
                )->fetch();

                if ($usernameCheck) {
                    $error = 'Username sudah digunakan.';
                } elseif ($emailCheck) {
                    $error = 'Email sudah digunakan akun lain.';
                } else {

                    db_query(
                        'UPDATE users
                         SET username = ?,
                             nama_lengkap = ?,
                             email = ?,
                             no_hp = ?,
                             jenis_kelamin = ?,
                             tanggal_lahir = ?,
                             alamat_default = ?
                         WHERE id_user = ?',
                        [
                            $username,
                            $nama,
                            $email,
                            $noHp,
                            $gender ?: null,
                            $tanggalLahirDB,
                            $alamat,
                            $userId
                        ]
                    );

                    header('Location: profile.php');
                    exit;
                }
            }
        }
    }

    $user['username'] = $username ?? $user['username'];
    $user['nama_lengkap'] = $nama ?? $user['nama_lengkap'];
    $user['email'] = $email ?? $user['email'];
    $user['no_hp'] = $noHp ?? $user['no_hp'];
    $user['jenis_kelamin'] = $gender ?? $user['jenis_kelamin'];
    $user['tanggal_lahir'] = $tanggalLahir ?? $user['tanggal_lahir'];
    $user['alamat_default'] = $alamat ?? $user['alamat_default'];
}

$html = file_get_contents(__DIR__ . '/templates/pages/edit_profile.xhtml');

echo strtr($html, [
    '{{username}}' => e($user['username'] ?? ''),
    '{{nama_lengkap}}' => e($user['nama_lengkap'] ?? ''),
    '{{email}}' => e($user['email'] ?? ''),
    '{{no_hp}}' => e($user['no_hp'] ?? ''),
    '{{jenis_kelamin_laki}}' => (($user['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'checked' : '',
    '{{jenis_kelamin_perempuan}}' => (($user['jenis_kelamin'] ?? '') === 'Perempuan') ? 'checked' : '',
    '{{tanggal_lahir}}' => e($user['tanggal_lahir'] ?? ''),
    '{{alamat_default}}' => e($user['alamat_default'] ?? ''),
    '{{csrf}}' => e(csrf_token()),
    '{{error}}' => e($error),
]);
