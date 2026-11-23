<?php 
/**
 * Plugin settings
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserNotices;
use PluginRx\UserNotices\Helpers;
use PluginRx\UserNotices\Notices;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
	(new Settings())->init();
} );


/**
 * The class
 */
class Settings {

    /**
     * Load on init
     */
    public function init() {
        
		// Submenu
        add_action( 'admin_menu', [ $this, 'submenu' ] );

		// Settings fields
        add_action( 'admin_init', [  $this, 'settings_fields' ] );

		// Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

    } // End init()


	/**
     * Submenu
     *
     * @return void
     */
    public function submenu() {
        add_submenu_page(
            'edit.php?post_type=' . (new Notices())->post_type,
            USERNOTICES_NAME . ' — ' . __( 'Settings', 'user-notices' ),
            __( 'Settings', 'user-notices' ),
            'manage_options',
            USERNOTICES__TEXTDOMAIN . '_settings',
            [ $this, 'page' ]
        );
    } // End submenu()

    
    /**
     * The page
     *
     * @return void
     */
    public function page() {
        global $current_screen;
        if ( $current_screen->id != USERNOTICES_SETTINGS_SCREEN_ID ) {
            return;
        }
        ?>
        <style>
        h2 { margin: 3rem 0 1rem 0; }
        </style>
		<div class="wrap">
			<h1><?php echo esc_attr( get_admin_page_title() ) ?></h1>

			<form method="post" action="options.php">
				<?php
					settings_fields( USERNOTICES_TEXTDOMAIN );
					do_settings_sections( USERNOTICES_TEXTDOMAIN );
					submit_button();
				?>
			</form>
		</div>
        <?php
    } // End page()


    /**
     * Store the settings fields here so we can call them on uninstall as well
     *
     * @return array
     */
    public function get_settings_fields( $return_keys_only = false ) {   
        $fields = [
            [
                'key'        => 'delete-db',
                'title'      => __( 'Delete All User Interaction Data on Uninstall', 'user-notices' ),
                'type'       => 'checkbox',
                'sanitize'  => 'sanitize_checkbox',
                'section'    => 'general',
                /* translators: %s: Name of the database table */
                'comments'   => sprintf( __( 'We log dismissals and actions taken in a database table called %s. This option will delete the table when you uninstall the plugin.', 'user-notices' ),
                    '<code>' . (new Database())->table_name . '</code>'
                )
            ],
        ];

        // Return
        if ( $return_keys_only ) {
            $field_keys = [];
            foreach ( $fields as $field ) {
                $field_keys[] = $field[ 'key' ];
            }
            return $field_keys;
        }
        return $fields;
    } // End get_settings_fields()


    /**
     * Settings fields
     *
     * @return void
     */
    public function settings_fields() {
        // Slug
        $slug = USERNOTICES_TEXTDOMAIN;

        /**
         * Sections
         */
        $settings_sections = [
            [ 'general', __( 'General', 'user-notices' ), '' ]
        ];

        // Iter the sections
        foreach ( $settings_sections as $settings_section ) {
            add_settings_section(
                $settings_section[0],
                $settings_section[1],
                $settings_section[2],
                $slug
            );
        }
        
        /**
         * Fields
         */
        $fields = $this->get_settings_fields( false );

        // Iter the fields
        foreach ( $fields as $field ) {
            $option_name = USERNOTICES_TEXTDOMAIN.'-'.$field[ 'key' ];
            $callback = 'settings_field_'.$field[ 'type' ];
            $args = [
                'id'    => $option_name,
                'class' => $option_name,
                'name'  => $option_name,
            ];

            // Add comments
            if ( isset( $field[ 'comments' ] ) ) {
                $args[ 'comments' ] = $field[ 'comments' ];
            }
            
            // Add select options
            if ( isset( $field[ 'options' ] ) ) {
                $args[ 'options' ] = $field[ 'options' ];
            }

            // Add default
            if ( isset( $field[ 'default' ] ) ) {
                $args[ 'default' ] = $field[ 'default' ];
            }

            // Add revert
            if ( isset( $field[ 'revert' ] ) ) {
                $args[ 'revert' ] = $field[ 'revert' ];
            }

            // Add the field
            register_setting( $slug, $option_name, sanitize_key( $field[ 'sanitize' ] ) );
            add_settings_field( $option_name, $field[ 'title' ], [ $this, $callback ], $slug, $field[ 'section' ], $args );
        }
    } // End settings_fields()
  
    
    /**
     * Custom callback function to print text field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_text( $args ) {
        $width = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '30rem';
        $comments = isset( $args[ 'comments' ] ) ? $args[ 'comments' ] : '';
        $default = isset( $args[ 'default' ] )  ? $args[ 'default' ] : '';
        $value = get_option( $args[ 'name' ], $default );

        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] == true && trim( $value ) == '' ) {
            $value = $default;
        }

        printf(
            '<input type="text" id="%s" name="%s" value="%s" style="width: %s;" />%s',
            esc_attr( $args[ 'id' ] ),
            esc_attr( $args[ 'name' ] ),
            esc_html( $value ),
            esc_attr( $width ),
            wp_kses_post( $comments )
        );
    } // settings_field_text()


    /**
     * Custom callback function to print checkbox field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_checkbox( $args ) {
        $value = $this->sanitize_checkbox( get_option( $args[ 'name' ] ) );
        printf(
            '<input type="checkbox" id="%s" name="%s" value="yes" %s/> <p class="description">%s</p>',
            esc_html( $args[ 'name' ] ),
            esc_html( $args[ 'name' ] ),
            esc_html( checked( 1, $value, false ) ),
            wp_kses( $args[ 'comments' ], [ 'code' => [] ] )
        );        
    } // End field_checkbox()


    /**
     * Sanitize checkbox
     *
     * @param int $value
     * @return boolean
     */
    public function sanitize_checkbox( $value ) {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    } // End sanitize_checkbox()


	/**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        // Check if we are on the correct admin page
        if ( $hook !== USERNOTICES_SETTINGS_SCREEN_ID ) {
            return;
        }

		// Register and enqueue your CSS
		wp_enqueue_style( USERNOTICES_TEXTDOMAIN . '-styles', USERNOTICES_CSS_PATH . 'settings.css', [], USERNOTICES_VERSION );
    } // End enqueue_scripts()

}
