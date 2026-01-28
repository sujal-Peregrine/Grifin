<?php
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
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'eruvznx4_db' );

/** Database username */
define( 'DB_USER', 'sujal' );

/** Database password */
define( 'DB_PASSWORD', 'Admin@12' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'fv%8n)xNfYuuzAudb[MZN-4eUCDfW7,=i8Q|*LVC=|Y|RrU=!bBpoE`e/AF>vdWD' );
define( 'SECURE_AUTH_KEY',  'Hn?[$vr{0D-a<.{H$$^jcx9Z.mGLu9+xbJl|[AG$=RYGt;Vc?k)f#EhQyxb`dw<B' );
define( 'LOGGED_IN_KEY',    '~zh@>QSH]dl4m|eXWZrr=-= dkw<3NPz-JN)p_.yP+0>,TDTbNRv@bE5=rcEJZtW' );
define( 'NONCE_KEY',        '<BZsMp~<w c/&zF99-pS?-=11v#tCZkmH*.qjLa8et)LPL=Gyu2pLN|K~Kd<?0C6' );
define( 'AUTH_SALT',        'i<]Phe?^m8b%T=~h|,q}<Uxe:f|Wt^*xgjG*yQ(hpwVh4s]PxowbRzrmzJK`GcDG' );
define( 'SECURE_AUTH_SALT', '*5<eCPV RQ_bRQ&vXg!W9~:E|8l-7JlNU^#mv:HAH&zEkPux9,,T{*5o3-Np@G(<' );
define( 'LOGGED_IN_SALT',   'CcDdv.j9D 5b,=Rc7w#kv?VYfgJ,8iG Nuq]SMp~s 2A:IbwZ?m(9|,37J+37 7M' );
define( 'NONCE_SALT',       '73!4!JvPY}E)HzZ_4/J5-|uwLR[*Wsh.t8pdgB8~J7kAE.kO-1B4P[Vqp|oprFev' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );
define('WP_CACHE', true); 
define('WP_AUTO_UPDATE_CORE', false);

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';