<?php
/**
 * Tiny template engine: keeps ALL markup in templates/*.xhtml and all logic in PHP.
 *
 * Template syntax (everything else is copied through untouched):
 *   {{name}}                 value, HTML-escaped        (dots allowed: {{asset.search}})
 *   {{{name}}}               value as-is (only for HTML that was rendered by a template)
 *   {{#if name}}..{{else}}..{{/if}}       shown when the value is not empty / false / 0
 *   {{#each list}}..{{else}}..{{/each}}   repeats for every item; {{else}} = list is empty
 *   {{#with name}}..{{/with}}             uses an array as the current scope
 *   {{> partial}}            inserts templates/partials/partial.xhtml (same scope)
 *
 * Inside {{#each}} / {{#with}} a name is looked up in the item first, then in the
 * enclosing scopes, so page-wide values (asset, csrf, ...) are always reachable.
 */
require_once __DIR__ . '/helpers.php';

const TEMPLATE_DIR = __DIR__ . '/../templates';

final class View
{
    /** @var array<string, array> parsed templates */
    private static array $cache = [];

    public static function render(string $template, array $data = []): string
    {
        return self::run(self::load($template), [$data]);
    }

    private static function load(string $name): array
    {
        if (!isset(self::$cache[$name])) {
            if (!preg_match('#^[A-Za-z0-9_\-/]+$#', $name) || str_contains($name, '..')) {
                throw new InvalidArgumentException('Bad template name: ' . $name);
            }
            $source = @file_get_contents(TEMPLATE_DIR . '/' . $name . '.xhtml');
            if ($source === false) {
                throw new RuntimeException('Template not found: ' . $name);
            }
            self::$cache[$name] = self::parse($source);
        }
        return self::$cache[$name];
    }

