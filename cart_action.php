<?php
// cart_action.php - handles cart quantity updates, item removal, dates, and QRIS checkout
require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/json; charset=UTF-8');

function json_reply($ok, $message, $extra = [], $status = 200)
{
    http_response_code($status);
    echo json_encode(array_merge([
        'ok'        => $ok,
        'message'   => $message,
        'cartCount' => cart_count(),
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_reply(false, 'Method not allowed.', [], 405);
}

$token = (string) ($_POST['csrf'] ?? '');
if (!csrf_valid($token)) {
    json_reply(false, 'Sesi berakhir. Silakan muat ulang halaman.', [], 403);
}

$action     = (string) ($_POST['action'] ?? '');
$isLoggedIn = is_logged_in();
$userId     = current_user_id();

if (!isset($_SESSION['cart_item_dates']) || !is_array($_SESSION['cart_item_dates'])) {
    $_SESSION['cart_item_dates'] = [];
}

// 1. Update item quantity
if ($action === 'update_qty') {
    $itemKey = (string) ($_POST['item_key'] ?? '');
    $qty     = max(1, (int) ($_POST['qty'] ?? 1));

    if ($isLoggedIn) {
        $row = db_query(
            'SELECT ci.id_cart_item, v.stok_tersedia
             FROM cart_items ci
             JOIN carts c ON c.id_cart = ci.id_cart
             JOIN product_variants v ON v.id_variant = ci.id_variant
             WHERE ci.id_cart_item = ? AND c.id_user = ?',
            [(int) $itemKey, $userId]
        )->fetch();

        if (!$row) {
            json_reply(false, 'Item tidak ditemukan.', [], 404);
        }

        $maxStock = max(1, (int) $row['stok_tersedia']);
        if ($qty > $maxStock) {
            $qty = $maxStock;
        }

        db_query('UPDATE cart_items SET qty = ? WHERE id_cart_item = ?', [$qty, $row['id_cart_item']]);
        json_reply(true, 'Jumlah diperbarui.', ['qty' => $qty]);
    } else {
        $guestCart = get_guest_cart();
        if (!isset($guestCart[$itemKey])) {
            json_reply(false, 'Item tidak ditemukan.', [], 404);
        }

        $variant = db_query(
            'SELECT stok_tersedia FROM product_variants WHERE id_variant = ?',
            [(int) $guestCart[$itemKey]['id_variant']]
        )->fetch();

        $maxStock = $variant ? max(1, (int) $variant['stok_tersedia']) : 1;
        if ($qty > $maxStock) {
            $qty = $maxStock;
        }

        $guestCart[$itemKey]['qty'] = $qty;
        save_guest_cart($guestCart);
        json_reply(true, 'Jumlah diperbarui.', ['qty' => $qty]);
    }
}

// 2. Remove an item from cart
if ($action === 'remove_item') {
    $itemKey = (string) ($_POST['item_key'] ?? '');

    if ($isLoggedIn) {
        db_query(
            'DELETE ci FROM cart_items ci
             JOIN carts c ON c.id_cart = ci.id_cart
             WHERE ci.id_cart_item = ? AND c.id_user = ?',
            [(int) $itemKey, $userId]
        );
    } else {
        $guestCart = get_guest_cart();
        unset($guestCart[$itemKey]);
        save_guest_cart($guestCart);
    }

    unset($_SESSION['cart_item_dates'][$itemKey]);
    json_reply(true, 'Item dihapus dari keranjang.');
}

// 3. Persist per-item or apply-all rental dates in session
if ($action === 'update_dates') {
    $rawDates = (string) ($_POST['dates_json'] ?? '');
    $decoded  = json_decode($rawDates, true);
    if (is_array($decoded)) {
        foreach ($decoded as $key => $pair) {
            if (!empty($pair['start_date']) && !empty($pair['end_date'])) {
                $_SESSION['cart_item_dates'][(string) $key] = [
                    'start_date' => (string) $pair['start_date'],
                    'end_date'   => (string) $pair['end_date'],
                ];
            }
        }
    }
    json_reply(true, 'Tanggal sewa diperbarui.');
}

// 4. Confirm QRIS Payment & Create Rental Order (requires authenticated account)
if ($action === 'checkout_qris') {
    if (!$isLoggedIn) {
        json_reply(false, 'Checkout memerlukan akun. Silakan login terlebih dahulu.', [], 401);
    }

    $namaPenerima = trim((string) ($_POST['nama_penerima'] ?? ''));
    $noWa         = trim((string) ($_POST['no_wa'] ?? ''));
    $pickupType   = (string) ($_POST['pickup_type'] ?? 'Delivery');
    $alamat       = trim((string) ($_POST['alamat_pengiriman'] ?? ''));
    $catatan      = trim((string) ($_POST['catatan_pesanan'] ?? ''));
    $datesMap     = json_decode((string) ($_POST['dates_json'] ?? '{}'), true);
    if (!is_array($datesMap)) {
        $datesMap = [];
    }

    if ($namaPenerima === '' || $noWa === '') {
        json_reply(false, 'Nama dan Nomor WhatsApp wajib diisi.', [], 422);
    }
    if ($pickupType === 'Delivery' && $alamat === '') {
        json_reply(false, 'Alamat pengiriman wajib diisi untuk metode Delivery.', [], 422);
    }

    $cartRow = db_query('SELECT id_cart FROM carts WHERE id_user = ?', [$userId])->fetch();
    if (!$cartRow) {
        json_reply(false, 'Keranjang kamu kosong.', [], 400);
    }

    $cartItems = db_query(
        'SELECT ci.id_cart_item, ci.id_product, ci.id_variant, ci.qty,
                p.nama_produk, p.harga_per_hari, v.stok_tersedia
         FROM cart_items ci
         JOIN products p ON p.id_product = ci.id_product
         JOIN product_variants v ON v.id_variant = ci.id_variant
         WHERE ci.id_cart = ?',
        [$cartRow['id_cart']]
    )->fetchAll();

    if (count($cartItems) === 0) {
        json_reply(false, 'Keranjang kamu kosong.', [], 400);
    }

    $subtotalSewa = 0;
    $earliestTs   = null;
    $latestTs     = null;
    $maxDays      = 1;
    $preparedRows = [];

    foreach ($cartItems as $ci) {
        $key      = (string) $ci['id_cart_item'];
        $qty      = (int) $ci['qty'];
        $stock    = (int) $ci['stok_tersedia'];

        if ($qty > $stock) {
            json_reply(false, 'Stok untuk ' . $ci['nama_produk'] . ' tidak mencukupi (tersisa ' . $stock . ').', [], 409);
        }

        $pair      = $datesMap[$key] ?? ($_SESSION['cart_item_dates'][$key] ?? []);
        $startDate = !empty($pair['start_date']) ? $pair['start_date'] : date('Y-m-d', strtotime('+1 day'));
        $endDate   = !empty($pair['end_date']) ? $pair['end_date'] : date('Y-m-d', strtotime('+3 days'));

        $sTs  = strtotime($startDate);
        $eTs  = strtotime($endDate);
        $days = ($sTs && $eTs && $eTs > $sTs) ? (int) round(($eTs - $sTs) / 86400) : 1;

        if ($earliestTs === null || $sTs < $earliestTs) {
            $earliestTs = $sTs;
        }
        if ($latestTs === null || $eTs > $latestTs) {
            $latestTs = $eTs;
        }
        if ($days > $maxDays) {
            $maxDays = $days;
        }

        $price        = (float) $ci['harga_per_hari'];
        $itemSubtotal = $price * $days * $qty;
        $subtotalSewa += $itemSubtotal;

        $preparedRows[] = [
            'id_product'    => (int) $ci['id_product'],
            'id_variant'    => (int) $ci['id_variant'],
            'qty'           => $qty,
            'harga_per_hari'=> $price,
            'subtotal_item' => $itemSubtotal,
        ];
    }

    $biayaPengiriman = ($pickupType === 'Delivery') ? 25000.00 : 0.00;
    $biayaLayanan    = round(($subtotalSewa + $biayaPengiriman) * 0.11);
    $totalBayar      = $subtotalSewa + $biayaPengiriman + $biayaLayanan;

    $finalAddress = ($pickupType === 'Delivery')
        ? $alamat
        : 'Self Pickup at CampRent Basecamp Yogyakarta';

    $fullNotes = 'Penerima: ' . $namaPenerima . ' (' . $noWa . ') | Tipe: ' . $pickupType;
    if ($catatan !== '') {
        $fullNotes .= ' | Catatan: ' . $catatan;
    }

    $kodeTransaksi = 'CR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

    global $pdo;
    try {
        $pdo->beginTransaction();

        db_query(
            'INSERT INTO rentals
              (kode_transaksi, id_user, tanggal_mulai_sewa, tanggal_selesai_sewa, durasi_hari,
               alamat_pengiriman, catatan_pesanan, subtotal_sewa, biaya_pengiriman, biaya_layanan,
               total_bayar, metode_pembayaran, status_pembayaran, status_sewa)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $kodeTransaksi,
                $userId,
                date('Y-m-d', $earliestTs),
                date('Y-m-d', $latestTs),
                $maxDays,
                $finalAddress,
                $fullNotes,
                $subtotalSewa,
                $biayaPengiriman,
                $biayaLayanan,
                $totalBayar,
                'QRIS',
                'lunas',
                'menunggu_konfirmasi',
            ]
        );

        $idRental = (int) $pdo->lastInsertId();

        foreach ($preparedRows as $pr) {
            db_query(
                'INSERT INTO rental_items (id_rental, id_product, id_variant, qty, harga_per_hari, subtotal_item)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$idRental, $pr['id_product'], $pr['id_variant'], $pr['qty'], $pr['harga_per_hari'], $pr['subtotal_item']]
            );

            db_query(
                'UPDATE product_variants SET stok_tersedia = stok_tersedia - ? WHERE id_variant = ?',
                [$pr['qty'], $pr['id_variant']]
            );

            db_query(
                'UPDATE products SET total_disewa = total_disewa + ? WHERE id_product = ?',
                [$pr['qty'], $pr['id_product']]
            );
        }

        db_query('DELETE FROM cart_items WHERE id_cart = ?', [$cartRow['id_cart']]);
        unset($_SESSION['cart_item_dates'], $_SESSION['cart_draft']);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_reply(false, 'Gagal memproses pesanan QRIS. Silakan coba lagi.', [], 500);
    }

    json_reply(true, 'Pembayaran QRIS berhasil dikonfirmasi!', [
        'kode_transaksi' => $kodeTransaksi,
        'total_bayar'    => rupiah($totalBayar),
    ]);
}

json_reply(false, 'Aksi tidak dikenal.', [], 400);