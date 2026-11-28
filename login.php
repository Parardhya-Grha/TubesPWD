<?php
require_once __DIR__ . '/includes/helpers.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = (string) ($_POST['csrf'] ?? '');
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_valid($token)) {
        $error = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } elseif ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $user = db_query(
            'SELECT id_user, nama_lengkap, username, email, password_hash, role
             FROM users
             WHERE email = ?
             LIMIT 1',
            [$email]
        )->fetch();

        if ($user) {
            // Salt taken only from PHP config (not stored in the database)
            $salt = AUTH_SALT; // <-- THIS VARIABLE IS THE SALT

            // Combine the PHP salt with the submitted password and hash with SHA-256
            $computedHash = hash('sha256', $salt . $password); // <-- THIS VARIABLE IS THE SHA-256 HASH

            if (hash_equals($user['password_hash'], $computedHash)) {
                session_regenerate_id(true);
                $_SESSION['id_user']      = (int) $user['id_user'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['role']         = $user['role'];

                merge_guest_cart_to_user((int) $user['id_user']);

                $redirect = 'index.php';
                $requestedRedirect = (string) ($_GET['redirect'] ?? $_POST['redirect'] ?? '');
                if (in_array($requestedRedirect, ['index.php', 'cart.php', 'product.php', 'profile.php'], true)) {
                    $redirect = $requestedRedirect;
                }

                header('Location: ' . $redirect);
                exit;
            }
        }

        $error = 'Email atau password salah.';
    }
}

$alertHtml = '';
if ($error !== '') {
    $alertHtml = '<div class="auth__alert auth__alert--error">' . e($error) . '</div>';
}

$html = file_get_contents(__DIR__ . '/templates/pages/login.xhtml');
echo strtr($html, [
    '{{hero_img}}' => e(ASSET['hero']),
    '{{csrf}}'     => e(csrf_token()),
    '{{email}}'    => e($email),
    '{{{alert}}}'  => $alertHtml,
]);