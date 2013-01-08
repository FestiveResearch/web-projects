<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache

/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'db99602_swig');

/** MySQL database username */
define('DB_USER', '1clk_wp_rlWRVx2');

/** MySQL database password */
define('DB_PASSWORD', 'QidWJT2l');

/** MySQL hostname */
define('DB_HOST', $_ENV{DATABASE_SERVER});

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'iKQt1aIO DNn8LIky EzFYObYK aaSnLEZz sGgiAdkH');
define('SECURE_AUTH_KEY', 'MwpqQeVk JQ3gmmHj 3srZhpXh fwNKsbMA bcSU5vEE');
define('LOGGED_IN_KEY', 'yC12SCyW JVwu4uwH 3ZNiIJij r3Q52tQM aRmzdv21');
define('NONCE_KEY', 'VUnP3AJv 6NualCd8 E6gxplLi Oartjw3f npTo61iY');
define('AUTH_SALT', 'hrC6gZou 2aBptZCs VWzTE6Ft 2eQmZSAZ KHG7zHJL');
define('SECURE_AUTH_SALT', 'wuiWMcro jPCVHyt2 Wl5z3JZA GXsJFkvJ 5wnAJlkv');
define('LOGGED_IN_SALT', 'hFNobLYu 5WBDs4Rd J1llMlLZ XwBzrrwp oEUzgQnw');
define('NONCE_SALT', 'NOvj3jik uboPaNnp Dj5Giwpj cjDK6xiU XumivTcx');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
