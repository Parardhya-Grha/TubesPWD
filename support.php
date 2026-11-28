<?php
// support.php - the Support page (Figma node "Support").
// The form is checked here on the server. When it is valid the visitor is sent to
// WhatsApp (wa.me) with the message already typed in. Nothing is saved in the database.
require_once __DIR__ . '/includes/view.php';

// WhatsApp number of the support team, in international format (0812 3456 7890 -> 62...).
const SUPPORT_WA_NUMBER = '6281234567890';

// The choices of the "Topik Bantuan" drop-down.
const SUPPORT_TOPICS = [
    'Konsultasi Pemesanan',
    'Konsultasi Ukuran Gear',
    'Perpanjangan Durasi Sewa',
    'Laporan Kendala Alat',
    'Lainnya',
];

$error = '';
$nama  = isset($_SESSION['nama_lengkap']) ? (string) $_SESSION['nama_lengkap'] : '';
$kontak = '';
$topik = SUPPORT_TOPICS[0];
$kode  = '';
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = (string) ($_POST['csrf'] ?? '');
    $nama   = trim((string) ($_POST['nama'] ?? ''));
    $kontak = trim((string) ($_POST['kontak'] ?? ''));
    $topik  = (string) ($_POST['topik'] ?? '');
    $kode   = trim((string) ($_POST['kode'] ?? ''));
    $pesan  = trim((string) ($_POST['pesan'] ?? ''));

    $is_phone = preg_match('/^\+?[0-9][0-9\s\-]{7,18}$/', $kontak) === 1;
    $is_email = filter_var($kontak, FILTER_VALIDATE_EMAIL) !== false;

    if (!csrf_valid($token)) {
        $error = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } elseif ($nama === '' || $kontak === '' || $pesan === '') {
        $error = 'Nama lengkap, WhatsApp atau email, dan detail pesan wajib diisi.';
    } elseif (!in_array($topik, SUPPORT_TOPICS, true)) {
        $error = 'Topik bantuan tidak valid.';
    } elseif (!$is_phone && !$is_email) {
        $error = 'Isi WhatsApp (angka) atau email yang valid.';
    } elseif ($kode !== '' && preg_match('/^[A-Za-z0-9\-]{1,30}$/', $kode) !== 1) {
        $error = 'Kode booking hanya boleh berisi huruf, angka, dan tanda hubung.';
    } elseif (mb_strlen($nama) > 100 || mb_strlen($kontak) > 100 || mb_strlen($pesan) > 1000) {
        $error = 'Teks terlalu panjang. Persingkat isi formulir Anda.';
    } else {
        $text = "Halo Tim Support CampRent,\n"
              . 'Nama: ' . $nama . "\n"
              . 'Kontak: ' . $kontak . "\n"
              . 'Topik: ' . $topik . "\n"
              . ($kode !== '' ? 'Kode Booking: ' . $kode . "\n" : '')
              . "\nPesan:\n" . $pesan;
        header('Location: https://wa.me/' . SUPPORT_WA_NUMBER . '?text=' . rawurlencode($text));
        exit;
    }
}

$topics = [];
foreach (SUPPORT_TOPICS as $t) {
    $topics[] = ['value' => $t, 'label' => $t, 'selected' => ($t === $topik)];
}

show_page('support', [
    'error'  => $error,
    'nama'   => $nama,
    'kontak' => $kontak,
    'kode'   => $kode,
    'pesan'  => $pesan,
    'topics' => $topics,
], 'Support – CampRent', '/support', '');
