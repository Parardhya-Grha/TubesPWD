<?php
// view.php - fills the XHTML templates with data.
//
// The XHTML files in /templates have no PHP in them. They only have small tags:
//
//   {{name}}                          show a value (made safe with e())
//   {{{name}}}                        show finished XHTML as it is (only for page content)
//   {{#if name}} ... {{else}} ... {{/if}}   show a part only when the value is not empty
//   {{#each list}} ... {{/each}}      repeat a part for every item of a list
//   {{#with name}} ... {{/with}}      use the values inside "name" for this part
//   {{> card}}                        insert the file templates/partials/card.xhtml
//
// Two small rules: write one {{#if}} inside another {{#if}} never (same for each/with),
// and keep the page's real work (loops, decisions, formatting) in PHP.
require_once __DIR__ . '/helpers.php';

define('TEMPLATE_DIR', __DIR__ . '/../templates');

/* ---------- The small template "engine" ---------- */

// Opens templates/<name>.xhtml and fills it with $data (an array of values).
function render($name, $data = [])
{
    $file = TEMPLATE_DIR . '/' . $name . '.xhtml';
    if (!is_file($file)) {
        exit('Template not found: ' . $name);
    }
    return fill(file_get_contents($file), $data);
}

// Looks a value up by name. "asset.search" means $data['asset']['search'].
function lookup($name, $data)
{
    $value = $data;
    foreach (explode('.', $name) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return null;                    // not found
        }
        $value = $value[$part];
    }
    return $value;
}

// Finds the first block like {{#each list}} ... {{/each}} in $text.
// Returns where it is, its name and what is inside, or null when there is none.
function find_block($text, $kind)
{
    $pattern = '/\{\{#' . $kind . ' ([\w.]+)\}\}(.*?)\{\{\/' . $kind . '\}\}/s';
    if (!preg_match($pattern, $text, $found, PREG_OFFSET_CAPTURE)) {
        return null;
    }
    return [
        'start'  => $found[0][1],
        'length' => strlen($found[0][0]),
        'name'   => $found[1][0],
        'inside' => $found[2][0],
    ];
}

// Does the real work: handles with, each, if, partials, then the values.
function fill($text, $data)
{
    // {{#with name}} ... {{/with}}
    while ($block = find_block($text, 'with')) {
        $inside = lookup($block['name'], $data);
        $result = '';
        if (is_array($inside)) {
            $result = fill($block['inside'], array_merge($data, $inside));
        }
        $text = substr_replace($text, $result, $block['start'], $block['length']);
    }

    // {{#each list}} ... {{/each}}  (once for every item in the list)
    while ($block = find_block($text, 'each')) {
        $list = lookup($block['name'], $data);
        $result = '';
        if (is_array($list)) {
            foreach ($list as $item) {
                $result .= fill($block['inside'], array_merge($data, $item));
            }
        }
        $text = substr_replace($text, $result, $block['start'], $block['length']);
    }

    // {{#if name}} yes {{else}} no {{/if}}
    while ($block = find_block($text, 'if')) {
        $parts = explode('{{else}}', $block['inside'], 2);    // [yes part, no part]
        $chosen = $parts[0];
        if (empty(lookup($block['name'], $data))) {           // empty = '', 0, false, null, []
            $chosen = '';
            if (isset($parts[1])) {
                $chosen = $parts[1];
            }
        }
        $text = substr_replace($text, fill($chosen, $data), $block['start'], $block['length']);
    }

    // {{> partial}}
    while (preg_match('/\{\{> ?([\w\-]+)\}\}/', $text, $found, PREG_OFFSET_CAPTURE)) {
        $part_file = TEMPLATE_DIR . '/partials/' . $found[1][0] . '.xhtml';
        $part = '';
        if (is_file($part_file)) {
            $part = fill(file_get_contents($part_file), $data);
        }
        $text = substr_replace($text, $part, $found[0][1], strlen($found[0][0]));
    }

    // {{{name}}} (as it is) and then {{name}} (made safe)
    while (preg_match('/\{\{\{([\w.]+)\}\}\}/', $text, $found, PREG_OFFSET_CAPTURE)) {
        $value = lookup($found[1][0], $data);
        if (!is_string($value)) {
            $value = '';
        }
        $text = substr_replace($text, $value, $found[0][1], strlen($found[0][0]));
    }
    while (preg_match('/\{\{([\w.]+)\}\}/', $text, $found, PREG_OFFSET_CAPTURE)) {
        $value = lookup($found[1][0], $data);
        if (!is_scalar($value)) {
            $value = '';
        }
        // Replace "{" in the value too, so visitor text can never become a template tag
        $safe = str_replace('{', '&#123;', e($value));
        $text = substr_replace($text, $safe, $found[0][1], strlen($found[0][0]));
    }

    return $text;
}

