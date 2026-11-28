<?php
// cart.php - displays the shopping cart (empty state, guest or member cart, and QRIS popup)
require_once __DIR__ . '/includes/view.php';

$isLoggedIn = is_logged_in();
$userId     = current_user_id();

$defaultStart = date('Y-m-d', strtotime('+1 day'));
$defaultEnd   = date('Y-m-d', strtotime('+3 days'));

$customerName  = '';
$customerPhone = '';
$deliveryAddr  = '';
$pickupType    = 'Delivery';
$orderNotes    = '';
$savedStart    = $defaultStart;
$savedDays     = 2;

if (!isset($_SESSION['cart_item_dates']) || !is_array($_SESSION['cart_item_dates'])) {
    $_SESSION['cart_item_dates'] = [];
}

$rawItems = [];

if ($isLoggedIn) {
    $userRow = db_query(
        'SELECT nama_lengkap, no_hp, alamat_default FROM users WHERE id_user = ?',
        [$userId]
    )->fetch();

    if ($userRow) {
        $customerName  = (string) ($userRow['nama_lengkap'] ?? '');
        $customerPhone = (string) ($userRow['no_hp'] ?? '');
        $deliveryAddr  = (string) ($userRow['alamat_default'] ?? '');
    }

    $cartRow = db_query(
        'SELECT id_cart, tanggal_mulai_sewa, durasi_hari, alamat_pengiriman, catatan_pesanan
         FROM carts
         WHERE id_user = ?',
        [$userId]
    )->fetch();

    if ($cartRow) {
        if (!empty($cartRow['tanggal_mulai_sewa'])) {
            $savedStart = $cartRow['tanggal_mulai_sewa'];
        }
        if ((int) $cartRow['durasi_hari'] > 0) {
            $savedDays = (int) $cartRow['durasi_hari'];
        }
        $defaultStart = $savedStart;
        $defaultEnd   = date('Y-m-d', strtotime($savedStart . ' +' . $savedDays . ' days'));

        if (!empty($cartRow['alamat_pengiriman'])) {
            $deliveryAddr = (string) $cartRow['alamat_pengiriman'];
        }
        if (!empty($cartRow['catatan_pesanan'])) {
            $orderNotes = (string) $cartRow['catatan_pesanan'];
        }

        $rawItems = db_query(
            'SELECT ci.id_cart_item AS item_key, ci.id_product, ci.id_variant, ci.qty,
                    p.nama_produk, p.kapasitas_ukuran, p.harga_per_hari, p.gambar_utama,
                    c.nama_kategori, v.stok_tersedia
             FROM cart_items ci
             JOIN products p ON p.id_product = ci.id_product
             JOIN categories c ON c.id_category = p.id_category
             JOIN product_variants v ON v.id_variant = ci.id_variant
             WHERE ci.id_cart = ?
             ORDER BY ci.id_cart_item ASC',
            [$cartRow['id_cart']]
        )->fetchAll();
    }
} else {
    $guestCart = get_guest_cart();
    foreach ($guestCart as $key => $entry) {
        $row = db_query(
            'SELECT p.id_product, p.nama_produk, p.kapasitas_ukuran, p.harga_per_hari, p.gambar_utama,
                    c.nama_kategori, v.id_variant, v.stok_tersedia
             FROM products p
             JOIN categories c ON c.id_category = p.id_category
             JOIN product_variants v ON v.id_variant = ? AND v.id_product = p.id_product
             WHERE p.id_product = ?',
            [(int) $entry['id_variant'], (int) $entry['id_product']]
        )->fetch();

        if ($row) {
            $row['item_key'] = (string) $key;
            $row['qty']      = (int) $entry['qty'];
            $rawItems[]      = $row;
        }
    }
}

$items          = [];
$rentalSubtotal = 0;

foreach ($rawItems as $row) {
    $key      = (string) $row['item_key'];
    $dateInfo = $_SESSION['cart_item_dates'][$key] ?? null;

    $startDate = ($dateInfo && !empty($dateInfo['start_date'])) ? $dateInfo['start_date'] : $defaultStart;
    $endDate   = ($dateInfo && !empty($dateInfo['end_date'])) ? $dateInfo['end_date'] : $defaultEnd;

    $startTs = strtotime($startDate);
    $endTs   = strtotime($endDate);
    $days    = ($startTs && $endTs && $endTs > $startTs)
        ? (int) round(($endTs - $startTs) / 86400)
        : 1;

    $pricePerDay  = (int) round((float) $row['harga_per_hari']);
    $qty          = max(1, (int) $row['qty']);
    $maxStock     = max(1, (int) $row['stok_tersedia']);
    $itemSubtotal = $pricePerDay * $days * $qty;
    $rentalSubtotal += $itemSubtotal;

    $subtitle = $row['nama_kategori'];
    if (!empty($row['kapasitas_ukuran'])) {
        $subtitle .= ' · ' . $row['kapasitas_ukuran'];
    }

    $items[] = [
        'item_key'          => $key,
        'name'              => $row['nama_produk'],
        'subtitle'          => $subtitle,
        'image'             => img_path($row['gambar_utama']),
        'start_date'        => $startDate,
        'end_date'          => $endDate,
        'date_range_label'  => date('d M', strtotime($startDate)) . ' - ' . date('d M', strtotime($endDate)),
        'days'              => $days,
        'days_label'        => $days . ($days === 1 ? ' day' : ' days'),
        'price_raw'         => $pricePerDay,
        'price_formatted'   => rupiah($pricePerDay),
        'qty'               => $qty,
        'max_stock'         => $maxStock,
        'subtotal_formatted'=> rupiah($itemSubtotal),
    ];
}

$isEmpty         = (count($items) === 0);
$hasItems        = !$isEmpty;
$isGuest         = !$isLoggedIn;
$showGuestBanner = ($isGuest && $hasItems);

$deliveryFee = ($pickupType === 'Delivery') ? 25000 : 0;
$taxAmount   = (int) round(($rentalSubtotal + $deliveryFee) * 0.11);
$grandTotal  = $rentalSubtotal + $deliveryFee + $taxAmount;

$guestCheckoutNote = '';
if ($isGuest) {
    $guestCheckoutNote = '<p class="cart-summary__guest-note">Checkout and Save Draft require an account. <a href="login.php?redirect=cart.php">Sign in</a> to continue.</p>';
}

$pickupOptions = '<option value="Delivery"' . ($pickupType === 'Delivery' ? ' selected="selected"' : '') . '>Delivery</option>'
               . '<option value="Self Pickup"' . ($pickupType === 'Self Pickup' ? ' selected="selected"' : '') . '>Self Pickup</option>';

show_page('cart', [
    'is_empty'               => $isEmpty,
    'has_items'              => $hasItems,
    'is_guest'               => $isGuest,
    'show_guest_banner'      => $showGuestBanner,
    'disabled_attr'          => $isGuest ? ' disabled="disabled"' : '',
    'guest_checkout_note'    => $guestCheckoutNote,
    'customer_name'          => $customerName,
    'customer_phone'         => $customerPhone,
    'delivery_address'       => $deliveryAddr,
    'pickup_options'         => $pickupOptions,
    'order_notes'            => $orderNotes,
    'items'                  => $items,
    'rental_subtotal_fmt'    => rupiah($rentalSubtotal),
    'delivery_fee_fmt'       => rupiah($deliveryFee),
    'tax_fmt'                => rupiah($taxAmount),
    'grand_total_fmt'        => rupiah($grandTotal),
    'grand_total_raw'        => $grandTotal,
], 'Keranjang – CampRent', '', '');