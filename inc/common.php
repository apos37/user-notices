<?php
/**
 * Version check, activation, deactivation, uninstallation, etc.
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserNotices;
use PluginRx\UserNotices\Database;
use PluginRx\UserNotices\Interactions;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
new Common();


/**
 * Register hooks
 */
register_uninstall_hook( USERNOTICES_BASENAME, [ 'PluginRx\UserNotices\Common', 'uninstall_plugin' ] );


/**
 * The class
 */
class Common {

    /**
     * Constructor
     */
    public function __construct() {

        // PHP Version check
		$this->check_php_version();

		// Create the table
        add_action( 'init', [ new Database(), 'maybe_create_table' ] );

		// Add links to the website and discord
        add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

        // Remove leftover license data from older versions
		add_action( 'admin_init', [ $this, 'remove_old_license_data' ] );
        
    } // End __construct()


	/**
	 * Prevent loading the plugin if PHP version is not minimum
	 *
	 * @return void
	 */
	public function check_php_version() {
		if ( version_compare( PHP_VERSION, USERNOTICES_MIN_PHP_VERSION, '<=' ) ) {
			add_action( 'admin_init', function() {
				deactivate_plugins( USERNOTICES_BASENAME );
			} );
			add_action( 'admin_notices', function() {
				/* translators: 1: Plugin name, 2: Required PHP version */
				$notice = sprintf( __( '%1$s requires PHP %2$s or newer.', 'user-notices' ),
					USERNOTICES_NAME,
					USERNOTICES_MIN_PHP_VERSION
				);
				echo wp_kses_post(
					'<div class="notice notice-error"><p>' . esc_html( $notice ) . '</p></div>'
				);
			} );
			return;
		}
	} // End check_php_version()	


    /**
	 * Remove the license data left behind by older versions (runs once)
	 *
	 * @return void
	 */
	public function remove_old_license_data() {
		$flag = USERNOTICES_TEXTDOMAIN . '-license-data-removed';
		if ( get_option( $flag ) ) {
			return;
		}

		delete_option( USERNOTICES_TEXTDOMAIN . '-license-id' );
		delete_option( USERNOTICES_TEXTDOMAIN . '-license-result' );
		delete_option( USERNOTICES_TEXTDOMAIN . '-license-hbv' );
		delete_transient( USERNOTICES_TEXTDOMAIN . '-license-data' );
		delete_transient( USERNOTICES__TEXTDOMAIN . '_update_check' );

		update_option( $flag, 1, false );
	} // End remove_old_license_data()


	/**
     * Add links to the website and discord
     *
     * @param array $links The existing plugin row meta links.
     * @param string $file The plugin file path.
     * @return array
     */
    public function plugin_row_meta( $links, $file ) {
        $text_domain = USERNOTICES_TEXTDOMAIN;
        if ( $text_domain . '/' . $text_domain . '.php' == $file ) {

            $guide_url = USERNOTICES_GUIDE_URL;
            $docs_url = USERNOTICES_DOCS_URL;
            $support_url = USERNOTICES_SUPPORT_URL;
            $plugin_name = USERNOTICES_NAME;

            $our_links = [
                'guide' => [
                    // translators: Link label for the plugin's user-facing guide.
                    'label' => __( 'How-To Guide', 'user-notices' ),
                    'url'   => $guide_url
                ],
                'docs' => [
                    // translators: Link label for the plugin's developer documentation.
                    'label' => __( 'Developer Docs', 'user-notices' ),
                    'url'   => $docs_url
                ],
                'support' => [
                    // translators: Link label for the plugin's support page.
                    'label' => __( 'Support', 'user-notices' ),
                    'url'   => $support_url
                ],
            ];

            $row_meta = [];
            foreach ( $our_links as $key => $link ) {
                // translators: %1$s is the link label, %2$s is the plugin name.
                $aria_label = sprintf( __( '%1$s for %2$s', 'user-notices' ), $link[ 'label' ], $plugin_name );
                $row_meta[ $key ] = '<a href="' . esc_url( $link[ 'url' ] ) . '" target="_blank" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $link[ 'label' ] ) . '</a>';
            }

            // Add the links
            return array_merge( $links, $row_meta );
        }

        // Return the links
        return (array) $links;
    } // End plugin_row_meta()


    /**
     * Uninstall the plugin
	 * 
	 * @return void
     */
    public static function uninstall_plugin() {
        if ( get_option( USERNOTICES_TEXTDOMAIN . '-delete-db', false ) ) {
            (new Database())->delete_table();
        }

		delete_option( (new Interactions())->option_key_per_page );

        delete_option( USERNOTICES_TEXTDOMAIN . '-license-id' );
        delete_option( USERNOTICES_TEXTDOMAIN . '-license-result' );
        delete_option( USERNOTICES_TEXTDOMAIN . '-license-hbv' );
        delete_option( USERNOTICES_TEXTDOMAIN . '-license-data-removed' );
        delete_transient( USERNOTICES_TEXTDOMAIN . '-license-data' );
        delete_transient( USERNOTICES__TEXTDOMAIN . '_update_check' );
    } // End uninstall_plugin()
    
}