/* ---------- Turning database rows into ready-to-show values ---------- */

// Everything one product card needs, already prepared.
function card_data($p, $show_detail)
{
    $badge = '';                                  // empty = no badge
    if ($p['badge'] != 'None') {
        $badge = $p['badge'];
    }

    return [
        'id_product'  => (int) $p['id_product'],
        'image'       => img_path($p['gambar_utama']),
        'name'        => $p['nama_produk'],
        'brand_line'  => $p['brand'] . ' - ' . $p['nama_kategori'],
        'detail'      => (string) $p['kapasitas_ukuran'],
        'show_detail' => $show_detail,
        'price'       => rupiah($p['harga_per_hari']),
        'badge'       => $badge,
        'badge_class' => 'card__badge--' . strtolower(str_replace(' ', '-', $p['badge'])),
        'in_stock'    => ($p['stok_total'] > 0),
    ];
}

function cards_data($products, $show_detail)
{
    $cards = [];
    foreach ($products as $p) {
        $cards[] = card_data($p, $show_detail);
    }
    return $cards;
}

// A titled row of cards (homepage). $class_name is 'popular' or 'newest'.
function carousel_data($title, $class_name, $products)
{
    return ['title' => $title, 'class_name' => $class_name, 'products' => cards_data($products, false)];
}

function review_data($r)
{
    $rating = (int) $r['rating'];
    if ($rating < 0) { $rating = 0; }             // keep the number between 0 and 5
    if ($rating > 5) { $rating = 5; }

    return [
        'initial' => mb_strtoupper(mb_substr($r['nama_lengkap'], 0, 1)),   // first letter of the name
        'name'    => $r['nama_lengkap'],
        'product' => $r['nama_produk'],
        'date'    => indo_date($r['created_at']),
        'rating'  => $rating,
        'comment' => $r['komentar'],
    ];
}

function reviews_data($reviews)
{
    $list = [];
    foreach ($reviews as $r) {
        $list[] = review_data($r);
    }
    return $list;
}

// One dropdown. $options = value => text.  $show_blank = first empty choice showing the label.
function select_data($name, $label, $options, $selected, $show_blank)
{
    $list = [];
    foreach ($options as $value => $text) {
        $list[] = [
            'value'    => (string) $value,
            'label'    => (string) $text,
            'selected' => ((string) $value == $selected),
        ];
    }
    return ['name' => $name, 'label' => $label, 'placeholder' => $show_blank, 'options' => $list];
}

// The category buttons above the filters
function quick_cats_data($categories, $f)
{
    $list = [];
    foreach ($categories as $c) {
        $is_active = ($f['cat'] == $c['slug_kategori']);
        $new_cat = $c['slug_kategori'];
        if ($is_active) {
            $new_cat = '';                        // clicking the active one clears it
        }
        $list[] = [
            'url'    => listing_url($f, ['cat' => $new_cat]),
            'name'   => $c['nama_kategori'],
            'icon'   => img_path($c['icon_url']),
            'active' => $is_active,
        ];
    }
    return $list;
}

