<?php
// helpers.php - small functions that the pages share.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'cookie_path'     => '/',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/* ---------- Fixed lists used by the filters ---------- */

// 'value in the URL' => ['label shown to the visitor', 'SQL used for ORDER BY']
$SORTS = [
    'newest'     => ['Newest',        'p.created_at DESC, p.id_product DESC'],
    'popular'    => ['Most Popular',  'p.total_disewa DESC, p.id_product ASC'],
    'price_asc'  => ['Lowest Price',  'p.harga_per_hari ASC, p.id_product ASC'],
    'price_desc' => ['Highest Price', 'p.harga_per_hari DESC, p.id_product ASC'],
];

// 'value in the URL' => ['label', lowest price, highest price]  (0 = no limit)
$PRICE_RANGES = [
    '0-15000'     => ['Up to Rp 15.000',     0,     15000],
    '15001-30000' => ['Rp 15.000 – 30.000',  15001, 30000],
    '30001-45000' => ['Rp 30.000 – 45.000',  30001, 45000],
    '45001-'      => ['Above Rp 45.000',     45001, 0],
];

$AVAILABILITY = [
    'in_stock'  => 'In stock',
    'low_stock' => 'Low stock (5 or fewer)',
];

// SQL piece that adds up the stock of all colours of one product
$STOCK_SQL = '(SELECT COALESCE(SUM(v.stok_tersedia), 0) FROM product_variants v WHERE v.id_product = p.id_product)';

/* ---------- Small text helpers ---------- */

// e() = "escape". Always wrap database/user text in it before printing to HTML.
// It turns < > & " into safe codes so nobody can inject a script into your page.
function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// The database stores "assets/products/x.jpg"; your project folder is called Images.
function img_path($db_path)
{
    if (substr($db_path, 0, 7) == 'assets/') {
        return 'Images/' . substr($db_path, 7);
    }
    return $db_path;
}

// 35000 -> "Rp 35.000"
function rupiah($amount)
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

// "2026-11-18 10:00:00" -> "18 November 2026"
function indo_date($datetime)
{
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
               'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $time = strtotime($datetime);
    if ($time === false) {
        return '';
    }
    $day = date('j', $time);
    $month_number = date('n', $time);      // 1 to 12
    $year = date('Y', $time);
    return $day . ' ' . $months[$month_number - 1] . ' ' . $year;
}

// The menu table stores slugs like "/products". Turn them into a real page.
function menu_url($slug)
{
    if ($slug == '/products') {
        return 'product.php';
    }
    return '#';     // pages that do not exist yet
}

// The page the visitor is on now, e.g. "product.php?cat=tent" (used to come back after Add to Cart)
function current_url()
{
    $url = basename($_SERVER['SCRIPT_NAME']);
    if ($_SERVER['QUERY_STRING'] != '') {
        $url = $url . '?' . $_SERVER['QUERY_STRING'];
    }
    return $url;
}

/* ---------- Reading the filters from the address bar ---------- */

// Reads one value from the URL (?cat=tent -> "tent"). Returns '' if missing.
function get_text($name)
{
    if (!isset($_GET[$name]) || !is_string($_GET[$name])) {
        return '';
    }
    return mb_substr(trim($_GET[$name]), 0, 60);   // trim spaces, keep max 60 characters
}

function current_page()
{
    $page = (int) get_text('page');      // (int) turns text into a whole number
    if ($page < 1) {
        $page = 1;
    }
    return $page;
}

// Collects all filters in one array. Wrong values are replaced by safe defaults.
function read_filters()
{
    global $SORTS, $PRICE_RANGES, $AVAILABILITY;

    $sort = get_text('sort');
    if (!isset($SORTS[$sort])) {
        $sort = 'newest';
    }
    $price = get_text('price');
    if (!isset($PRICE_RANGES[$price])) {
        $price = '';
    }
    $avail = get_text('avail');
    if (!isset($AVAILABILITY[$avail])) {
        $avail = '';
    }

    $filters = [];
    $filters['q']       = get_text('q');       // search words
    $filters['cat']     = get_text('cat');     // category slug
    $filters['brand']   = get_text('brand');
    $filters['size']    = get_text('size');
    $filters['price']   = $price;
    $filters['avail']   = $avail;
    $filters['sort']    = $sort;
    $filters['popular'] = false;               // only the homepage sets this to true
    return $filters;
}

