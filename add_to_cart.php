<?php
/**
 * POST add_to_cart.php   (fields: id_product, csrf, return)
 *
 * Adds one unit of a product to the current user's cart. Because the colour
 * selector was removed from the design, the first variant that still has
 * stock is used (cart_items.id_variant is required by the database).
 *
 * Answers JSON for the JavaScript version (X-Requested-With header) and
 * redirects back with a message for plain form posts.
 */
require_once __DIR__ . '/includes/helpers.php';

$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

function finish(bool $ok, string $message, int $status, bool $isAjax): void
{
    if ($isAjax) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => $ok, 'message' => $message, 'cartCount' => cart_count()]);
        exit;
    }

    flash_set($message, $ok ? 'ok' : 'error');
    // Only allow a plain "page.php?query" target, never another site.
    $return = (string) ($_POST['return'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_\-]+\.php(\?[A-Za-z0-9_\-=&%.+\[\]]*)?$/', $return)) {
        $return = 'product.php';
    }
    header('Location: ' . $return);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    finish(false, 'Method not allowed.', 405, $isAjax);
}
if (!csrf_valid((string) ($_POST['csrf'] ?? ''))) {
    finish(false, 'Session expired. Please reload the page.', 403, $isAjax);
}

$idProduct = (int) ($_POST['id_product'] ?? 0);
$idUser    = current_user_id();
$pdo       = db();

try {
    $pdo->beginTransaction();

    $product = db_query('SELECT nama_produk FROM products WHERE id_product = ?', [$idProduct])->fetch();
    if (!$product) {
        $pdo->rollBack();
        finish(false, 'Product not found.', 404, $isAjax);
    }

    $variant = db_query(
        'SELECT id_variant, stok_tersedia FROM product_variants
         WHERE id_product = ? AND stok_tersedia > 0 ORDER BY id_variant LIMIT 1',
        [$idProduct]
    )->fetch();
    if (!$variant) {
        $pdo->rollBack();
        finish(false, $product['nama_produk'] . ' is out of stock.', 409, $isAjax);
    }

    $idCart = db_query('SELECT id_cart FROM carts WHERE id_user = ?', [$idUser])->fetchColumn();
    if (!$idCart) {
        db_query('INSERT INTO carts (id_user) VALUES (?)', [$idUser]);
        $idCart = (int) $pdo->lastInsertId();
    }

    $item = db_query(
        'SELECT id_cart_item, qty FROM cart_items WHERE id_cart = ? AND id_product = ? AND id_variant = ?',
        [(int) $idCart, $idProduct, (int) $variant['id_variant']]
    )->fetch();

    if ($item) {
        if ((int) $item['qty'] + 1 > (int) $variant['stok_tersedia']) {
            $pdo->rollBack();
            finish(false, 'Only ' . (int) $variant['stok_tersedia'] . ' available for ' . $product['nama_produk'] . '.', 409, $isAjax);
        }
        db_query('UPDATE cart_items SET qty = qty + 1 WHERE id_cart_item = ?', [(int) $item['id_cart_item']]);
    } else {
        db_query(
            'INSERT INTO cart_items (id_cart, id_product, id_variant, qty) VALUES (?, ?, ?, 1)',
            [(int) $idCart, $idProduct, (int) $variant['id_variant']]
        );
    }

    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('add_to_cart failed: ' . $ex->getMessage());
    finish(false, 'Could not add to cart. Please try again.', 500, $isAjax);
}

finish(true, $product['nama_produk'] . ' added to cart.', 200, $isAjax);
