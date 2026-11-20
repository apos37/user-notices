<?php 
/**
 * Notices on the back end
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserNotices;
use PluginRx\UserNotices\Helpers;
use PluginRx\UserNotices\Database;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
    (new Notices())->init();
} );


/**
 * The class
 */
class Notices {

    /**
     * Post type
     * 
     * @var string
     */ 
    public $post_type = 'user-notices';


    /**
     * Post meta keys
     *
     * @var string
     */
    public $meta_key_buttons = 'buttons';
    public $meta_key_site_location_type = 'site_location_type';
    public $meta_key_site_location = 'site_location';
    public $meta_key_screen_location_type = 'screen_location_type';
    public $meta_key_screen_location = 'screen_location';
    public $meta_key_audience_type = 'audience_type';
    public $meta_key_audience = 'audience';
    public $meta_key_start_date = 'start_date';
    public $meta_key_end_date = 'end_date';
    public $meta_key_dismissable = 'dismissable';
    public $meta_key_hide_after_action = 'hide_after_action';
    public $meta_key_delay = 'delay';
    public $meta_key_show_user_actions = 'show_user_actions';
    public $meta_key_debug = 'debug';
    public $meta_key_bg_color = 'bg_color';
    public $meta_key_dismissals = 'dismiss_count';
    public $meta_key_actions = 'actions_count';


    /**
     * $_SESSION keys
     *
     * @var string
     */
    public $session_key_dismissals = 'dismissals';
    public $session_key_actions = 'actions';


    /**
     * Common arrays
     *
     * @var array
     */
    public $site_locations = [];
    public $screen_locations = [];
    public $audience_types = [];
    public $merge_tags = [];


    /**
     * Default date and time formats
     *
     * @var string
     */
    public $default_date_format = 'F j, Y';
    public $default_time_format = 'g:i A';


    /**
     * Nonces
     *
     * @var string
     */
	private $edit_nonce = 'user_notices_edit_nonce';
    private $user_nonce = 'user_notices_user_nonce';


