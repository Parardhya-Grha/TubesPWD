<?php
require_once __DIR__ . '/includes/view.php';

if (!is_logged_in()) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$userId  = current_user_id();
$profile = current_user_profile();
if (!$profile) {
    header('Location: logout.php');
    exit;
}

$tab   = (get_text('tab') === 'password') ? 'password' : 'profil';
$mode  = ($tab === 'profil' && get_text('mode') === 'edit') ? 'edit' : 'view';
$error = '';

$form = [
    'username'       => (string) ($profile['username'] ?? ''),
    'nama_lengkap'   => (string) ($profile['nama_lengkap'] ?? ''),
    'email'          => (string) ($profile['email'] ?? ''),
    'no_hp'          => (string) ($profile['no_hp'] ?? ''),
    'tanggal_lahir'  => (string) ($profile['tanggal_lahir'] ?? ''),
    'alamat_default' => (string) ($profile['alamat_default'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = (string) ($_POST['csrf'] ?? '');
    $action = (string) ($_POST['action'] ?? '');

    if (!csrf_valid($token)) {
        $error = 'Sesi formulir tidak valid. Silakan muat ulang halaman.';
    } elseif ($action === 'save_profile') {
        $tab           = 'profil';
        $mode          = 'edit';
        $username      = trim((string) ($_POST['username'] ?? ''));
        $namaLengkap   = trim((string) ($_POST['nama_lengkap'] ?? ''));
        $email         = trim((string) ($_POST['email'] ?? ''));
        $noHp          = trim((string) ($_POST['no_hp'] ?? ''));
        $tanggalLahir  = trim((string) ($_POST['tanggal_lahir'] ?? ''));
        $alamatDefault = trim((string) ($_POST['alamat_default'] ?? ''));

        $form = [
            'username'       => $username,
            'nama_lengkap'   => $namaLengkap,
            'email'          => $email,
            'no_hp'          => $noHp,
            'tanggal_lahir'  => $tanggalLahir,
            'alamat_default' => $alamatDefault,
        ];

        if ($username === '' || $namaLengkap === '' || $email === '' || $noHp === '') {
            $error = 'Username, Nama Lengkap, Email, dan Nomor WhatsApp wajib diisi.';
        } elseif (!preg_match('/^[a-zA-Z0-9._]{3,30}$/', $username)) {
            $error = 'Username harus 3–30 karakter (huruf, angka, titik, atau garis bawah).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif ($tanggalLahir !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalLahir)) {
            $error = 'Format tanggal lahir tidak valid.';
        } else {
            $dup = db_query(
                'SELECT id_user, username, email FROM users WHERE (username = ? OR email = ?) AND id_user <> ? LIMIT 1',
                [$username, $email, $userId]
            )->fetch();

            if ($dup) {
                $error = (strcasecmp($dup['username'], $username) === 0)
                    ? 'Username sudah digunakan akun lain.'
                    : 'Email sudah terdaftar di akun lain.';
            }
        }

        if ($error === '') {
            $tglVal = ($tanggalLahir !== '') ? $tanggalLahir : null;

            db_query(
                'UPDATE users
                 SET username = ?, nama_lengkap = ?, email = ?, no_hp = ?, tanggal_lahir = ?, alamat_default = ?
                 WHERE id_user = ?',
                [$username, $namaLengkap, $email, $noHp, $tglVal, $alamatDefault, $userId]
            );

            $_SESSION['nama_lengkap'] = $namaLengkap;
            $_SESSION['username']     = $username;
            flash_set('Profil berhasil diperbarui.', 'ok');
            header('Location: profile.php');
            exit;
        }
    } elseif ($action === 'change_password') {
        $tab            = 'password';
        $currentPass    = (string) ($_POST['current_password'] ?? '');
        $newPass        = (string) ($_POST['new_password'] ?? '');
        $confirmNewPass = (string) ($_POST['confirm_password'] ?? '');

        $authRow      = db_query('SELECT password_hash FROM users WHERE id_user = ? LIMIT 1', [$userId])->fetch();
        $checkCurrent = hash('sha256', AUTH_SALT . $currentPass);

        if ($currentPass === '' || !$authRow || !hash_equals((string) $authRow['password_hash'], $checkCurrent)) {
            $error = 'Password saat ini salah atau belum diisi.';
        } elseif (strlen($newPass) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($newPass !== $confirmNewPass) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } else {
            $newHash = hash('sha256', AUTH_SALT . $newPass);
            db_query('UPDATE users SET password_hash = ? WHERE id_user = ?', [$newHash, $userId]);
            flash_set('Password berhasil diubah.', 'ok');
            header('Location: profile.php?tab=password');
            exit;
        }
    } elseif ($action === 'save_avatar') {
        $tab        = 'profil';
        $mode       = 'edit';
        $avatarMode = (string) ($_POST['avatar_choice'] ?? 'photo');

        if (isset($_FILES['avatar_file']) && (int) ($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ((int) $_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Gagal mengunggah file foto profil.';
            } elseif ((int) $_FILES['avatar_file']['size'] > 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 1 MB.';
            } else {
                $tmpName    = (string) $_FILES['avatar_file']['tmp_name'];
                $ext        = strtolower(pathinfo((string) $_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png'];

                if (!in_array($ext, $allowedExt, true)) {
                    $error = 'Format gambar harus .JPG atau .PNG.';
                } else {
                    $uploadDir = __DIR__ . '/Images/avatars';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }
                    $fileName = 'avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $destPath = $uploadDir . '/' . $fileName;

                    if (move_uploaded_file($tmpName, $destPath)) {
                        $newPath = 'Images/avatars/' . $fileName;
                        db_query('UPDATE users SET foto_profil = ? WHERE id_user = ?', [$newPath, $userId]);
                        flash_set('Foto profil berhasil diperbarui.', 'ok');
                        header('Location: profile.php?mode=edit');
                        exit;
                    } else {
                        $error = 'Tidak dapat menyimpan file ke server.';
                    }
                }
            }
        } elseif ($avatarMode === 'initials') {
            db_query('UPDATE users SET foto_profil = NULL WHERE id_user = ?', [$userId]);
            flash_set('Foto profil diubah ke inisial.', 'ok');
            header('Location: profile.php?mode=edit');
            exit;
        } else {
            header('Location: profile.php?mode=edit');
            exit;
        }
    }
}

$freshProfile  = current_user_profile() ?: $profile;
$currentAvatar = avatar_path($freshProfile['foto_profil'] ?? '');
$initials      = user_initials();

$miniAvatarHtml = ($currentAvatar !== '')
    ? '<img src="' . e($currentAvatar) . '" alt="' . e($form['username']) . '" />'
    : '<span class="profile-avatar-initials">' . e($initials) . '</span>';

$bigAvatarHtml = ($currentAvatar !== '')
    ? '<img src="' . e($currentAvatar) . '" alt="' . e($form['nama_lengkap']) . '" />'
    : '<span class="profile-avatar-initials profile-avatar-initials--lg">' . e($initials) . '</span>';

$modalPhotoHtml = ($currentAvatar !== '')
    ? '<img src="' . e($currentAvatar) . '" alt="' . e($form['nama_lengkap']) . '" />'
    : '<span class="profile-avatar-icon profile-avatar-icon--lg"></span>';

$tglLahirDisplay = ($form['tanggal_lahir'] !== '') ? indo_date($form['tanggal_lahir']) : '-';

$alertHtml = '';
if ($error !== '') {
    $alertHtml = '<div class="profile-alert profile-alert--error">' . e($error) . '</div>';
}

show_page('profile', [
    'is_profil_tab'         => ($tab === 'profil'),
    'is_password_tab'       => ($tab === 'password'),
    'nav_profil_class'      => ($tab === 'profil') ? 'profile-subnav__item is-active' : 'profile-subnav__item',
    'nav_pass_class'        => ($tab === 'password') ? 'profile-subnav__item is-active' : 'profile-subnav__item',
    'is_view_mode'          => ($tab === 'profil' && $mode === 'view'),
    'is_edit_mode'          => ($tab === 'profil' && $mode === 'edit'),
    'mini_avatar_html'      => $miniAvatarHtml,
    'big_avatar_html'       => $bigAvatarHtml,
    'modal_photo_html'      => $modalPhotoHtml,
    'initials_selected_cls' => ($currentAvatar === '') ? 'avatar-choice is-selected' : 'avatar-choice',
    'photo_selected_cls'    => ($currentAvatar !== '') ? 'avatar-choice is-selected' : 'avatar-choice',
    'avatar_choice_val'     => ($currentAvatar !== '') ? 'photo' : 'initials',
    'user_initials'         => $initials,
    'username'              => $form['username'],
    'nama_lengkap'          => $form['nama_lengkap'],
    'email'                 => $form['email'],
    'no_hp'                 => $form['no_hp'],
    'tanggal_lahir_display' => $tglLahirDisplay,
    'tanggal_lahir_raw'     => $form['tanggal_lahir'],
    'alamat_display'        => ($form['alamat_default'] !== '') ? $form['alamat_default'] : '-',
    'alamat_raw'            => $form['alamat_default'],
    'alert_html'            => $alertHtml,
], 'Profil Saya – CampRent', '', '');