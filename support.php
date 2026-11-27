<?php
// support.php - the SUPPORT page (how to rent, rental rules, and a message form for the team).
// Step 1: read the form.  Step 2: save the message in the database.
// Step 3: turn the data into values for the template and show the page.
require_once __DIR__ . '/includes/view.php';

// The choices of the "Topik Bantuan" dropdown. These exact words are saved in the database.
$TOPICS = [
    'Konsultasi Pemesanan',
    'Konsultasi Ukuran Gear',
    'Perpanjangan Masa Sewa',
    'Laporan Kendala Alat',
];

// ----- Step 1: what the visitor typed (empty when the page is just opened) -----
$values = [
    'nama'   => '',
    'kontak' => '',
    'topik'  => $TOPICS[0],
    'kode'   => '',
    'pesan'  => '',
];
if (is_logged_in()) {
    $values['nama'] = (string) ($_SESSION['nama_lengkap'] ?? '');   // fill the name for logged-in users
}
$errors = ['nama' => '', 'kontak' => '', 'pesan' => '', 'form' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf'] ?? '');

    $values['nama']   = mb_substr(trim((string) ($_POST['nama'] ?? '')), 0, 100);
    $values['kontak'] = mb_substr(trim((string) ($_POST['kontak'] ?? '')), 0, 100);
    $values['topik']  = (string) ($_POST['topik'] ?? '');
    $values['kode']   = mb_substr(trim((string) ($_POST['kode_booking'] ?? '')), 0, 30);
    $values['pesan']  = trim((string) ($_POST['pesan'] ?? ''));

    if (!in_array($values['topik'], $TOPICS, true)) {
        $values['topik'] = $TOPICS[0];                    // unknown topic -> the first one
    }

    if (!csrf_valid($token)) {
        $errors['form'] = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        // ----- Check every field (the JavaScript checks the same things, but never trust it alone) -----
        $phone = preg_replace('/[\s-]/', '', $values['kontak']);
        if (mb_strlen($values['nama']) < 3) {
            $errors['nama'] = 'Nama minimal 3 karakter.';
        }
        if (!filter_var($values['kontak'], FILTER_VALIDATE_EMAIL) && !preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', $phone)) {
            $errors['kontak'] = 'Isi nomor WhatsApp (08xx) atau email yang valid.';
        }
        if (mb_strlen($values['pesan']) < 10 || mb_strlen($values['pesan']) > 2000) {
            $errors['pesan'] = 'Detail pesan 10 sampai 2000 karakter.';
        }

        // ----- Step 2: save it -----
        if ($errors['nama'] == '' && $errors['kontak'] == '' && $errors['pesan'] == '') {
            $id_user = null;                              // guests have no account: saved as empty (NULL)
            if (is_logged_in()) {
                $id_user = current_user_id();
            }
            $kode = null;                                 // the booking code is optional
            if ($values['kode'] != '') {
                $kode = $values['kode'];
            }

            try {
                db_query(
                    'INSERT INTO support_messages (id_user, nama_lengkap, kontak, topik, kode_booking, pesan)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [$id_user, $values['nama'], $values['kontak'], $values['topik'], $kode, $values['pesan']]
                );
                // Show the message once on the next page (the little box at the bottom), then forget it
                flash_set('Pesan terkirim. Tim kami akan menghubungi kamu secepatnya.', 'ok');
                header('Location: support.php');
                exit;
            } catch (PDOException $error) {
                // Most likely db_support_messages.sql has not been imported yet
                $errors['form'] = 'Pesan belum bisa disimpan. Coba lagi nanti.';
            }
        }
    }
}

// ----- Step 3: prepare the values the template needs -----
$topic_options = [];
foreach ($TOPICS as $topic) {
    $topic_options[] = [
        'value'    => $topic,
        'selected' => ($topic == $values['topik']),
    ];
}

show_page('support', [
    'nama'       => $values['nama'],
    'kontak'     => $values['kontak'],
    'kode'       => $values['kode'],
    'pesan'      => $values['pesan'],
    'topics'     => $topic_options,
    'err_nama'   => $errors['nama'],
    'err_kontak' => $errors['kontak'],
    'err_pesan'  => $errors['pesan'],
    'err_form'   => $errors['form'],
], 'Support – CampRent', '/support', '', [
    'css' => 'css/support.css',
    'js'  => 'js/support.js',
]);