    /**
	 * Constructor
	 */
	public function __construct() {

        // Common arrays
        $this->site_locations = filter_var_array( apply_filters( 'usernotices_site_location_types', [
            'all'           => __( 'All Pages', 'user-notices' ),
            'homepage'      => __( 'Homepage Only', 'user-notices' ),
            'post_types'    => __( 'Specific Post Types', 'user-notices' ),
            'ids'           => __( 'Specific Posts & Pages', 'user-notices' ),
            'shortcode'     => __( 'Shortcode', 'user-notices' ),
        ] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        $this->screen_locations = filter_var_array( apply_filters( 'usernotices_screen_location_types', [
            'top'           => __( 'Top', 'user-notices' ),
            'above_content' => __( 'Above Content', 'user-notices' ),
            'below_content' => __( 'Below Content', 'user-notices' ),
            'bottom'        => __( 'Bottom', 'user-notices' ),
            'modal'         => __( 'Pop-Up Modal', 'user-notices' ),
            'alert'         => __( 'Pop-Up Alert (Plain Text Only, Does Not Track)', 'user-notices' ),
            'console'       => __( 'Developer Console (Limited Styling & No Buttons)', 'user-notices' ),
        ] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        $this->audience_types = filter_var_array( apply_filters( 'usernotices_audience_types', [
            'everyone'      => __( 'Everyone', 'user-notices' ),
            'logged_in'     => __( 'Logged-In Members', 'user-notices' ),
            'logged_out'    => __( 'Logged-Out Visitors', 'user-notices' ),
            'roles'         => __( 'Specific Roles', 'user-notices' ),
            'users'         => __( 'Specific Users (By ID, Email, or IP Address)', 'user-notices' ),
        ] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        $this->merge_tags = filter_var_array( apply_filters( 'usernotices_merge_tags', [
            'user' => [
                'label' => __( 'User Meta (logged-in users only)', 'user-notices' ),
                'keys' => [
                    'ID'              => __( 'ID', 'user-notices' ),
                    'user_email'      => __( 'Email', 'user-notices' ),
                    'display_name'    => __( 'Display Name', 'user-notices' ),
                    'nickname'        => __( 'Nickname', 'user-notices' ),
                    'first_name'      => __( 'First Name', 'user-notices' ),
                    'last_name'       => __( 'Last Name', 'user-notices' ),
                    'user_registered' => __( 'Registered Date', 'user-notices' ),
                ]
            ],
            'post' => [
                'label' => __( 'Post Meta (current post only)', 'user-notices' ),
                'keys' => [
                    'ID'           => __( 'ID', 'user-notices' ),
                    'post_title'   => __( 'Title', 'user-notices' ),
                    'post_excerpt' => __( 'Excerpt', 'user-notices' ),
                    'post_status'  => __( 'Status', 'user-notices' ),
                    'post_date'    => __( 'Published Date', 'user-notices' ),
                    'post_type'    => __( 'Type', 'user-notices' ),
                    'post_author'  => __( 'Author', 'user-notices' ),
                ]
            ],
            'date' => [
                'label' => __( 'Dates', 'user-notices' ),
                'keys'  => [
                    'current'                                   => __( 'Current Date', 'user-notices' ),
                    'current:' . $this->default_date_format     => __( 'Current Date Formatted', 'user-notices' ),
                    'start'                                     => __( 'Start Date', 'user-notices' ),
                    'start:' . $this->default_date_format       => __( 'Start Date Formatted', 'user-notices' ),
                    'end'                                       => __( 'End Date', 'user-notices' ),
                    'end:' . $this->default_date_format         => __( 'End Date Formatted', 'user-notices' )
                ]
            ],
            'time' => [
                'label' => __( 'Times', 'user-notices' ),
                'keys'  => [
                    'current'                                   => __( 'Current Time', 'user-notices' ),
                    'current:' . $this->default_time_format     => __( 'Current Time Formatted', 'user-notices' ),
                    'start'                                     => __( 'Start Time', 'user-notices' ),
                    'start:' . $this->default_time_format       => __( 'Start Time Formatted', 'user-notices' ),
                    'end'                                       => __( 'End Time', 'user-notices' ),
                    'end:' . $this->default_time_format         => __( 'End Time Formatted', 'user-notices' )
                ]
            ]
        ] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );

	} // End __construct()


    /**
     * Load on init, but not every time the class is called
     *
     * @return void
     */
    public function init() {

        // Register the post type
        $this->register_post_type();

        // Add background color to the editor
        if ( is_admin() ) {
            add_filter( 'mce_buttons', [ $this, 'add_mce_buttons' ] );
            add_filter( 'mce_external_plugins', [ $this, 'add_mce_plugin' ] );
            add_filter( 'tiny_mce_before_init', [ $this, 'allow_custom_mce_styles' ] );
        }

        // Change title placeholder
        add_filter( 'gettext', [ $this, 'title' ], 10, 2 );

        // Add the meta box
        add_action( 'add_meta_boxes', [ $this, 'meta_boxes' ] );

        // Save the post data
        add_action( 'save_post', [ $this, 'save_post' ] );

        // Add admin columns
        add_filter( 'manage_'.$this->post_type.'_posts_columns', [ $this, 'admin_columns' ] );
        add_action( 'manage_'.$this->post_type.'_posts_custom_column', [ $this, 'admin_column_content' ], 10, 2 );

        // Delete records for the notice
        add_action( 'before_delete_post', [ new Database(), 'delete_notice_records' ] );

        // Clear user records
        add_action( 'show_user_profile', [ $this, 'user_clear_records_button' ] );
        add_action( 'edit_user_profile', [ $this, 'user_clear_records_button' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

    } // End init()


    /**
     * Register the post type
     */
    public function register_post_type() {
        // Set the labels
        $labels = [
            'name'                  => _x( 'User Notices', 'Post Type General Name', 'user-notices' ),
            'singular_name'         => _x( 'User Notice', 'Post Type Singular Name', 'user-notices' ),
            'menu_name'             => __( 'User Notices', 'user-notices' ),
            'name_admin_bar'        => __( 'User Notices', 'user-notices' ),
            'archives'              => __( 'User Notice Archives', 'user-notices' ),
            'attributes'            => __( 'User Notice Attributes', 'user-notices' ),
            'parent_item_colon'     => __( 'Parent User Notice:', 'user-notices' ),
            'all_items'             => __( 'All User Notices', 'user-notices' ),
            'add_new_item'          => __( 'Add New User Notice', 'user-notices' ),
            'add_new'               => __( 'Add New', 'user-notices' ),
            'new_item'              => __( 'New User Notice', 'user-notices' ),
            'edit_item'             => __( 'Edit User Notice', 'user-notices' ),
            'update_item'           => __( 'Update User Notice', 'user-notices' ),
            'view_item'             => __( 'View User Notice', 'user-notices' ),
            'view_items'            => __( 'View User Notices', 'user-notices' ),
            'search_items'          => __( 'Search User Notices', 'user-notices' ),
            'not_found'             => __( 'Not found', 'user-notices' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'user-notices' ),
            'featured_image'        => __( 'Featured Image', 'user-notices' ),
            'set_featured_image'    => __( 'Set featured image', 'user-notices' ),
            'remove_featured_image' => __( 'Remove featured image', 'user-notices' ),
            'use_featured_image'    => __( 'Use as featured image', 'user-notices' ),
            'insert_into_item'      => __( 'Insert into user notice', 'user-notices' ),
            'uploaded_to_this_item' => __( 'Uploaded to this user notice', 'user-notices' ),
            'items_list'            => __( 'User Notice list', 'user-notices' ),
            'items_list_navigation' => __( 'User Notice list navigation', 'user-notices' ),
            'filter_items_list'     => __( 'Filter user notice list', 'user-notices' ),
        ];
    
        // Set the CPT args
        $args = [
            'label'                 => __( 'User Notices', 'user-notices' ),
            'description'           => __( 'User Notices', 'user-notices' ),
            'labels'                => $labels,
            'supports'              => [ 'title', 'editor' ],
            'taxonomies'            => [],
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_icon'             => 'dashicons-megaphone',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'query_var'             => $this->post_type,
            'capability_type'       => 'post',
            'show_in_rest'          => false,
        ];
    
        // Register the CPT
        register_post_type( $this->post_type, $args );
    } // End register_post_type()


    /**
     * Add a background color button to the editor
     *
     * @param array $buttons
     * @return array
     */
    public function add_mce_buttons( $buttons ) {
        global $current_screen;
        if ( $current_screen->id == $this->post_type ) {
            $buttons[] = 'usernotices_bgcolor_button';
            $buttons[] = 'usernotices_mergetags_button';
        }
        return $buttons;
    } // End add_mce_buttons()

    public function add_mce_plugin( $plugin_array ) {
        global $current_screen;
        if ( $current_screen->id == $this->post_type ) {
            $plugin_array[ 'usernotices_bgcolor_plugin' ] = USERNOTICES_JS_PATH . 'tinymce-bgcolor.js';
            $plugin_array[ 'usernotices_mergetags_plugin' ] = USERNOTICES_JS_PATH . 'tinymce-mergetags.js';
        }
        return $plugin_array;
    } // End add_mce_plugin()

    public function allow_custom_mce_styles( $init_array ) {
        global $post, $current_screen;
        if ( $current_screen->id == $this->post_type ) {
            $bg_color = sanitize_hex_color( get_post_meta( $post->ID, $this->meta_key_bg_color, true ) );
            if ( $bg_color ) {
                $init_array[ 'content_style' ] = 'body { background-color: ' . esc_attr( $bg_color ) . '; }';
            }
        }
        return $init_array;
    } // End allow_custom_mce_styles()


    /**
     * Rename title
     *
     * @param string $translation
     * @param string $original
     * @return string
     */
    public function title( $translation, $original ) {
        // Admin only
        if ( !is_admin() ) {
            return $translation;
        }

        // Make sure we are only looking at our post type
        global $post_type;
        if ( $post_type != $this->post_type ) {
            return $translation;
        }

        // Update the box
        if ( 'Add title' == $original ) {
            return __( 'Add title (used for your records only; not displayed on the notice)', 'user-notices' );
        }
        return $translation;
    } // End title()


    /**
     * Meta box
     *
     * @return void
     */
    public function meta_boxes() {
        add_meta_box( 
            'notice_settings',
            __( 'Notice Settings', 'user-notices' ),
            [ $this, 'meta_box_content' ],
            $this->post_type,
            'advanced',
			'high'
        );

        add_meta_box( 
            'notice_interactions',
            __( 'Interactions', 'user-notices' ),
            [ $this, 'meta_box_interactions' ],
            $this->post_type,
            'advanced',
			'default'
        );
    } // End meta_boxes()


    /**
     * Meta box content
     *
     * @param object $post
     * @return void
     */
    public function meta_box_content( $post ) {
        $notice_id = $post->ID;
        wp_nonce_field( $this->edit_nonce, $this->edit_nonce );

        // Get the current values
        $buttons = get_post_meta( $notice_id, $this->meta_key_buttons, true );
        $buttons = !empty( $buttons ) ? filter_var_array( $buttons, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : [];
        $buttons_qty = absint( apply_filters( 'usernotices_buttons_qty', 3 ) );

        $site_location_type = sanitize_key( get_post_field( $this->meta_key_site_location_type, $notice_id ) );
        $screen_location_type = sanitize_key( get_post_field( $this->meta_key_screen_location_type, $notice_id ) );

        $audience_type = sanitize_key( get_post_field( $this->meta_key_audience_type, $notice_id ) );

        $start_date = sanitize_text_field( get_post_field( $this->meta_key_start_date, $notice_id ) );
        $end_date = sanitize_text_field( get_post_field( $this->meta_key_end_date, $notice_id ) );

        $start_date_date = '';
        $start_date_time = '';
        if ( $start_date ) {
            $start_date_date = gmdate( 'Y-m-d', $start_date );
            $start_date_time = gmdate( 'H:i', $start_date );
        }
        $end_date_date = '';
        $end_date_time = '';
        if ( $end_date ) {
            $end_date_date = gmdate( 'Y-m-d', $end_date );
            $end_date_time = gmdate( 'H:i', $end_date );
        }

        $dismissable = filter_var( get_post_field( $this->meta_key_dismissable, $notice_id ), FILTER_VALIDATE_BOOLEAN );
        $hide_after_action = filter_var( get_post_field( $this->meta_key_hide_after_action, $notice_id ), FILTER_VALIDATE_BOOLEAN );
        $delay = absint( get_post_field( $this->meta_key_delay, $notice_id ) );
        $debug = filter_var( get_post_field( $this->meta_key_debug, $notice_id ), FILTER_VALIDATE_BOOLEAN );

        $bg_color = sanitize_hex_color( get_post_field( $this->meta_key_bg_color, $notice_id ) );
        ?>

        <!-- BUTTONS -->
        <div id="buttons">
            <h3><?php echo esc_html__( 'Action Buttons', 'user-notices' ); ?></h3>
            <p><?php echo esc_html__( 'You can add buttons to your notice to use like a survey or to log responses. Responses will be shown in the "Interactions" section below. If you include a URL, the user will be redirected when clicking on the button, otherwise it will simply close the notice. Developers can also add a meta key to log the action on the user\'s meta; it will add the meta key with 1 (true) as the value. Leave button text blank if you don\'t want to use buttons. If you add a form to the body content above, the submit button will act as an action button as well.', 'user-notices' ); ?></p>

            <?php
            for ( $b = 0; $b < $buttons_qty; $b++ ) {
                ?>
                <div class="col-btns">
                    <?php
                    $columns = [
                        'text'    => __( 'Button Text', 'user-notices' ),
                        'bgcolor' => __( 'Background Color', 'user-notices' ),
                        'color'   => __( 'Text Color', 'user-notices' ),
                        'url'     => __( 'URL (Optional)', 'user-notices' ),
                        'metakey' => __( 'Meta Key (Optional)', 'user-notices' ),
                    ];
                    foreach ( $columns as $c => $column ) {
                        $value = isset( $buttons[ $b ][ $c ] ) ? $buttons[ $b ][ $c ] : '';

                        if ( $c == 'bgcolor' ) {
                            $value = $value && $value != '' ? $value : '#2271B1';
                            $input_type = 'color';
                        } elseif ( $c == 'color' ) {
                            $value = $value && $value != '' ? $value : '#FFFFFF';
                            $input_type = 'color';
                        } else {
                            $input_type = 'text';
                        }                        
                        ?>
                        <div class="btn-col-<?php echo esc_attr( $c ); ?>">
                            <label for="un-buttons-<?php echo esc_attr( $b ); ?>-<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $column ); ?>:</label>
                            <input id="un-buttons-<?php echo esc_attr( $b ); ?>-<?php echo esc_attr( $c ); ?>" type="<?php echo esc_attr( $input_type ); ?>" name="buttons[<?php echo esc_attr( $b ); ?>][<?php echo esc_attr( $c ); ?>]" value="<?php echo esc_html( $value ); ?>">
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- LOCATION -->
        <div id="location">
            <h3><?php echo esc_html__( 'Location', 'user-notices' ); ?></h3>
            <div class="col-2">
                <div class="col-left">
                    <label for="un-site-location-type"><?php echo esc_html__( 'Site Location', 'user-notices' ); ?>:</label>
                    <select id="un-site-location-type" name="<?php echo esc_attr( $this->meta_key_site_location_type ); ?>" required>
                        <option value=""><?php echo esc_html__( '-- Select One --', 'user-notices' ); ?></option>
                        <?php
                        foreach ( $this->site_locations as $key => $label ) {
                            ?>
                            <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $site_location_type ); ?>><?php echo esc_attr( $label ); ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="col-right" id="un-site-location-cont">
                    <!-- FIELD POPULATED DYNAMICALLY BY JQUERY -->
                </div>
            </div>

            <div class="col-2" id="screen-location-field">
                <div class="col-left">
                    <label for="un-screen-location-type"><?php echo esc_html__( 'Screen Location', 'user-notices' ); ?>:</label>
                    <select id="un-screen-location-type" name="<?php echo esc_attr( $this->meta_key_screen_location_type ); ?>" required>
                        <option value=""><?php echo esc_html__( '-- Select One --', 'user-notices' ); ?></option>
                        <?php
                        foreach ( $this->screen_locations as $key => $label ) {
                            ?>
                            <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $screen_location_type ); ?>><?php echo esc_attr( $label ); ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="col-right" id="un-screen-location">
                    <!-- FIELD POPULATED DYNAMICALLY BY JQUERY -->
                </div>
            </div>
        </div>

        <!-- AUDIENCE -->
        <div id="audience">
            <h3><?php echo esc_html__( 'Audience', 'user-notices' ); ?></h3>
            <div class="col-2">
                <div class="col-left">
                    <label for="un-audience-type"><?php echo esc_html__( 'Audience Type', 'user-notices' ); ?>:</label>
                    <select id="un-audience-type" name="<?php echo esc_attr( $this->meta_key_audience_type ); ?>" required>
                        <option value=""><?php echo esc_html__( '-- Select One --', 'user-notices' ); ?></option>
                        <?php
                        foreach ( $this->audience_types as $key => $label ) {
                            ?>
                            <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $audience_type ); ?>><?php echo esc_attr( $label ); ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
                <div class="col-right" id="un-audience">
                    <!-- FIELD POPULATED DYNAMICALLY BY JQUERY -->
                </div>
            </div>
        </div>

        <!-- SCHEDULING -->
        <div id="scheduling">
            <h3><?php echo esc_html__( 'Scheduling', 'user-notices' ); ?></h3>
            <p><?php echo esc_html__( 'Timezone is based on your site settings: ', 'user-notices' ) . ' (' . esc_html( wp_timezone_string() ) . ').' . esc_html__( 'You may enter only a start date or end date, but you can not enter times without dates. If a start time is not specified, 00:00:00 will be used by default. If an end time is not specified, 23:59:59 will be used by default.', 'user-notices' ); ?></p>
            <div class="col-2">
                <div class="col-left">
                    <label for="un-start-date-date"><?php echo esc_html__( 'Start Date (Optional)', 'user-notices' ); ?>:</label>
                    <input id="un-start-date-date" type="date" name="<?php echo esc_attr( $this->meta_key_start_date . '_date' ); ?>" value="<?php echo esc_attr( $start_date_date ); ?>">
                    <label for="un-start-date-time"><?php echo esc_html__( 'Start Time (Optional)', 'user-notices' ); ?>:</label>
                    <input id="un-start-date-time" type="time" name="<?php echo esc_attr( $this->meta_key_start_date . '_time' ); ?>" value="<?php echo esc_attr( $start_date_time ); ?>">

                </div>
                <div class="col-right">
                    <label for="un-end-date-date"><?php echo esc_html__( 'End Date (Optional)', 'user-notices' ); ?>:</label>
                    <input id="un-end-date-date" type="date" name="<?php echo esc_attr( $this->meta_key_end_date . '_date' ); ?>" value="<?php echo esc_attr( $end_date_date ); ?>">
                    <label for="un-end-date-time"><?php echo esc_html__( 'End Time (Optional)', 'user-notices' ); ?>:</label>
                    <input id="un-end-date-time" type="time" name="<?php echo esc_attr( $this->meta_key_end_date . '_time' ); ?>" value="<?php echo esc_attr( $end_date_time ); ?>">
                </div>
            </div>
        </div>

        <!-- NON-TEMPLATE SETTINGS -->
        <div id="settings">
            <h3><?php echo esc_html__( 'Settings', 'user-notices' ); ?></h3>

            <div class="col-1">
                <label for="un-delay"><?php echo esc_html__( 'Delay (in seconds)', 'user-notices' ); ?></label>
                <input id="un-delay" type="number" name="<?php echo esc_attr( $this->meta_key_delay ); ?>" value="<?php echo esc_attr( $delay ); ?>">
            </div>

            <div class="col-1">
                <input id="un-dismissable" type="checkbox" name="<?php echo esc_attr( $this->meta_key_dismissable ); ?>" value="1"<?php checked( $dismissable ) ?>> 
                <label for="un-dismissable"><?php echo esc_html__( 'Allow Dismissing', 'user-notices' ); ?></label>
                <p class="desc"><?php echo esc_html__( 'Adds a little &times; button at the top-right of the notice so they can close it. (RECOMMENDED)', 'user-notices' ); ?></p>
            </div>

            <div class="col-1">
                <input id="un-hide-after-action" type="checkbox" name="<?php echo esc_attr( $this->meta_key_hide_after_action ); ?>" value="1"<?php checked( $hide_after_action ) ?>> 
                <label for="un-hide-after-action"><?php echo esc_html__( 'Hide After Action Taken', 'user-notices' ); ?></label>
                <p class="desc"><?php echo esc_html__( 'Actions include any buttons that the user clicks on. The notice will close regardless, but if this is disabled the notice will appear again the next time they load the page. (RECOMMENDED)', 'user-notices' ); ?></p>
            </div>

            <div class="col-1">
                <input id="un-debug" type="checkbox" name="<?php echo esc_attr( $this->meta_key_debug ); ?>" value="1"<?php checked( $debug ) ?>> 
                <label for="un-debug"><?php echo esc_html__( 'Debug Notice', 'user-notices' ); ?></label>
                <p class="desc"><?php echo esc_html__( 'If your notice is not showing up where you expect it to, you can enable this and look in the developer console on the page to see the reason.', 'user-notices' ); ?></p>
            </div>
        </div>

        <!-- HIDDEN INPUT FOR BACKGROUND COLOR -->
        <input type="hidden" name="<?php echo esc_attr( $this->meta_key_bg_color ); ?>" id="un-bg-color" value="<?php echo esc_attr( $bg_color ); ?>">
        <?php
    } // End meta_box_content()


    /**
     * Save the post data
     *
     * @param int $post_id
     * @return void
     */
    public function save_post( $post_id ) {
        // Validate
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( !(new Helpers())->can_save_post( $post_id, $this->post_type, $this->edit_nonce, $this->edit_nonce ) ) {
            return;
        }

		// Most other fields to save
		$fields = [
			$this->meta_key_site_location_type    => 'sanitize_key',
            $this->meta_key_site_location         => 'mixed',
			$this->meta_key_screen_location_type  => 'sanitize_key',
			$this->meta_key_audience_type         => 'sanitize_key',
			$this->meta_key_audience              => 'mixed',
            $this->meta_key_dismissable           => 'boolean',
            $this->meta_key_hide_after_action     => 'boolean',
            $this->meta_key_delay                 => 'absint',
            $this->meta_key_show_user_actions     => 'boolean',
            $this->meta_key_debug                 => 'boolean',
            $this->meta_key_bg_color              => 'sanitize_hex_color'
		];
	
		foreach ( $fields as $field => $sanitize_function ) {
			if ( isset( $_POST[ $field ] ) ) {

                // Unsanitized value
                $unsanitized_value = wp_unslash( $_POST[ $field ] ); // phpcs:ignore 

                // Arrays
                if ( $sanitize_function == 'mixed' ) {
                    if ( is_array( $unsanitized_value ) ) {
                        $value = filter_var_array( $unsanitized_value, FILTER_SANITIZE_FULL_SPECIAL_CHARS ); // phpcs:ignore 
                        $value = implode( ', ', $value );
                    } else {
                        $value = sanitize_text_field( $unsanitized_value );
                    }

                // Booleans
                } elseif ( $sanitize_function == 'boolean' ) {
                    $value = filter_var( $unsanitized_value, FILTER_VALIDATE_BOOLEAN );

                // Specific
                } else {
                    $value = $sanitize_function( $unsanitized_value );
                }

				update_post_meta( $post_id, $field, $value );
                
			} elseif ( $sanitize_function == 'boolean' ) {
                update_post_meta( $post_id, $field, false );
            }
		}

        // Handle Buttons
        $buttons = isset( $_POST[ $this->meta_key_buttons ] ) ? filter_var_array( wp_unslash( $_POST[ $this->meta_key_buttons ] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : []; // phpcs:ignore 
        $filtered_buttons = [];
        foreach ( $buttons as $button ) {
            if ( $button[ 'text' ] ) {
                $filtered_buttons[] = $button;
            }
        }
        update_post_meta( $post_id, $this->meta_key_buttons, $filtered_buttons );

        // Handle Start Date/Time
        $start_date_date = isset( $_POST[ $this->meta_key_start_date . '_date' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->meta_key_start_date . '_date' ] ) ) : '';
        $start_date_time = isset( $_POST[ $this->meta_key_start_date . '_time' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->meta_key_start_date . '_time' ] ) ) : '00:00:00';

        $start_timestamp = '';
        if ( $start_date_date ) {
            $combined_datetime = $start_date_date . ' ' . $start_date_time;
            $start_timestamp = strtotime( $combined_datetime );
        }
        update_post_meta( $post_id, $this->meta_key_start_date, $start_timestamp );

        // Handle End Date/Time (similar logic)
        $end_date_date = isset( $_POST[ $this->meta_key_end_date . '_date' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->meta_key_end_date . '_date' ] ) ) : '';
        $end_date_time = isset( $_POST[ $this->meta_key_end_date . '_time' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->meta_key_end_date . '_time' ] ) ) : '23:59:59';

        $end_timestamp = '';
        if ( $end_date_date ) {
            $combined_datetime = $end_date_date . ' ' . $end_date_time;
            $end_timestamp = strtotime( $combined_datetime );
        }
        update_post_meta( $post_id, $this->meta_key_end_date, $end_timestamp );
    } // End save_post()


    /**
     * Meta box interactions
     *
     * @param object $post
     * @return void
     */
    public function meta_box_interactions( $post ) {
        $notice_id = $post->ID;
        $dismissals = absint( get_post_field( $this->meta_key_dismissals, $notice_id ) );

        // Dismissals
        ?>
        <div id="dismissals">
            <h3><?php echo esc_html__( 'Total Dismissals', 'user-notices' ); ?>: <?php echo esc_attr( $dismissals ); ?></h3>
        </div>
        <?php

        // Actions
        $total_actions = 0;

        $actions_taken = [];
        $actions = get_post_meta( $notice_id, $this->meta_key_actions, true );
        if ( $actions ) {
            $actions = filter_var_array( $actions, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            foreach ( $actions as $text => $count ) {
                $actions_taken[] = '<strong>' . $text . ':</strong> ' . $count;
                $total_actions += $count;
            }
        }

        if ( !empty( $actions ) ) {
            ?>
            <div id="actions">
                <h3><?php echo esc_html__( 'Actions Taken', 'user-notices' ); ?>: <?php echo esc_attr( $total_actions ); ?></h3>
                <?php
                if ( !empty( $actions_taken ) ) {
                    echo wp_kses( implode( '<br>', $actions_taken ), [ 'strong' => [], 'br' => [] ] );
                }
                ?>
            </div>
            <?php
        }
    } // End meta_box_interactions()

    
    /**
     * Admin columns
     *
     * @param array $columns
     * @return array
     */
    public function admin_columns( $columns ) {
        $columns[ 'un_site_location' ]   = __( 'Site Location', 'user-notices' );
        $columns[ 'un_screen_location' ] = __( 'Screen Location', 'user-notices' );
        $columns[ 'un_audience' ]        = __( 'Audience', 'user-notices' );
        $columns[ 'un_schedule' ]        = __( 'Scheduled', 'user-notices' );
        $columns[ 'un_actions_taken' ]   = __( 'Actions Taken', 'user-notices' );
        return $columns;
    } // End admin_columns()


    /**
     * Admin column content
     *
     * @param string $column
     * @param int $post_id
     * @return void
     */
    public function admin_column_content( $column, $post_id ) {
        // Site Location
        if ( 'un_site_location' === $column ) {
            $site_location_type = sanitize_key( get_post_meta( $post_id, $this->meta_key_site_location_type, true ) );
            $site_location_type_label = isset( $this->site_locations[ $site_location_type ] ) ? $this->site_locations[ $site_location_type ] : $site_location_type;

            $site_location_req = [ 'post_types', 'ids' ];
            $sep = in_array( $site_location_type, $site_location_req ) ? ' =><br>' : '';

            $site_location = sanitize_text_field( get_post_meta( $post_id, $this->meta_key_site_location, true ) );
            if ( strpos( $site_location, ',' ) === false ) {
                $site_location = explode( ', ', $site_location );
            }
            
            $site_location_labels = '';
            if ( !empty( $site_location ) ) {
                $site_location_labels_array = [];
                if ( $site_location_type == 'post_types' ) {
                    foreach ( $site_location as $post_type ) {
                        $obj = get_post_type_object( $post_type );
                        $site_location_labels_array[] = sanitize_text_field( $obj->labels->name );
                    }
                } elseif ( $site_location_type == 'ids' ) {
                    foreach ( $site_location as $id ) {
                        $site_location_labels_array[] = get_the_title( $id ) . ' (#' . $id . ')';
                    }
                }
                
                if ( !empty( $site_location_labels_array ) ) {
                    $site_location_labels = implode( ', ', $site_location_labels_array );
                }
            }
            
            echo wp_kses_post( $site_location_type_label . $sep . $site_location_labels );
        }

        // Screen Location
        if ( 'un_screen_location' === $column ) {
            $screen_location_type = sanitize_key( get_post_meta( $post_id, $this->meta_key_screen_location_type, true ) );
            $screen_location_type_label = isset( $this->screen_locations[ $screen_location_type ] ) ? $this->screen_locations[ $screen_location_type ] : $screen_location_type;

            echo esc_html( $screen_location_type_label );
        }

        // Audience
        if ( 'un_audience' === $column ) {
            $audience_type = sanitize_key( get_post_meta( $post_id, $this->meta_key_audience_type, true ) );
            $audience_type_label = isset( $this->audience_types[ $audience_type ] ) ? $this->audience_types[ $audience_type ] : $audience_type;

            $sep = $audience_type == 'user' ? ' =><br>' : '';

            $audience_labels = '';
            if ( $audience_type == 'user' ) {
                $audience = sanitize_text_field( get_post_meta( $post_id, $this->meta_key_audience, true ) );
                if ( strpos( $audience, ',' ) === false ) {
                    $audience = explode( ',', $audience );
                    $audience = array_map( 'trim', $audience );

                    $ip_lookup_url = 'https://www.criminalip.io/asset/report/{ip}';
                    $ip_lookup_url = sanitize_text_field( apply_filters( 'usernotices_ip_lookup_url', $ip_lookup_url ) );
                    
                    $audience_array = [];
                    foreach ( $audience as $a ) {
                        if ( is_numeric( $a ) ) {
                            if ( $user = get_userdata( $a ) ) {
                                $audience_array[] = $user->display_name . ' (#' . $a . ')';
                            } else {
                                $audience_array[] = __( 'Unknown User', 'user-notices' ) . ' (#' . $a . ')';
                            }
                        } elseif ( is_email( $a ) ) {
                            if ( $user = get_user_by( 'email', $a ) ) {
                                $audience_array[] = $user->display_name . ' (' . $a . ')';
                            } else {
                                $audience_array[] = __( 'Unknown User', 'user-notices' ) . ' (' . $a . ')';
                            }
                        } elseif ( filter_var( $a, FILTER_VALIDATE_IP ) !== false ) {
                            $audience_array[] = '<a href="' . str_replace( '{ip}', $a, $ip_lookup_url ) . '" target="_blank">' . __( 'IP: ', 'user-notices' ) . $a . '</a>';
                        }
                    }

                    if ( !empty( $audience_array ) ) {
                        $audience_labels = implode( ', ', $audience_array );
                    }
                }
            }

            echo esc_html( $audience_type_label . $sep . $audience_labels );
        }

        // Schedule
        if ( 'un_schedule' === $column ) {
            $format = 'F j, Y g:i A';

            $start_date = sanitize_text_field( get_post_meta( $post_id, $this->meta_key_start_date, true ) );
            if ( $start_date ) {
                $start_date = gmdate( $format, $start_date );
            }

            $end_date = sanitize_text_field( get_post_meta( $post_id, $this->meta_key_end_date, true ) );
            if ( $end_date ) {
                $end_date = gmdate( $format, $end_date );
            }

            if ( $start_date && !$end_date ) {
                $label = __( 'Starts: ', 'user-notices' ) . $start_date;
            } elseif ( !$start_date && $end_date ) {
                $label = __( 'Ends: ', 'user-notices' ) . $end_date;
            } elseif ( $start_date && $end_date ) {
                $label = $start_date . ' - ' . $end_date;
            } else {
                $label = __( 'No', 'user-notices' );
            }

            echo esc_html( $label );
        }

        // Action Taken
        if ( 'un_actions_taken' === $column ) {
            $actions_taken = [];

            $dismissals = absint( get_post_meta( $post_id, $this->meta_key_dismissals, true ) );
            $actions_taken[] = '<strong>' . __( 'Dismissals', 'user-notices' ) . ':</strong> ' . $dismissals;

            $actions = get_post_meta( $post_id, $this->meta_key_actions, true );
            if ( $actions ) {
                $actions = filter_var_array( $actions, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                foreach ( $actions as $text => $count ) {
                    $actions_taken[] = '<strong>' . $text . ':</strong> ' . $count;
                }
            }

            if ( !empty( $actions_taken ) ) {
                echo wp_kses( implode( '<br>', $actions_taken ), [ 'strong' => [], 'br' => [] ] );
            } else {
                echo 0;
            }
        }
    } // End admin_column_content()


    /**
     * Adds a button to clear all user records on the profile edit screen.
     *
     * @param WP_User $user The user object.
     */
    public function user_clear_records_button( $user ) {
        // Clear the records
        if ( isset( $_GET[ 'user_clear_records' ] ) && isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), $this->user_nonce ) && current_user_can( 'edit_user' ) )  {
            $user_id = absint( $_GET[ 'user_clear_records' ] );

            $database = new Database();
            $database->delete_user_records( $user_id );

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'All user notice records have been cleared.', 'user-notices' ) . '</p></div>';
            
            ?>
            <script>
            document.addEventListener( 'DOMContentLoaded', function () {
                if ( window.location.search.includes( 'user_clear_records=' ) || window.location.search.includes( '_wpnonce=' ) ) {
                    const url = new URL( window.location.href );
                    url.searchParams.delete( 'user_clear_records' );
                    url.searchParams.delete( '_wpnonce' );
                    window.history.replaceState( null, '', url.toString() );
                }
            } ) ;
            </script>
            <?php
        }
        
        // Add the button
        $url = add_query_arg( [
            'user_clear_records' => $user->ID,
            '_wpnonce'           => wp_create_nonce( $this->user_nonce ),
        ] );
        ?>
        <h2><?php echo esc_attr( USERNOTICES_NAME ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Interactions', 'user-notices' ); ?></th>
                <td>
                    <a href="<?php echo esc_url( $url ); ?>" class="button button-secondary">
                        <?php esc_html_e( 'Clear All User Logs', 'user-notices' ); ?>
                    </a>
                </td>
            </tr>
        </table>
        <?php
    } // End user_clear_records_button()


    /**
     * Get an array of post types with labels
     *
     * @return array
     */
    public function get_post_types_with_labels() {
        // Post types
        $post_page = [
            'post' => 'post',
            'page' => 'page'
        ];
        $post_types = get_post_types( [ '_builtin' => false ] );
        $post_types = array_merge( $post_page, $post_types );

        $pts = [];
        foreach ( $post_types as $pt ) {
            $obj = get_post_type_object( $pt );

            // Skip if post type does not generate and allow a UI for managing the post type in the admin
            if ( !$obj->show_ui ) {
                continue;
            }

            if ( $obj ) {
                $name = sanitize_text_field( $obj->labels->name );
            } else {
                $name = $pt;
            }
            $pts[ $pt ] = $name;
        }

        return filter_var_array( apply_filters( 'usernotices_post_types', $pts ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
    } // End get_post_types_with_labels()


    /**
     * Get an array of roles with labels
     *
     * @return array
     */
    public function get_roles_with_labels() {
        global $wp_roles;

        $roles = [];
        if ( !empty( $wp_roles->roles ) && is_array( $wp_roles->roles ) ) {
            foreach ( $wp_roles->roles as $role => $details ) {
                $roles[ $role ] = translate_user_role( $details[ 'name' ] );
            }
        }

        return filter_var_array( apply_filters( 'usernotices_roles', $roles ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
    } // End get_roles_with_labels()


    /**
     * Actions performed when dismissing a notice
     *
     * @param int $notice_id
     * @return void
     */
    public function dismiss_notice( $notice_id ) {
        // Post
        $post_dismissals = absint( get_post_meta( $notice_id, $this->meta_key_dismissals, true ) );
        $post_dismissals++;
        update_post_meta( $notice_id, $this->meta_key_dismissals, $post_dismissals );

        // User
        $user_id = get_current_user_id();
        (new Database())->add_record( $user_id, $notice_id, 'dismiss' );

        // If not logged in, also log session
        if ( !$user_id ) {
            if ( !session_id() ) {
                session_start();
            }

            // Sanitize the session data if it exists
            if ( isset( $_SESSION[ $this->session_key_dismissals ] ) ) {
                $user_dismissals = filter_var_array( $_SESSION[ $this->session_key_dismissals ], FILTER_SANITIZE_FULL_SPECIAL_CHARS ); // phpcs:ignore 
            } else {
                $user_dismissals = [];
            }

            $user_dismissals[ $notice_id ] = time();
            $_SESSION[ $this->session_key_dismissals ] = $user_dismissals;
        }

        // Hook
        do_action( 'usernotices_after_dismissing_notice', $notice_id, $user_id );
    } // End dismiss_notice()


    /**
     * Log an action
     *
     * @param int $notice_id
     * @return void
     */
    public function log_action( $notice_id, $action ) {
        // Post
        $post_actions = get_post_meta( $notice_id, $this->meta_key_actions, true );
        if ( $post_actions ) {
            $post_actions = filter_var_array( $post_actions, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        } else {
            $post_actions = [];
        }

        if ( isset( $post_actions[ $action ] ) ) {
            $post_actions[ $action ]++;
        } else {
            $post_actions[ $action ] = 1;
        }

        update_post_meta( $notice_id, $this->meta_key_actions, $post_actions );

        // User
        $user_id = get_current_user_id();
        (new Database())->add_record( $user_id, $notice_id, 'action', $action );

        // If not logged in, also log session
        if ( !$user_id ) {
            if ( !session_id() ) {
                session_start();
            }
    
            // Sanitize the session data if it exists
            if ( isset( $_SESSION[ $this->session_key_actions ] ) ) {
                $user_actions = filter_var_array( $_SESSION[ $this->session_key_actions ], FILTER_SANITIZE_FULL_SPECIAL_CHARS ); // phpcs:ignore 
            } else {
                $user_actions = [];
            }
    
            $user_actions[ $notice_id ][ $action ] = time();
            $_SESSION[ $this->session_key_actions ] = $user_actions;
        }

        // Hook
        do_action( 'usernotices_after_logging_action', $notice_id, $action, $user_id );
    } // End log_action()


    /**
     * Meta key save
     *
     * @param string $meta_key
     * @param int|null $user_id
     * @return void
     */
    public function save_metakey( $notice_id, $action, $meta_key, $user_id = null ) {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        if ( $user_id ) {
            update_user_meta( $user_id, $meta_key, TRUE );
        }

        // Hook
        do_action( 'usernotices_after_saving_metakey', $notice_id, $action, $meta_key, $user_id );
    } // End save_metakey()


    /**
     * Enqueue scripts
     *
     * @param string $screen
     * @return void
     */
    public function enqueue_scripts( $screen ) {
        if ( ( $screen == 'post-new.php' || $screen == 'post.php' ) && get_post_type() == $this->post_type ) {
            $post_id = get_the_ID();
            $site_location = sanitize_text_field( get_post_meta( $post_id, $this->meta_key_site_location, true ) );
            $audience = sanitize_text_field( get_post_meta( $post_id, $this->meta_key_audience, true ) );

            // Jquery
            $js_handle = USERNOTICES_TEXTDOMAIN.'-edit-script';
            wp_enqueue_script( 'jquery' );
            wp_register_script( $js_handle, USERNOTICES_JS_PATH . 'edit.js', [ 'jquery' ], time(), true );
            wp_localize_script( $js_handle, 'user_notices_edit', [
                'notice_id'            => $post_id,
                'site_location'        => $site_location,
                'audience'             => $audience,
                'roles'                => $this->get_roles_with_labels(),
                'audience_labels'      => [ 
                    'roles'      => __( 'Choose one or more roles', 'user-notices' ),
                    'users'      => __( 'Enter user IDs, email addresses and/or IP addresses separated by commas', 'user-notices' ),
                ],
                'post_types'           => $this->get_post_types_with_labels(),
                'site_location_labels' => [ 
                    'post_types' => __( 'Choose one or more post types', 'user-notices' ),
                    'ids'        => __( 'Enter post IDs separated by commas (from any public post type)', 'user-notices' ),
                    'sc_title'   => __( 'Click to copy shortcode to your clipboard', 'user-notices' ),
                    'sc_label'   => __( 'Use the following shortcode where you want to display it. You can click it to copy it to your clipboard. Set the <code>force_display</code> parameter to <code>true</code> to override other conditions during testing only, then set it to <code>false</code> or remove it altogether.', 'user-notices' ),
                    'sc_copied'  => __( 'Shortcode has been copied to clipboard.', 'user-notices' ),
                ],
                'merge_tags'           => $this->merge_tags
            ] );
            wp_enqueue_script( $js_handle );

            // CSS
            $css_handle = USERNOTICES_TEXTDOMAIN . '-edit-style';
            wp_register_style( $css_handle, USERNOTICES_CSS_PATH . 'edit.css', [], time() );
            wp_enqueue_style( $css_handle );
        }
    } // End enqueue_scripts()

}