    /** Turn template text into a tree of nodes: ['t',text] ['v',name,raw] ['p',name] [kind,name,then,else]. */
    private static function parse(string $source): array
    {
        $tokens = preg_split(
            '/(\{\{\{\s*[\w.]+\s*\}\}\}|\{\{[#\/>]?[\w.\- \/]*\}\})/',
            $source,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $stack = [['kind' => 'root', 'name' => '', 'then' => [], 'else' => null]];
        $append = static function (array $node) use (&$stack): void {
            $k = count($stack) - 1;
            if ($stack[$k]['else'] !== null) {
                $stack[$k]['else'][] = $node;
            } else {
                $stack[$k]['then'][] = $node;
            }
        };

        foreach ($tokens as $i => $token) {
            if ($i % 2 === 0) {                                  // plain text
                if ($token !== '') {
                    $append(['t', $token]);
                }
                continue;
            }
            if (str_starts_with($token, '{{{')) {                // {{{raw}}}
                $append(['v', trim(substr($token, 3, -3)), true]);
                continue;
            }
            $inner = trim(substr($token, 2, -2));
            $first = $inner[0] ?? '';

            if ($inner === 'else') {
                $k = count($stack) - 1;
                if ($stack[$k]['kind'] === 'root') {
                    throw new RuntimeException('{{else}} outside a block');
                }
                $stack[$k]['else'] = [];
            } elseif ($first === '#') {                          // open block
                $parts = preg_split('/\s+/', trim(substr($inner, 1)), 2);
                $stack[] = ['kind' => $parts[0], 'name' => $parts[1] ?? '', 'then' => [], 'else' => null];
            } elseif ($first === '/') {                          // close block
                $frame = array_pop($stack);
                if ($frame['kind'] === 'root' || $frame['kind'] !== trim(substr($inner, 1))) {
                    throw new RuntimeException('Unbalanced {{/' . trim(substr($inner, 1)) . '}}');
                }
                $append([$frame['kind'], $frame['name'], $frame['then'], $frame['else'] ?? []]);
            } elseif ($first === '>') {                          // partial
                $append(['p', trim(substr($inner, 1))]);
            } else {                                             // {{escaped}}
                $append(['v', $inner, false]);
            }
        }

        if (count($stack) !== 1) {
            throw new RuntimeException('Unclosed {{#' . $stack[count($stack) - 1]['kind'] . '}} block');
        }
        return $stack[0]['then'];
    }

    private static function run(array $nodes, array $scopes): string
    {
        $out = '';
        foreach ($nodes as $node) {
            switch ($node[0]) {
                case 't':
                    $out .= $node[1];
                    break;
                case 'v':
                    $value = self::lookup($node[1], $scopes);
                    $text  = is_scalar($value) ? (string) $value : '';
                    $out  .= $node[2] ? $text : e($text);
                    break;
                case 'p':
                    $out .= self::run(self::load('partials/' . $node[1]), $scopes);
                    break;
                case 'if':
                    $out .= self::run(self::lookup($node[1], $scopes) ? $node[2] : $node[3], $scopes);
                    break;
                case 'with':
                    $ctx = self::lookup($node[1], $scopes);
                    $out .= is_array($ctx) && $ctx
                        ? self::run($node[2], array_merge($scopes, [$ctx]))
                        : self::run($node[3], $scopes);
                    break;
                case 'each':
                    $list = self::lookup($node[1], $scopes);
                    if (is_array($list) && $list) {
                        foreach ($list as $item) {
                            $scope = is_array($item) ? $item : ['this' => $item];
                            $out .= self::run($node[2], array_merge($scopes, [$scope]));
                        }
                    } else {
                        $out .= self::run($node[3], $scopes);
                    }
                    break;
            }
        }
        return $out;
    }

    /** Find "a.b.c": the first part is searched from the innermost scope outwards. */
    private static function lookup(string $path, array $scopes)
    {
        $parts = explode('.', $path);
        $head  = array_shift($parts);

        for ($i = count($scopes) - 1; $i >= 0; $i--) {
            if (is_array($scopes[$i]) && array_key_exists($head, $scopes[$i])) {
                $value = $scopes[$i][$head];
                foreach ($parts as $part) {
                    if (!is_array($value) || !array_key_exists($part, $value)) {
                        return null;
                    }
                    $value = $value[$part];
                }
                return $value;
            }
        }
        return null;
    }
}

/* ------------------------------------------------------------------ *
 * Helpers used by the page controllers (product.php, index.php)
 * ------------------------------------------------------------------ */

/** Where add_to_cart.php should send the visitor back to (current page + query). */
function current_return_to(): string
{
    $query = $_SERVER['QUERY_STRING'] ?? '';
    return basename($_SERVER['SCRIPT_NAME']) . ($query !== '' ? '?' . $query : '');
}

/** Turn a products row into the values card.xhtml needs. */
function card_data(array $p, bool $showDetail = false): array
{
    $badge = $p['badge'] ?? 'None';
    return [
        'id_product'  => (int) $p['id_product'],
        'image'       => img_path($p['gambar_utama']),
        'name'        => $p['nama_produk'],
        'brand_line'  => $p['brand'] . ' - ' . $p['nama_kategori'],
        'detail'      => (string) ($p['kapasitas_ukuran'] ?? ''),
        'show_detail' => $showDetail,
        'price'       => rupiah($p['harga_per_hari']),
        'badge'       => $badge === 'None' ? '' : $badge,
        'badge_class' => 'card__badge--' . strtolower(str_replace(' ', '-', $badge)),
        'in_stock'    => (int) ($p['stok_total'] ?? 1) > 0,
    ];
}

/**
 * Render a page: templates/pages/<page>.xhtml goes into templates/layout.xhtml
 * (header, navigation, footer, toast, scripts).
 *
 * $meta: 'title', 'active' (site_menus.url_slug to highlight), 'search' (search box text)
 */
function render_page(string $page, array $data, array $meta = []): void
{
    $shared = [
        'asset'     => ASSET,
        'csrf'      => csrf_token(),
        'return_to' => current_return_to(),
    ];

    $content = View::render('pages/' . $page, $data + $shared);

    $menu = [];
    foreach (site_menus() as $m) {
        $menu[] = [
            'url'    => menu_url($m['url_slug']),
            'label'  => $m['nama_menu'],
            'active' => $m['url_slug'] === ($meta['active'] ?? ''),
        ];
    }

    $cart  = cart_count();
    $flash = flash_pull();

    $layout = $shared + [
        'title'         => $meta['title'] ?? 'CampRent',
        'menu'          => $menu,
        'search'        => $meta['search'] ?? '',
        'cart_count'    => $cart,
        'cart_empty'    => $cart <= 0,
        'toast_visible' => $flash !== null,
        'toast_error'   => $flash !== null && $flash['type'] === 'error',
        'toast_text'    => $flash['message'] ?? '',
        'content'       => $content,
    ];

    header('Content-Type: text/html; charset=UTF-8');
    echo View::render('layout', $layout);
}