// Previous arrow, up to 5 page numbers, next arrow
function page_links_data($f, $page, $total_pages)
{
    $first_number = max(1, $page - 2);                      // 2 pages before the current one
    $last_number = min($total_pages, $first_number + 4);    // ... up to 5 numbers in total
    $first_number = max(1, $last_number - 4);

    $links = [];

    if ($page > 1) {
        $links[] = ['is_link' => true, 'url' => listing_url($f, ['page' => $page - 1]),
                    'title_attr' => ' title="Previous"', 'css_class' => '', 'label' => '‹'];
    } else {
        $links[] = ['is_link' => false, 'css_class' => 'page-link is-disabled', 'label' => '‹'];
    }

    for ($i = $first_number; $i <= $last_number; $i++) {
        if ($i == $page) {
            $links[] = ['is_link' => false, 'css_class' => 'page-link is-current', 'label' => $i];
        } else {
            $links[] = ['is_link' => true, 'url' => listing_url($f, ['page' => $i]),
                        'title_attr' => '', 'css_class' => '', 'label' => $i];
        }
    }

    if ($page < $total_pages) {
        $links[] = ['is_link' => true, 'url' => listing_url($f, ['page' => $page + 1]),
                    'title_attr' => ' title="Next"', 'css_class' => '', 'label' => '›'];
    } else {
        $links[] = ['is_link' => false, 'css_class' => 'page-link is-disabled', 'label' => '›'];
    }
    return $links;
}

/* ---------- Whole page ---------- */

// Fills templates/pages/<page>.xhtml, puts it inside templates/layout.xhtml and prints it.
//   $page = 'home' or 'products'     $data = the values for that page
//   $title = text in the browser tab $active_menu = menu item to highlight (e.g. '/products')
//   $search_text = what to show in the search box
function show_page($page, $data, $title, $active_menu, $search_text)
{
    // Values every template can use: the image paths, the security token, the return page
    $data['asset'] = ASSET;
    $data['csrf'] = csrf_token();
    $data['return_to'] = current_url();

    $content = render('pages/' . $page, $data);

    // The top menu
    $menu = [];
    foreach (get_menus() as $m) {
        $menu[] = [
            'url'    => menu_url($m['url_slug']),
            'label'  => $m['nama_menu'],
            'active' => ($m['url_slug'] == $active_menu),
        ];
    }

    // Message left by Add to Cart (if any) for the little toast box
    $flash = flash_pull();
    $toast_visible = false;
    $toast_error = false;
    $toast_text = '';
    if ($flash) {
        $toast_visible = true;
        $toast_error = ($flash['type'] == 'error');
        $toast_text = $flash['message'];
    }

    $cart_total = cart_count();

    $profile           = is_logged_in() ? current_user_profile() : null;
    $user_name         = $profile ? (string) $profile['nama_lengkap'] : (string) ($_SESSION['nama_lengkap'] ?? '');
    $user_avatar       = $profile ? avatar_path($profile['foto_profil'] ?? '') : '';
    $show_nav_avatar   = (is_logged_in() && $user_avatar !== '');
    $show_nav_initials = (is_logged_in() && $user_avatar === '');

    header('Content-Type: text/html; charset=UTF-8');
    echo render('layout', [
        'asset'         => ASSET,
        'title'         => $title,
        'menu'          => $menu,
        'search_text'   => $search_text,
        'cart_count'    => $cart_total,
        'cart_empty'    => ($cart_total <= 0),
        'is_logged_in'      => is_logged_in(),
        'show_nav_avatar'   => $show_nav_avatar,
        'show_nav_initials' => $show_nav_initials,
        'is_guest'          => !is_logged_in(),
        'user_initials'     => user_initials(),
        'user_avatar'       => $user_avatar,
        'user_name'         => $user_name,
        'toast_visible' => $toast_visible,
        'toast_error'   => $toast_error,
        'toast_text'    => $toast_text,
        'content'       => $content,
    ]);
}
