<?php
require_once __DIR__ . '/includes/helpers.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$form = [
    'nama_lengkap' => '',
    'username'     => '',
    'no_hp'        => '',
    'email'        => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token           = (string) ($_POST['csrf'] ?? '');
    $namaLengkap     = trim((string) ($_POST['nama_lengkap'] ?? ''));
    $username        = trim((string) ($_POST['username'] ?? ''));
    $noHp            = trim((string) ($_POST['no_hp'] ?? ''));
    $email           = trim((string) ($_POST['email'] ?? ''));
    $password        = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $form['nama_lengkap'] = $namaLengkap;
    $form['username']     = $username;
    $form['no_hp']        = $noHp;
    $form['email']        = $email;

    if (!csrf_valid($token)) {
        $error = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } elseif ($namaLengkap === '' || $username === '' || $noHp === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!preg_match('/^[a-zA-Z0-9._]{3,30}$/', $username)) {
        $error = 'Username harus 3–30 karakter dan hanya boleh berisi huruf, angka, titik, atau garis bawah.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Confirm Password tidak cocok dengan Password.';
    } else {
        // Check database first for duplicate username or email
        $existing = db_query(
            'SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $email]
        )->fetch();

        if ($existing) {
            if (strcasecmp($existing['username'], $username) === 0) {
                $error = 'Username "' . $username . '" sudah digunakan. Silakan pilih username lain.';
            } else {
                $error = 'Email "' . $email . '" sudah terdaftar. Silakan gunakan email lain atau masuk.';
            }
        } else {
            // Salt taken only from PHP config (not stored in the database)
            $salt = AUTH_SALT; // <-- THIS VARIABLE IS THE SALT

            // Combine the PHP salt with the plain-text password and compute the SHA-256 hash
            $passwordHash = hash('sha256', $salt . $password); // <-- THIS VARIABLE IS THE SHA-256 HASH

            db_query(
                'INSERT INTO users (nama_lengkap, username, email, no_hp, password_hash, role)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$namaLengkap, $username, $email, $noHp, $passwordHash, 'customer']
            );

            global $pdo;
            $newUserId = (int) $pdo->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['id_user']      = $newUserId;
            $_SESSION['nama_lengkap'] = $namaLengkap;
            $_SESSION['username']     = $username;
            $_SESSION['role']         = 'customer';

            header('Location: index.php');
            exit;
        }
    }
}

$alertHtml = '';
if ($error !== '') {
    $alertHtml = '<div class="auth__alert auth__alert--error">' . e($error) . '</div>';
}

$html = file_get_contents(__DIR__ . '/templates/pages/register.xhtml');
echo strtr($html, [
    '{{hero_img}}'     => e(ASSET['hero']),
    '{{csrf}}'         => e(csrf_token()),
    '{{nama_lengkap}}' => e($form['nama_lengkap']),
    '{{username}}'     => e($form['username']),
    '{{no_hp}}'        => e($form['no_hp']),
    '{{email}}'        => e($form['email']),
    '{{{alert}}}'      => $alertHtml,
]);