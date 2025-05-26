<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'cat-and-mouse-game' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

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
define( 'AUTH_KEY',         'yGs`aA{EF)NX<a_^&43;YdwKx|{T9ox:wZ5VoBiQFx:sgv}R[3^38n$Fw5XLw`V6' );
define( 'SECURE_AUTH_KEY',  'B}s<;<~j&GWiJ0}IFgzVg+0~8pY0kc>*xRMr>JFAqf}Zqd/64rv!Fanq[`8|e:Uv' );
define( 'LOGGED_IN_KEY',    '-^k?ugAs%o?t]Nq8=/zR%%`H;$^#$&.2ZL8K.ru)3jsCMi^hz_>L1hL/+K=67V&Q' );
define( 'NONCE_KEY',        '+h92ZYqhCR)k0r{!.L:>~ae-MI$D#bN1`&lB#%818T*T,C0Y/),1e6+=!XG*7`qc' );
define( 'AUTH_SALT',        '11s?npd3Y)T9L^GV)*}!vAN 5u0h5I@F<5@xsHPKGsy!5AlYrI+_!fR?rm>l(rb4' );
define( 'SECURE_AUTH_SALT', 'ntE1`5HyFk{in_]@nOH`@T{Be:RWr1JWK.|c[K5VncFI7$Mc0CU-6W@Z!g|MPl8^' );
define( 'LOGGED_IN_SALT',   'T>)lZ1UnGF_):u=`&}~(Fk,h:<Yg?8&nDU2h,9LzwABkp0AWY^aL@wExUze4vi0S' );
define( 'NONCE_SALT',       'P_&<#Sm=`?~Z6)I&o Fe##;;vXvG7$$H_Z`L;DvUOR/3bDkpLE>l):6f?o)R,<H@' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
