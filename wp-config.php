<?php
define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u155787068_hSjVe' );

/** Database username */
define( 'DB_USER', 'u155787068_jB2dO' );

/** Database password */
define( 'DB_PASSWORD', 'F56ofn5hji' );

/** Database hostname */
define( 'DB_HOST', 'db' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '_elF.F#{U22gnD=iAX^VSO0ZS->CyoMc+6(9<_nrS[E,?z44Upc?NWV<PB7MW2(4' );
define( 'SECURE_AUTH_KEY',   '>f;KhgH,$Df+[}V!R%c-X784p.CgUV4Lx.yGTQIw,;8GX<bN}FF9dyNP1T6W([JS' );
define( 'LOGGED_IN_KEY',     'MQNa]/q0Xi~SgeYZw~L/@{i=j&<] u045#[8dG]a ]=s$-ZPj@.F@YlmWG<};GkK' );
define( 'NONCE_KEY',         'J6Cq4/4wlsh&9du-OY}CoI.eE6Ey/1O~]eUhW7o%LHSr*OU5a#+PLE@<wfc[KHRQ' );
define( 'AUTH_SALT',         '_+aJqi}nPe<=l_{ZJfI:T|EYn~$%~T]yPt/B4Qm[hk;tnh2U_,!`iFO{T,YLV}{L' );
define( 'SECURE_AUTH_SALT',  '/%ij*W]pPGFIF&w`H3{])io{)h}Tc SXfWk6cuQcRFXLu0>N2>Fx1A]KM=._i1Cd' );
define( 'LOGGED_IN_SALT',    '/d>4N|ta#5:Dbw= LOh0Om8e3(->i?#8h^>efYx_Xr3W/J_zGiy%bLK4)gy&&`Yj' );
define( 'NONCE_SALT',        ':IXS3>16aQm|~zg5k4O6!s*w- PRSn@|b~o_pCn)1NFKIU<H)R)Z{^d(58@3oJn=' );
define( 'WP_CACHE_KEY_SALT', '4s$>C={b|#VQZ;,,sh(-4V>1=1=(SdUDhSI2zx7Q*pfs#[|e`{6am%Q+Sn(l.ZEJ' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', 'b2ecee8c6af492ef6788f936aee3c569' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/**
 * Dynamic URL overrides for migration and Docker.
 */
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    define('WP_HOME', $protocol . '://' . $_SERVER['HTTP_HOST']);
    define('WP_SITEURL', $protocol . '://' . $_SERVER['HTTP_HOST']);
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
