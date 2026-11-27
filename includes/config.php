<?php
/**
 * CampRent – configuration.
 * Change the database settings to match your server (XAMPP defaults shown).
 */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'db_camprent');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * There is no login page yet, so Add to Cart and the cart badge use this
 * customer (user #2 "Ardhya" already owns the seeded cart).
 * When a login page exists, it only has to set $_SESSION['id_user'].
 */
define('DEMO_USER_ID', 2);
/** Secret salt kept only in PHP (never stored in the database). */
define('AUTH_SALT', 'CampRent#2026!SecretSalt');

/** Products per page on product.php (4 columns x 3 rows, like the design). */
define('PER_PAGE', 12);

/**
 * Icons and fixed images used by the shared header/footer/homepage.
 * If your files in Images/ are named differently, change them here only.
 */
const ASSET = [
    'search'     => 'Images/search.svg',
    'bell'       => 'Images/bell.svg',
    'cart'       => 'Images/shopping-cart.svg',
    'user'       => 'Images/user.svg',
    'cart_small' => 'Images/shopping-cart 2.svg',
    'star'       => 'Images/star.svg',
    'sliders'    => 'Images/sliders.svg',
    'arrow'      => 'Images/arrow_back_ios.svg',
    'arrow_back' => 'Images/arrow-back.svg',
    'hero'       => 'Images/camp.jpg',
    'hero_dots'  => 'assets/hero-dots.svg',
    'qris'       => 'Images/qris-qweh.jpeg',
    'instagram'  => 'Images/Instagram--Streamline-Atlas 1.svg',
    'whatsapp'   => 'Images/Whatsapp--Streamline-Atlas 1.svg',
    'tiktok'     => 'Images/Tiktok--Streamline-Atlas 1.svg',
    'facebook'   => 'Images/Facebook--Streamline-Atlas 1.svg',
];
