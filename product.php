<?php
// product.php - the ALL PRODUCTS page.
// Step 1: read what the visitor chose.  Step 2: ask the database.
// Step 3: show the page (header + products.xhtml + footer).
require_once __DIR__ . '/includes/helpers.php';

// ----- Step 1: filters from the address bar -----
$f = read_filters();
$page = current_page();

// ----- Step 2: data from the database -----
$total = count_products($f);                          // how many products match
$total_pages = ceil($total / PER_PAGE);               // 30 products / 12 per page = 3 pages
if ($total_pages < 1) {
    $total_pages = 1;
}
if ($page > $total_pages) {
    $page = $total_pages;                             // e.g. ?page=99 -> show the last page
}

$products = [];
if ($total > 0) {
    $skip = ($page - 1) * PER_PAGE;                   // products on earlier pages
    $order = $SORTS[$f['sort']][1];                   // ORDER BY text for the chosen sort
    $products = get_products($f, $order, PER_PAGE, $skip);
}

// "Showing 13-24 of 30 products"
$from = 0;
if ($total > 0) {
    $from = ($page - 1) * PER_PAGE + 1;
}
$to = min($total, $page * PER_PAGE);

// Lists for the dropdowns: each one is  value => text
$categories = get_categories();
$category_options = [];
foreach ($categories as $c) {
    $category_options[$c['slug_kategori']] = $c['nama_kategori'];
}
$price_options = [];
foreach ($PRICE_RANGES as $key => $range) {
    $price_options[$key] = $range[0];                 // [0] is the label
}
$brand_options = [];
foreach (get_brands() as $brand) {
    $brand_options[$brand] = $brand;
}
$size_options = [];
foreach (get_sizes() as $size) {
    $size_options[$size] = $size;
}
$sort_options = [];
foreach ($SORTS as $key => $sort) {
    $sort_options[$key] = $sort[0];
}

// ----- Step 3: show the page -----
$page_title = 'All Products – CampRent';
$active_menu = '/products';
$search_text = $f['q'];

include __DIR__ . '/templates/header.xhtml';
include __DIR__ . '/templates/products.xhtml';
include __DIR__ . '/templates/footer.xhtml';
