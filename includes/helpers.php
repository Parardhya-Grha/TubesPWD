<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ------------------------------------------------------------------ *
 * Listing options (labels shown in the dropdowns)
 * ------------------------------------------------------------------ */
const SORTS = [
    'newest'     => ['Newest',        'p.created_at DESC, p.id_product DESC'],
    'popular'    => ['Most Popular',  'p.total_disewa DESC, p.id_product ASC'],
    'price_asc'  => ['Lowest Price',  'p.harga_per_hari ASC, p.id_product ASC'],
    'price_desc' => ['Highest Price', 'p.harga_per_hari DESC, p.id_product ASC'],
];

/** key => [label, min, max]  (null = no limit) */
const PRICE_RANGES = [
    '0-15000'     => ['Up to Rp 15.000',      0,     15000],
    '15001-30000' => ['Rp 15.000 – 30.000',   15001, 30000],
    '30001-45000' => ['Rp 30.000 – 45.000',   30001, 45000],
    '45001-'      => ['Above Rp 45.000',      45001, null],
];

const AVAILABILITY = [
    'in_stock'  => 'In stock',
    'low_stock' => 'Low stock (5 or fewer)',
];

/** Total stock of all variants of a product (variants are not selectable in the UI). */
const STOCK_EXPR = '(SELECT COALESCE(SUM(v.stok_tersedia), 0) FROM product_variants v WHERE v.id_product = p.id_product)';

/* ------------------------------------------------------------------ *
 * Small output helpers
 * ------------------------------------------------------------------ */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** The database stores "assets/..." paths; this project keeps them in "Images/...". */
function img_path(string $dbPath): string
{
    return preg_replace('#^assets/#', 'Images/', $dbPath);
}