// true when the visitor chose at least one filter (used to show "Clear all")
function has_filters($f)
{
    if ($f['q'] != '' || $f['cat'] != '' || $f['brand'] != '') {
        return true;
    }
    if ($f['size'] != '' || $f['price'] != '' || $f['avail'] != '') {
        return true;
    }
    return false;
}

/* ---------- Getting products from the database ---------- */

// Builds the WHERE part of the SQL from the filters.
// Returns two things: ['sql' => 'WHERE ...', 'values' => [the values for the ? marks]]
function build_where($f)
{
    global $PRICE_RANGES, $STOCK_SQL;

    $conditions = [];   // pieces like "p.brand = ?"
    $values = [];       // the values that go in place of the ? marks

    if ($f['q'] != '') {
        $conditions[] = '(p.nama_produk LIKE ? OR p.brand LIKE ? OR p.deskripsi LIKE ?)';
        $like = '%' . $f['q'] . '%';
        $values[] = $like;
        $values[] = $like;
        $values[] = $like;
    }
    if ($f['cat'] != '') {
        $conditions[] = 'c.slug_kategori = ?';
        $values[] = $f['cat'];
    }
    if ($f['brand'] != '') {
        $conditions[] = 'p.brand = ?';
        $values[] = $f['brand'];
    }
    if ($f['size'] != '') {
        $conditions[] = 'p.kapasitas_ukuran = ?';
        $values[] = $f['size'];
    }
    if ($f['price'] != '') {
        $range = $PRICE_RANGES[$f['price']];
        $conditions[] = 'p.harga_per_hari >= ?';
        $values[] = $range[1];                 // lowest price
        if ($range[2] > 0) {                   // 0 means "no highest price"
            $conditions[] = 'p.harga_per_hari <= ?';
            $values[] = $range[2];
        }
    }
    if ($f['avail'] == 'in_stock') {
        $conditions[] = $STOCK_SQL . ' > 0';
    }
    if ($f['avail'] == 'low_stock') {
        $conditions[] = $STOCK_SQL . ' BETWEEN 1 AND 5';
    }
    if ($f['popular']) {
        $conditions[] = 'p.is_popular = 1';
    }

    $sql = '';
    if (count($conditions) > 0) {
        $sql = 'WHERE ' . implode(' AND ', $conditions);   // join the pieces with AND
    }
    return ['sql' => $sql, 'values' => $values];
}

// How many products match the filters
function count_products($f)
{
    $where = build_where($f);
    $sql = 'SELECT COUNT(*) FROM products p
            JOIN categories c ON c.id_category = p.id_category ' . $where['sql'];
    return (int) db_query($sql, $where['values'])->fetchColumn();
}

// Gets one page of products.
//   $order  = ORDER BY text taken from $SORTS (never typed by the visitor)
//   $limit  = how many products, $offset = how many to skip first
function get_products($f, $order, $limit, $offset = 0)
{
    global $STOCK_SQL;

    $where = build_where($f);
    // (int) makes sure these are numbers, so it is safe to put them straight in the SQL text
    $limit = (int) $limit;
    $offset = (int) $offset;

    $sql = 'SELECT p.*, c.nama_kategori, c.slug_kategori, ' . $STOCK_SQL . ' AS stok_total
            FROM products p
            JOIN categories c ON c.id_category = p.id_category ' . $where['sql'] . '
            ORDER BY ' . $order . '
            LIMIT ' . $limit . ' OFFSET ' . $offset;
    return db_query($sql, $where['values'])->fetchAll();
}

function get_categories()
{
    return db_query('SELECT slug_kategori, nama_kategori, icon_url FROM categories ORDER BY id_category')->fetchAll();
}

