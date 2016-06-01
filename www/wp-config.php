<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'lnsjauni_wp389');

/** MySQL database username */
define('DB_USER', 'lnsjauni_wp389');

/** MySQL database password */
define('DB_PASSWORD', '3[6Pm95[YS');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

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
define('AUTH_KEY',         'cdxntzaostknrtdumth81ldrikbtyteysdxp6asrhcam53nwx6iuvpa9endrtoi9');
define('SECURE_AUTH_KEY',  'tiehqjbmogk5fxwsnpx2evhkpqcmkmxazqrew14sfkrszi6tdykb2vxrrovnnec9');
define('LOGGED_IN_KEY',    'wzxlbinvhczuox8097mk6ijei4m7trw6jwsgdwapdzuvw9dupof6ek7sdmj94rlc');
define('NONCE_KEY',        'u5o13ovf1vnwxdb6j4pn0pqfxoqjl89pry1bng8tt1euheiwspi8bozydpaeufka');
define('AUTH_SALT',        'zdktxqqomqruwpseqxsc3ewxlx6ekegpk1fnarcoukln4eyj9dp9p9yyzvjjj0uq');
define('SECURE_AUTH_SALT', 'urgoxurmc2gsrtyqfwnsqzkl54n8llajwmjrrhqnv2dnidivzdivtb450scg3bts');
define('LOGGED_IN_SALT',   'bfnnvfkdan2mo5kegtrg4n2znykcdpftoysgsxcynfuas2kdtdekzpkrgrv2jelw');
define('NONCE_SALT',       'vqek98bqgrnedniuxcd1fofxf3bfxglmf6ucnlniygpxuiqyhktlofxxdxepr9lh');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp0b_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