function rupiah($amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function indo_date(string $datetime): string
{
    static $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                      'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

/** site_menus.url_slug -> real page. Pages that do not exist yet stay "#". */
function menu_url(string $slug): string
{
    static $map = ['/products' => 'product.php'];
    return $map[$slug] ?? '#';
}

/* ------------------------------------------------------------------ *
 * Filters, products
 * ------------------------------------------------------------------ */
function get_str(string $key, int $max = 60): string
{
    $v = $_GET[$key] ?? '';
    return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
}

function current_page(): int
{
    $p = $_GET['page'] ?? 1;
    return is_scalar($p) ? max(1, (int) $p) : 1;
}

/** Read and whitelist every filter from the query string. */
function read_filters(): array
{
    $sort = get_str('sort');
    if (!isset(SORTS[$sort])) {
        $sort = 'newest';
    }
    $price = get_str('price');
    if (!isset(PRICE_RANGES[$price])) {
        $price = '';
    }
    $avail = get_str('avail');
    if (!isset(AVAILABILITY[$avail])) {
        $avail = '';
    }

    return [
        'q'       => get_str('q'),
        'cat'     => get_str('cat'),
        'brand'   => get_str('brand'),
        'size'    => get_str('size'),
        'price'   => $price,
        'avail'   => $avail,
        'sort'    => $sort,
        'popular' => false,
    ];
}

function has_filters(array $f): bool
{
    return $f['q'] !== '' || $f['cat'] !== '' || $f['brand'] !== '' || $f['size'] !== ''
        || $f['price'] !== '' || $f['avail'] !== '';
}

/** Build the WHERE clause; values are always bound, never concatenated. */
function build_where(array $f, array &$params): string
{
    $where = [];

    if ($f['q'] !== '') {
        $where[] = '(p.nama_produk LIKE ? OR p.brand LIKE ? OR p.deskripsi LIKE ?)';
        $like = '%' . $f['q'] . '%';
        array_push($params, $like, $like, $like);
    }
    if ($f['cat'] !== '') {
        $where[] = 'c.slug_kategori = ?';
        $params[] = $f['cat'];
    }
    if ($f['brand'] !== '') {
        $where[] = 'p.brand = ?';
        $params[] = $f['brand'];
    }
    if ($f['size'] !== '') {
        $where[] = 'p.kapasitas_ukuran = ?';
        $params[] = $f['size'];
    }
    if ($f['price'] !== '') {
        [, $min, $max] = PRICE_RANGES[$f['price']];
        $where[] = 'p.harga_per_hari >= ?';
        $params[] = $min;
        if ($max !== null) {
            $where[] = 'p.harga_per_hari <= ?';
            $params[] = $max;
        }
    }
    if ($f['avail'] === 'in_stock') {
        $where[] = STOCK_EXPR . ' > 0';
    } elseif ($f['avail'] === 'low_stock') {
        $where[] = STOCK_EXPR . ' BETWEEN 1 AND 5';
    }
    if (!empty($f['popular'])) {
        $where[] = 'p.is_popular = 1';
    }

    return $where ? 'WHERE ' . implode(' AND ', $where) : '';
}

function count_products(array $f): int
{
    $params = [];
    $where = build_where($f, $params);
    $sql = "SELECT COUNT(*) FROM products p JOIN categories c ON c.id_category = p.id_category $where";
    return (int) db_query($sql, $params)->fetchColumn();
}

/** $order must come from SORTS (never from user input directly). */
function query_products(array $f, string $order, int $limit, int $offset = 0): array
{
    $params = [];
    $where = build_where($f, $params);
    $sql = 'SELECT p.*, c.nama_kategori, c.slug_kategori, ' . STOCK_EXPR . " AS stok_total
            FROM products p
            JOIN categories c ON c.id_category = p.id_category
            $where
            ORDER BY $order
            LIMIT ? OFFSET ?";
    array_push($params, $limit, $offset);
    return db_query($sql, $params)->fetchAll();
}

function filter_options(): array
{
    return [
        'categories' => db_query('SELECT slug_kategori, nama_kategori, icon_url FROM categories ORDER BY id_category')->fetchAll(),
        'brands'     => db_query('SELECT DISTINCT brand FROM products ORDER BY brand')->fetchAll(PDO::FETCH_COLUMN),
        'sizes'      => db_query('SELECT DISTINCT kapasitas_ukuran FROM products WHERE kapasitas_ukuran IS NOT NULL ORDER BY kapasitas_ukuran')->fetchAll(PDO::FETCH_COLUMN),
    ];
}

/** URL of product.php that keeps the current filters, with some values overridden. */
function listing_url(array $f, array $override = []): string
{
    $q = array_merge([
        'q'     => $f['q'],
        'cat'   => $f['cat'],
        'brand' => $f['brand'],
        'size'  => $f['size'],
        'price' => $f['price'],
        'avail' => $f['avail'],
        'sort'  => $f['sort'],
    ], $override);

    if (($q['sort'] ?? '') === 'newest') {
        unset($q['sort']);
    }
    if (isset($q['page']) && (int) $q['page'] <= 1) {
        unset($q['page']);
    }
    $q = array_filter($q, static fn($v) => $v !== '' && $v !== null);

    return 'product.php' . ($q ? '?' . http_build_query($q) : '');
}

function site_menus(): array
{
    return db_query('SELECT nama_menu, url_slug FROM site_menus WHERE is_active = 1 ORDER BY urutan')->fetchAll();
}

/* ------------------------------------------------------------------ *
 * User, cart, CSRF, flash messages
 * ------------------------------------------------------------------ */
function current_user_id(): int
{
    return (int) ($_SESSION['id_user'] ?? DEMO_USER_ID);
}

function cart_count(): int
{
    $sql = 'SELECT COALESCE(SUM(ci.qty), 0)
            FROM carts c JOIN cart_items ci ON ci.id_cart = c.id_cart
            WHERE c.id_user = ?';
    return (int) db_query($sql, [current_user_id()])->fetchColumn();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(string $token): bool
{
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function flash_set(string $message, string $type = 'ok'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function flash_pull(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
