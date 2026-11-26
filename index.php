<?php
/** Homepage – categories, popular/new products and reviews come from db_camprent. Markup: templates/pages/home.xhtml */
require_once __DIR__ . '/includes/view.php';

$noFilters  = read_filters();                       // empty filters, default sort
$categories = filter_options()['categories'];

$popularFilter = $noFilters;
$popularFilter['popular'] = true;
$popular = query_products($popularFilter, SORTS['popular'][1], 8);
$newest  = query_products($noFilters, SORTS['newest'][1], 8);

$reviewRows = db_query(
    'SELECT r.rating, r.komentar, r.created_at, u.nama_lengkap, u.role
     FROM reviews r
     JOIN users u ON u.id_user = r.id_user
     JOIN products p ON p.id_product = r.id_product
     WHERE r.is_featured = 1
     ORDER BY r.created_at DESC, r.id_review DESC
     LIMIT 10'
)->fetchAll();

$reviews = [];
foreach ($reviewRows as $r) {
    $reviews[] = [
        'initial' => mb_strtoupper(mb_substr($r['nama_lengkap'], 0, 1)),
        'name'    => $r['nama_lengkap'],
        'product' => ucfirst(strtolower((string) $r['role'])),
        'date'    => indo_date($r['created_at']),
        'rating'  => max(0, min(5, (int) $r['rating'])),
        'comment' => $r['komentar'],
    ];
}

render_page('home', [
    'categories' => array_map(static fn(array $c): array => [
        'url'  => 'product.php?cat=' . urlencode($c['slug_kategori']),
        'name' => $c['nama_kategori'],
        'icon' => img_path($c['icon_url']),
    ], $categories),
    'popular' => ['key' => 'popular', 'title' => 'PRODUK POPULER', 'products' => array_map('card_data', $popular)],
    'newest'  => ['key' => 'newest', 'title' => 'PRODUK TERBARU', 'products' => array_map('card_data', $newest)],
    'reviews' => $reviews,
], ['title' => 'CampRent']);
