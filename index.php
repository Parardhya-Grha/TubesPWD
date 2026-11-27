<?php
// index.php - the homepage. Categories, products and reviews come from the database.
require_once __DIR__ . '/includes/view.php';

$f = read_filters();                       // no filters chosen = everything

$categories = get_categories();

// Popular products: same query, but only products marked is_popular = 1
$f['popular'] = true;
$popular = get_products($f, $SORTS['popular'][1], 8);

// Newest products: no popular filter
$f['popular'] = false;
$newest = get_products($f, $SORTS['newest'][1], 8);

// Reviews that the owner marked as featured (is_featured = 1)
$sql = 'SELECT r.rating, r.komentar, r.created_at, u.nama_lengkap, p.nama_produk
        FROM reviews r
        JOIN users u ON u.id_user = r.id_user
        JOIN products p ON p.id_product = r.id_product
        WHERE r.is_featured = 1
        ORDER BY r.created_at DESC, r.id_review DESC
        LIMIT 10';
$reviews = db_query($sql)->fetchAll();

// One link per category (the small picture buttons under the hero)
$category_links = [];
foreach ($categories as $c) {
    $category_links[] = [
        'url'  => 'product.php?cat=' . urlencode($c['slug_kategori']),
        'icon' => img_path($c['icon_url']),
        'name' => $c['nama_kategori'],
    ];
}

// Fill the page template and show it
show_page('home', [
    'categories' => $category_links,
    'popular'    => carousel_data('PRODUK POPULER', 'popular', $popular),
    'newest'     => carousel_data('PRODUK TERBARU', 'newest', $newest),
    'reviews'    => reviews_data($reviews),
], 'CampRent', '', '');
