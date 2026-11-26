<?php
// index.php - the homepage. Categories, products and reviews come from the database.
require_once __DIR__ . '/includes/helpers.php';

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

// Show the page
$page_title = 'CampRent';
$active_menu = '';
$search_text = '';

include __DIR__ . '/templates/header.xhtml';
include __DIR__ . '/templates/home.xhtml';
include __DIR__ . '/templates/footer.xhtml';
