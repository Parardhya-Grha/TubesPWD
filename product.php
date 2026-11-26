<?php
/** ALL PRODUCTS – listing page (Figma node 220:294), driven by db_camprent. Markup: templates/pages/products.xhtml */
require_once __DIR__ . '/includes/view.php';

$f     = read_filters();
$total = count_products($f);
$pages = max(1, (int) ceil($total / PER_PAGE));
$page  = min(current_page(), $pages);
$items = $total > 0 ? query_products($f, SORTS[$f['sort']][1], PER_PAGE, ($page - 1) * PER_PAGE) : [];
$opts  = filter_options();

/** Data for templates/partials/select.xhtml. $options = value => label. */
function select_data(string $name, string $label, array $options, string $selected, bool $placeholder = true): array
{
    $list = [];
    foreach ($options as $value => $text) {
        $list[] = ['value' => (string) $value, 'label' => (string) $text, 'selected' => (string) $value === $selected];
    }
    return ['name' => $name, 'label' => $label, 'placeholder' => $placeholder, 'options' => $list];
}

$categoryOptions = [];
$quickCats = [];
foreach ($opts['categories'] as $c) {
    $categoryOptions[$c['slug_kategori']] = $c['nama_kategori'];
    $active = ($f['cat'] === $c['slug_kategori']);
    $quickCats[] = [
        'url'    => listing_url($f, ['cat' => $active ? '' : $c['slug_kategori']]),
        'name'   => $c['nama_kategori'],
        'icon'   => img_path($c['icon_url']),
        'active' => $active,
    ];
}
$priceOptions = [];
foreach (PRICE_RANGES as $key => $range) {
    $priceOptions[$key] = $range[0];
}
$sortOptions = [];
foreach (SORTS as $key => $sort) {
    $sortOptions[$key] = $sort[0];
}

// Pagination: previous, up to 5 page numbers, next
$start = max(1, $page - 2);
$end   = min($pages, $start + 4);
$start = max(1, $end - 4);
$links = [];
$links[] = $page > 1
    ? ['label' => "\u{2039}", 'url' => listing_url($f, ['page' => $page - 1]), 'title' => 'Previous', 'is_link' => true]
    : ['label' => "\u{2039}", 'is_disabled' => true];
for ($i = $start; $i <= $end; $i++) {
    $links[] = $i === $page
        ? ['label' => (string) $i, 'is_current' => true]
        : ['label' => (string) $i, 'url' => listing_url($f, ['page' => $i]), 'is_link' => true];
}
$links[] = $page < $pages
    ? ['label' => "\u{203A}", 'url' => listing_url($f, ['page' => $page + 1]), 'title' => 'Next', 'is_link' => true]
    : ['label' => "\u{203A}", 'is_disabled' => true];

render_page('products', [
    'total'       => $total,
    'plural'      => $total !== 1,
    'q'           => $f['q'],
    'quick_cats'  => $quickCats,
    'filters'     => [
        select_data('cat', 'Category', $categoryOptions, $f['cat']),
        select_data('price', 'Price', $priceOptions, $f['price']),
        select_data('brand', 'Brand', array_combine($opts['brands'], $opts['brands']) ?: [], $f['brand']),
        select_data('size', 'Size/Capacity', array_combine($opts['sizes'], $opts['sizes']) ?: [], $f['size']),
        select_data('avail', 'Availability', AVAILABILITY, $f['avail']),
    ],
    'has_filters' => has_filters($f),
    'sort'        => select_data('sort', 'Newest', $sortOptions, $f['sort'], false),
    'products'    => array_map(static fn(array $p): array => card_data($p, true), $items),
    'from'        => $total > 0 ? ($page - 1) * PER_PAGE + 1 : 0,
    'to'          => min($total, $page * PER_PAGE),
    'page_links'  => $links,
], [
    'title'  => 'All Products – CampRent',
    'active' => '/products',
    'search' => $f['q'],
]);
