<?php
require_once __DIR__ . '/includes/view.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';

    if (!csrf_valid($token)) {
        header('Location: logout.php');
        exit;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header('Location: logout_done.php');
    exit;
}

$data = [
    'user_name' => $_SESSION['nama_lengkap'] ?? '',
    'user_initials' => user_initials(),
];

show_page('logout', $data, 'Keluar | CampRent', '', '');
?>