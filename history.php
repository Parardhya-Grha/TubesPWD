<?php
require_once __DIR__ . '/includes/view.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['id_user'];

$rentals = db_query(
    'SELECT *
     FROM rentals
     WHERE id_user = ?
     ORDER BY created_at DESC',
    [$user_id]
)->fetchAll();

$history_data = [];

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
        <div class="ph-product">
            <img src="' . e(img_path($item['gambar_utama'])) . '" alt="' . e($item['nama_produk']) . '" />
            <div class="ph-product-info">
                <strong>' . e($item['nama_produk']) . '</strong>
                <p>Warna: ' . e($item['nama_warna']) . '</p>
                <p>Jumlah: ' . e($item['qty']) . '</p>
                <p>Harga per hari: ' . e(rupiah($item['harga_per_hari'])) . '</p>
                <p>Subtotal: ' . e(rupiah($item['subtotal_item'])) . '</p>
            </div>
        </div>';
    }

    $history_data[] = [
        'kode_transaksi' => $rental['kode_transaksi'],
        'tanggal_mulai' => indo_date($rental['tanggal_mulai_sewa']),
        'tanggal_selesai' => indo_date($rental['tanggal_selesai_sewa']),
        'durasi' => $rental['durasi_hari'],
        'alamat' => $rental['alamat_pengiriman'],
        'metode' => $rental['metode_pembayaran'],
        'status_pembayaran' => str_replace('_', ' ', $rental['status_pembayaran']),
        'status_sewa' => str_replace('_', ' ', $rental['status_sewa']),
        'subtotal' => rupiah($rental['subtotal_sewa']),
        'pengiriman' => rupiah($rental['biaya_pengiriman']),
        'layanan' => rupiah($rental['biaya_layanan']),
        'total' => rupiah($rental['total_bayar']),
        'catatan' => $rental['catatan_pesanan'] ?? '',
        'items_html' => $items_html,
    ];
}

$data = [
    'history' => $history_data,
    'has_history' => count($history_data) > 0,
];

show_page('history', $data, 'Riwayat Penyewaan | CampRent', '', '');
?>