function get_brands()
{
    return db_query('SELECT DISTINCT brand FROM products ORDER BY brand')->fetchAll(PDO::FETCH_COLUMN);
}

function get_sizes()
{
    return db_query('SELECT DISTINCT kapasitas_ukuran FROM products WHERE kapasitas_ukuran IS NOT NULL ORDER BY kapasitas_ukuran')->fetchAll(PDO::FETCH_COLUMN);
}

function get_menus()
{
    return db_query('SELECT nama_menu, url_slug FROM site_menus WHERE is_active = 1 ORDER BY urutan')->fetchAll();
}

// Builds a link to product.php that keeps the current filters.
// $changes lets you replace some, e.g. listing_url($f, ['page' => 2])
function listing_url($f, $changes = [])
{
    $values = $f;                               // start from the current filters
    foreach ($changes as $key => $value) {
        $values[$key] = $value;                 // put the changes on top
    }

    $keep = [];
    $keys = ['q', 'cat', 'brand', 'size', 'price', 'avail', 'sort', 'page'];
    foreach ($keys as $key) {
        if (!isset($values[$key]) || $values[$key] === '') {
            continue;                           // skip empty values: keeps the URL short
        }
        if ($key == 'sort' && $values[$key] == 'newest') {
            continue;                           // "newest" is the default, no need to show it
        }
        if ($key == 'page' && $values[$key] <= 1) {
            continue;                           // page 1 is the default too
        }
        $keep[$key] = $values[$key];
    }

    if (count($keep) == 0) {
        return 'product.php';
    }
    return 'product.php?' . http_build_query($keep);   // {cat: tent} -> "cat=tent"
}

/* ---------- User, cart, security token, messages ---------- */

// Who is shopping? No login page yet, so we use the demo user from config.php
function current_user_id()
{
    if (isset($_SESSION['id_user'])) {
        return (int) $_SESSION['id_user'];
    }
    return DEMO_USER_ID;
}

function is_logged_in()
{
    return isset($_SESSION['id_user']) && (int) $_SESSION['id_user'] > 0;
}

function user_initials()
{
    if (!is_logged_in()) {
        return '';
    }
    $name = trim((string) ($_SESSION['nama_lengkap'] ?? ''));
    if ($name === '') {
        $name = trim((string) ($_SESSION['username'] ?? 'U'));
    }
    $parts = preg_split('/\s+/', $name);
    if (count($parts) >= 2) {
        $initials = mb_substr($parts[0], 0, 1, 'UTF-8') . mb_substr($parts[1], 0, 1, 'UTF-8');
    } else {
        $initials = mb_substr($parts[0], 0, 2, 'UTF-8');
    }
    return mb_strtoupper($initials, 'UTF-8');
}

// Total number of items in the cart (shown in the little yellow badge)
function cart_count()
{
    $sql = 'SELECT COALESCE(SUM(ci.qty), 0)
            FROM carts c JOIN cart_items ci ON ci.id_cart = c.id_cart
            WHERE c.id_user = ?';
    return (int) db_query($sql, [current_user_id()])->fetchColumn();
}

// A secret random text put in every Add to Cart form. It proves the click came
// from our own page and not from another website ("CSRF protection").
function csrf_token()
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_valid($token)
{
    // hash_equals compares two texts safely. A normal == would also work here.
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// "Flash" message: saved on one page, shown once on the next page, then forgotten.
function flash_set($message, $type = 'ok')
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function flash_pull()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);      // remove it so it only shows once
    return $flash;
}

/* ---------- Print small pieces of the page ---------- */
// These functions just load a small template file. The variables used by the
// file (like $p) are the function's own variables, so the file can see them.

function print_card($p, $show_detail)
{
    include __DIR__ . '/../templates/partials/card.xhtml';
}

function print_select($name, $label, $options, $selected, $show_blank)
{
    include __DIR__ . '/../templates/partials/select.xhtml';
}

function print_carousel($title, $class_name, $products)
{
    include __DIR__ . '/../templates/partials/carousel.xhtml';
}

function print_review($r){
    include __DIR__ . '/../templates/partials/review.xhtml';
}