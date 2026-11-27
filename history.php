<?php
require_once __DIR__ . '/includes/view.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = current_user_id();

$filter = $_GET['status'] ?? 'all';

$allowed_filters = [
    'all',
    'sedang_disewa',
    'selesai',
    'menunggu_konfirmasi',
    'dibatalkan'
];

if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'all';
}

$sql = 'SELECT *
        FROM rentals
        WHERE id_user = ?';

$params = [$user_id];

if ($filter !== 'all') {
    $sql .= ' AND status_sewa = ?';
    $params[] = $filter;
}

$sql .= ' ORDER BY created_at DESC';

$rentals = db_query($sql, $params)->fetchAll();

$cards_html = '';

foreach ($rentals as $rental) {

    $items = db_query(
        'SELECT ri.qty, ri.harga_per_hari, ri.subtotal_item,
                p.nama_produk, p.gambar_utama, pv.nama_warna
         FROM rental_items ri
         JOIN products p ON p.id_product = ri.id_product
         JOIN product_variants pv ON pv.id_variant = ri.id_variant
         WHERE ri.id_rental = ?',
        [$rental['id_rental']]
    )->fetchAll();

    $items_html = '';

    foreach ($items as $item) {
        $items_html .= '
        <div class="history-product">

            <img src="' . e(img_path($item['gambar_utama'])) . '"
                 alt="' . e($item['nama_produk']) . '" />

            <div class="history-product-detail">

                <h4>' . e($item['nama_produk']) . '</h4>

                <p>Warna: ' . e($item['nama_warna']) . '</p>

                <p>Jumlah: ' . e($item['qty']) . '</p>

                <p>Harga per hari: ' .
                    e(rupiah($item['harga_per_hari'])) . '</p>

                <p>Subtotal: ' .
                    e(rupiah($item['subtotal_item'])) . '</p>

            </div>

        </div>';
    }

    $status = str_replace('_', ' ', $rental['status_sewa']);

    $cards_html .= '
    <article class="history-card">

        <div class="history-card-top">

            <div>
                <span class="account-muted">Kode Transaksi</span>
                <h3>' . e($rental['kode_transaksi']) . '</h3>
            </div>

            <span class="history-status">' . e($status) . '</span>

        </div>

        <div class="history-meta">

            <div>
                <span class="account-muted">Tanggal Mulai</span>
                <strong>' . e(indo_date($rental['tanggal_mulai_sewa'])) . '</strong>
            </div>

            <div>
                <span class="account-muted">Tanggal Selesai</span>
                <strong>' . e(indo_date($rental['tanggal_selesai_sewa'])) . '</strong>
            </div>

            <div>
                <span class="account-muted">Durasi</span>
                <strong>' . e($rental['durasi_hari']) . ' Hari</strong>
            </div>

        </div>

        <div class="history-products">

            <h4>Barang yang Disewa</h4>

            ' . $items_html . '

        </div>

        <div class="history-summary">

            <div>
                <span>Subtotal Sewa</span>
                <strong>' . e(rupiah($rental['subtotal_sewa'])) . '</strong>
            </div>

            <div>
                <span>Biaya Pengiriman</span>
                <strong>' . e(rupiah($rental['biaya_pengiriman'])) . '</strong>
            </div>

            <div>
                <span>Biaya Layanan</span>
                <strong>' . e(rupiah($rental['biaya_layanan'])) . '</strong>
            </div>

            <div class="history-total">
                <span>Total Pembayaran</span>
                <strong>' . e(rupiah($rental['total_bayar'])) . '</strong>
            </div>

        </div>

        <div class="history-payment">

            <div>
                <span class="account-muted">Pembayaran</span>
                <strong>' . e($rental['metode_pembayaran']) . '</strong>
            </div>

            <div>
                <span class="account-muted">Status Pembayaran</span>
                <strong>' . e(str_replace('_', ' ', $rental['status_pembayaran'])) . '</strong>
            </div>

        </div>

        <div class="history-address">

            <span class="account-muted">Alamat Pengiriman</span>

            <p>' . e($rental['alamat_pengiriman']) . '</p>

        </div>

    </article>';
}

$data = [
    'cards_html' => $cards_html,
    'has_history' => count($rentals) > 0,
    'filter_all' => $filter === 'all',
    'filter_sewa' => $filter === 'sedang_disewa',
    'filter_selesai' => $filter === 'selesai',
    'filter_konfirmasi' => $filter === 'menunggu_konfirmasi',
    'filter_batal' => $filter === 'dibatalkan',
    'user_name' => $_SESSION['nama_lengkap'] ?? '',
    'user_initials' => user_initials(),
];

show_page('history', $data, 'Riwayat Sewa | CampRent', '', '');
?>