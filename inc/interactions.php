<?php
/**
 * Interactions
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserNotices;
use PluginRx\UserNotices\Notices;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
	(new Interactions())->init();
} );


/**
 * The class
 */
class Interactions {

	/**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'usernotices_nonce';


    /**
     * Option keys
     *
     * @var string
     */
    public $option_key_per_page = 'usernotices_per_page';


	/**
	 * Load on init
	 *
	 * @return void
	 */
	public function init() {
        
		// Submenu
        add_action( 'admin_menu', [ $this, 'submenu' ] );

    } // End init()


	/**
     * Submenu
     *
     * @return void
     */
    public function submenu() {
        add_submenu_page(
            'edit.php?post_type='.(new Notices())->post_type,
            USERNOTICES_NAME . ' — ' . __( 'Interactions', 'user-notices' ),
            __( 'Interactions', 'user-notices' ),
            'manage_options',
            USERNOTICES__TEXTDOMAIN . '_interactions',
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
        if ( $current_screen->id != USERNOTICES_INTERACTIONS_SCREEN_ID ) {
            return;
        }

        // Possible filters
        $filters = [
            'user'   => __( 'User ID', 'user-notices' ),
            'notice' => __( 'Notice', 'user-notices' ),
            'type'   => __( 'Action Type', 'user-notices' )
        ];

        // Vars
        $user_id = null;
        $user_ip = null;
        $notice_id = null;
        $type = null;
        $per_page = get_option( $this->option_key_per_page, 25 );
        $paged = 1;

        // If we have a nonce, let's check for the rest
        if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET[ '_wpnonce' ] ) ), $this->nonce ) ) {

            // Check for filters
            foreach ( $filters as $key => $label ) {
                if ( isset( $_GET[ $key ] ) && $_GET[ $key ] != '' ) {
                    if ( $key == 'type' ) {
                        $type = sanitize_key( $_GET[ $key ] );
                    } elseif ( $key == 'notice' ) {
                        $notice_id = absint( $_GET[ $key ] );
                    } else {
                        $user_search = sanitize_text_field( $_GET[ $key ] );

                        if ( filter_var( $user_search, FILTER_VALIDATE_IP) !== false ) {
                            $user_ip = $user_search;
                        } elseif ( is_numeric( $user_search ) || $user_search === 0 ) {
                            $user_id = absint( $user_search );
                        } else {
                            $user = get_user_by( 'email', $user_search );
                            if ( !$user ) {
                                $user = get_user_by( 'login', $user_search );
                            }
                            $user_id = $user ? $user->ID : null;
                        }
                    }
                }
            }
        }

        // Paging
        $per_page = isset( $_GET[ 'per_page' ] ) ? intval( $_GET[ 'per_page' ] ) : $per_page;
        $paged = isset( $_GET[ 'paged' ] ) ? max( 1, intval( $_GET[ 'paged' ] ) ) : $paged;

        // Let's get the records
        $records = (new Database())->get( $user_id, $user_ip, $notice_id, $type , 'time' );

        // Total
        $total = count( $records );

        // Ensure the current page is not out of range
        if ( $paged > $total ) {
            $paged = $total;
        }

        // Calculate the offset
        $offset = ( $paged - 1 ) * $per_page;

        // Slice the forms array
        $records_to_display = array_slice( $records, $offset, $per_page );

        // Define list table columns
        $columns = [
            'date'    => __( 'Date', 'user-notices' ),
            'title'   => __( 'Notice', 'user-notices' ),
            'action'  => __( 'Action Taken', 'user-notices' ),
            'user'    => __( 'User', 'user-notices' )
        ];

        // Collect data
        $data = [];

        // The IP path
        $ip_address_link = sanitize_text_field( apply_filters( 'usernotices_ip_lookup_path', 'https://www.criminalip.io/asset/report/{ip}' ) );

        // Iter
        foreach ( $records_to_display as $record ) {
            $title = get_the_title( $record->notice_id );
            if ( $title ) {
                $title = '<a href="' . get_edit_post_link( $record->notice_id ) . '">' . $title . '</a>';
            } else {
                $title = __( 'Unknown Notice ID: ', 'user-notices' ) . $record->notice_id;
            }

            $action =  ( $record->type == 'dismiss' ) ? '<em>' . __( 'Dismissed', 'user-notices' ) . '</em>' : __( 'Button: ', 'user-notices' ) . '<strong>' . $record->action . '</strong>';

            // Guests
            if ( $record->user_ip ) {
                $ip_address_link = sanitize_url( str_replace( '{ip}', $record->user_ip, $ip_address_link ) );
                $display_name = __( 'Guest', 'user-notices' ) . ' (<a href="' . $ip_address_link . '" target="_blank">' . $record->user_ip . '</a>)';

            // Logged-in members or guests that we didn't get an IP address for
            } else {
                $user = get_userdata( $record->user_id );
                if ( $user ) {
                    $display_name = '<a href="' . get_edit_profile_url( $record->user_id ) . '">' . $user->display_name . '</a>' . ' (ID: ' . $record->user_id . ')';
                } elseif ( $record->user_id == 0 ) {
                    $display_name = __( 'Guest (No IP Found)', 'user-notices' );
                } else {
                    $display_name = __( 'Unknown User ID: ', 'user-notices' ) . $record->user_id;
                }
            }

            $data[] = [
                'date'   => gmdate( 'F j, Y \a\t g:i A', strtotime( $record->time ) ),
                'title'  => $title,
                'action' => $action,
                'user'   => $display_name,
            ];
        }
        ?>

        <style>
        h2 { margin: 3rem 0 1rem 0; }
        </style>

		<div class="wrap">
			<h1><?php echo esc_attr( get_admin_page_title() ) ?></h1>
			<p><?php echo esc_html__( 'A list of all user interactions.', 'user-notices' ); ?></p>

            <div id="usernotice-interactions">
                <br><br>
                <?php $this->wp_list_table( $columns, $data, $total ); ?>
            </div>
		</div>
        <?php
        (new Helpers())->remove_qs_without_refresh();
    } // End page()


    /**
     * Return the WP List Table
     *
     * @param array $columns
     * @param array $data
     * @param string $current_tab
     * @param array $qs             [ 'param' => $value, 'param' => $value ]
     * @return void
     */
    public function wp_list_table( $columns, $data, $total_count = -1, $checkbox_name = false, $checkbox_value = false ) {
        // Per page
        if ( isset( $_GET[ 'per_page' ] ) && isset( $_REQUEST[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST[ '_wpnonce' ] ) ), $this->nonce ) ) {
            $current_per_page = intval( $_GET[ 'per_page' ] );
            update_option( $this->option_key_per_page, $current_per_page );
        } else {
            $current_per_page = get_option( $this->option_key_per_page, 25 );
        }
        
        ?>
        <form id="usernotices-items-per-page" method="GET" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
            <input type="hidden" name="page" value="interactions">
            <input type="hidden" name="post_type" value="<?php echo esc_attr( (new Notices())->post_type ); ?>">
            <?php

            // Display the table
            $table = new ListTable( $columns, $data, $this->nonce, $current_per_page, $total_count, $checkbox_name, $checkbox_value );
            $table->prepare_items();
            $table->display();

            // Add per-page dropdown
            $per_page_options = [ 5, 10, 25, 50 ];
            if ( !in_array( $current_per_page, $per_page_options ) ) {
                $per_page_options[] = $current_per_page;
                sort( $per_page_options );
            }
            ?>
            
            <?php wp_nonce_field( $this->nonce, '_wpnonce', false ); ?>
            <label for="per_page"><?php esc_html_e( 'Items per page:', 'user-notices' ); ?></label>
            <select name="per_page" id="per_page" onchange="this.form.submit()">
                <?php foreach ( $per_page_options as $option ) : ?>
                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $option, $current_per_page ); ?>>
                        <?php echo esc_html( $option ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php
    } // End wp_list_table()
    
}