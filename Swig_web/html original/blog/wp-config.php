<?php
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
define('WP_CACHE', true); //Added by WP-Cache Manager
define( 'WPCACHEHOME', '/nfs/c07/h01/mnt/99602/domains/swigme.com/html/blog/wp-content/plugins/wp-super-cache/' ); //Added by WP-Cache Manager
define('DB_NAME', 'db99602_swig');

/** MySQL database username */
define('DB_USER', '1clk_wp_ACSEgkf');

/** MySQL database password */
define('DB_PASSWORD', 'd6c4PIVE');

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
define('AUTH_KEY', 'gHWUNFzX HUii3bjb yn2Rbc4y 5wqdREj3 BcBZPooc');
define('SECURE_AUTH_KEY', 'BCBscPjA apyf8YNp mowZSfiw hOLYY4lh ufIOFcpr');
define('LOGGED_IN_KEY', 'p44LNxDx haPJ3znM Lf7ZUL3i uqTA7k6G lIAE3gcQ');
define('NONCE_KEY', 'Gjbs5BXS Dxpy4UWa FXOcbVjG wbtrubiR XMBaApD1');
define('AUTH_SALT', 'Xazn7oUY XodlMBnt QqWJLZJe hpG7ErDf RSAANiXR');
define('SECURE_AUTH_SALT', 'ZfJOsFbl 5EKDUZDn Fprwnzax PpYzPr1W JzBO5GNV');
define('LOGGED_IN_SALT', 'ej2nfwEk NbaXovvl PRrkrq7v VAE8i3vU D7rXYcCQ');
define('NONCE_SALT', '6R8waUTj bCQcId6f csZz8AGI 43rRwYHy PoPMvDTb');
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
