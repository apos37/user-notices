<?php
/**
 * Plugin Name:         User Notices
 * Plugin URI:          https://pluginrx.com/plugin/user-notices
 * Description:         Display targeted notices to your website visitors based on various criteria like login status, user roles, specific users, and more.
 * Version:             1.2.1
 * Requires at least:   6.0
 * Requires PHP:        8.0
 * Author:              PluginRx
 * Author URI:          https://pluginrx.com/
 * Discord URI:         https://discord.gg/3HnzNEJVnR
 * Text Domain:         user-notices
 * License:             GPL-2.0-or-later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:          https://pluginrx.com/
 * Created on:          February 6, 2025
 * Premium:             true
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserNotices;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Defines
 */
$plugin_data = get_file_data( __FILE__, [
    'name'         => 'Plugin Name',
    'description'  => 'Description',
    'version'      => 'Version',
    'plugin_uri'   => 'Plugin URI',
    'requires_php' => 'Requires PHP',
    'textdomain'   => 'Text Domain',
    'author'       => 'Author',
    'author_uri'   => 'Author URI',
    'discord_uri'  => 'Discord URI'
] );

// Versions
define( 'USERNOTICES_VERSION', $plugin_data[ 'version' ] );
define( 'USERNOTICES_MIN_PHP_VERSION', $plugin_data[ 'requires_php' ] );

// Names
define( 'USERNOTICES_NAME', $plugin_data[ 'name' ] );
define( 'USERNOTICES_TEXTDOMAIN', $plugin_data[ 'textdomain' ] );
define( 'USERNOTICES__TEXTDOMAIN', str_replace( '-', '_', USERNOTICES_TEXTDOMAIN ) );
define( 'USERNOTICES_AUTHOR', $plugin_data[ 'author' ] );
define( 'USERNOTICES_AUTHOR_URI', $plugin_data[ 'author_uri' ] );
define( 'USERNOTICES_PLUGIN_URI', $plugin_data[ 'plugin_uri' ] );
define( 'USERNOTICES_GUIDE_URL', USERNOTICES_AUTHOR_URI . 'guide/plugin/' . USERNOTICES_TEXTDOMAIN . '/' );
define( 'USERNOTICES_DOCS_URL', USERNOTICES_AUTHOR_URI . 'docs/plugin/' . USERNOTICES_TEXTDOMAIN . '/' );
define( 'USERNOTICES_SUPPORT_URL', USERNOTICES_AUTHOR_URI . 'support/plugin/' . USERNOTICES_TEXTDOMAIN . '/' );
define( 'USERNOTICES_DISCORD_URL', $plugin_data[ 'discord_uri' ] );

// Paths
define( 'USERNOTICES_BASENAME', plugin_basename( __FILE__ ) );                                                          //: text-domain/text-domain.php
define( 'USERNOTICES_ABSPATH', plugin_dir_path( __FILE__ ) );                                                           //: /home/.../public_html/wp-content/plugins/text-domain/
define( 'USERNOTICES_DIR', plugins_url( '/' . USERNOTICES_TEXTDOMAIN . '/' ) );                                         //: https://domain.com/wp-content/plugins/text-domain/
define( 'USERNOTICES_INCLUDES_ABSPATH', USERNOTICES_ABSPATH . 'inc/' );                                                 //: /home/.../public_html/wp-content/plugins/text-domain/includes/
define( 'USERNOTICES_INCLUDES_DIR', USERNOTICES_DIR . 'inc/' );                                                         //: https://domain.com/wp-content/plugins/text-domain/includes/
define( 'USERNOTICES_CSS_PATH', USERNOTICES_INCLUDES_DIR . 'css/' );                                                    //: https://domain.com/wp-content/plugins/text-domain/includes/css/
define( 'USERNOTICES_JS_PATH', USERNOTICES_INCLUDES_DIR . 'js/' );                                                      //: https://domain.com/wp-content/plugins/text-domain/includes/js/
define( 'USERNOTICES_LANG_PATH', USERNOTICES_INCLUDES_DIR . 'lang/' );                                                  //: https://domain.com/wp-content/plugins/text-domain/includes/lang/
define( 'USERNOTICES_SETTINGS_PATH', admin_url( 'edit.php?post_type=' . USERNOTICES_TEXTDOMAIN . '&page=settings' ) );  //: https://domain.com/wp-admin/?page=text-domain

// Screen IDs
define( 'USERNOTICES_SETTINGS_SCREEN_ID', USERNOTICES_TEXTDOMAIN . '_page_' . USERNOTICES__TEXTDOMAIN . '_settings' );
define( 'USERNOTICES_EXAMPLES_SCREEN_ID', USERNOTICES_TEXTDOMAIN . '_page_' . USERNOTICES__TEXTDOMAIN . '_examples' );
define( 'USERNOTICES_INTERACTIONS_SCREEN_ID', USERNOTICES_TEXTDOMAIN . '_page_' . USERNOTICES__TEXTDOMAIN . '_interactions' );
define( 'USERNOTICES_REPORT_SCREEN_ID', USERNOTICES_TEXTDOMAIN . '_page_' . USERNOTICES__TEXTDOMAIN . '_report' );


/**
 * Includes
 */
require_once USERNOTICES_INCLUDES_ABSPATH . 'db.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'common.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'helpers.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'wp-list-table.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'updater.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'interactions.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'report.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'examples.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'settings.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'notices-back.php';
require_once USERNOTICES_INCLUDES_ABSPATH . 'notices-front.php';