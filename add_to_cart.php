<?php
// add_to_cart.php - receives the "Add to Cart" button click (a POST) and saves it.
// There are two kinds of visitors:
//   - JavaScript on: the page asks in the background ("ajax") and we answer with JSON
//   - JavaScript off: the browser sends a normal form and we send the visitor back
require_once __DIR__ . '/includes/helpers.php';

// Did the request come from our JavaScript? (listing.js adds this header)
$is_ajax = false;
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    $is_ajax = true;
}

// Answers the visitor and stops. $ok = true/false, $status = HTTP code (200 ok, 404 not found...)
function reply($ok, $message, $status, $is_ajax)
{
    if ($is_ajax) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => $ok, 'message' => $message, 'cartCount' => cart_count()]);
        exit;
    }

    // No JavaScript: save the message, then go back to the page the visitor was on
    if ($ok) {
        flash_set($message, 'ok');
    } else {
        flash_set($message, 'error');
    }
    $back = 'product.php';
    if (isset($_POST['return'])) {
        $return = $_POST['return'];
        // Only allow our own pages, so nobody can use this to send visitors to another website
        if (substr($return, 0, 11) == 'product.php' || substr($return, 0, 9) == 'index.php') {
            $back = $return;
        }
    }
    header('Location: ' . $back);
    exit;
}

// Only accept a POST (a button click), not someone typing the address in the browser
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    reply(false, 'Method not allowed.', 405, $is_ajax);
}

// Check the secret token from the form
$token = '';
if (isset($_POST['csrf'])) {
    $token = $_POST['csrf'];
}
if (!csrf_valid($token)) {
    reply(false, 'Session expired. Please reload the page.', 403, $is_ajax);
}

$id_product = 0;
if (isset($_POST['id_product'])) {
    $id_product = (int) $_POST['id_product'];
}
$id_user = current_user_id();

// 1. Does the product exist?
$product = db_query('SELECT nama_produk FROM products WHERE id_product = ?', [$id_product])->fetch();
if (!$product) {
    reply(false, 'Product not found.', 404, $is_ajax);
}

// 2. Find a colour version that is in stock. (The colour picker was removed, so we
//    take the first one that still has stock; the cart table needs one.)
$variant = db_query(
    'SELECT id_variant, stok_tersedia FROM product_variants
     WHERE id_product = ? AND stok_tersedia > 0 ORDER BY id_variant LIMIT 1',
    [$id_product]
)->fetch();
if (!$variant) {
    reply(false, $product['nama_produk'] . ' is out of stock.', 409, $is_ajax);
}

// 3. Find this user's cart, or create one
$id_cart = db_query('SELECT id_cart FROM carts WHERE id_user = ?', [$id_user])->fetchColumn();
if (!$id_cart) {
    db_query('INSERT INTO carts (id_user) VALUES (?)', [$id_user]);
    $id_cart = $pdo->lastInsertId();       // the number the database just gave the new cart
}

// 4. Is this product already in the cart?
$item = db_query(
    'SELECT id_cart_item, qty FROM cart_items WHERE id_cart = ? AND id_product = ? AND id_variant = ?',
    [$id_cart, $id_product, $variant['id_variant']]
)->fetch();

if ($item) {
    // Yes: add one more, but never more than the stock
    if ($item['qty'] + 1 > $variant['stok_tersedia']) {
        reply(false, 'Only ' . $variant['stok_tersedia'] . ' available for ' . $product['nama_produk'] . '.', 409, $is_ajax);
    }
    db_query('UPDATE cart_items SET qty = qty + 1 WHERE id_cart_item = ?', [$item['id_cart_item']]);
} else {
    // No: add a new line with quantity 1
    db_query(
        'INSERT INTO cart_items (id_cart, id_product, id_variant, qty) VALUES (?, ?, ?, 1)',
        [$id_cart, $id_product, $variant['id_variant']]
    );
}

reply(true, $product['nama_produk'] . ' added to cart.', 200, $is_ajax);
