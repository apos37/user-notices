<?php 
/**
 * Notices on the front end
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserNotices;
use PluginRx\UserNotices\License;
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
    (new DisplayNotice())->init();
} );


/**
 * The class
 */
class DisplayNotice {

    /**
     * Nonces
     *
     * @var string
     */
	private $nonce = 'usernotices_nonce';


    /**
     * Load on init, but not every time the class is called
     *
     * @return void
     */
    public function init() {

        // Check license validity
        if ( !(new License())->has_been_validated() ) {
            return;
        }

        // Display any user notices
        add_action( 'wp_footer', [ $this, 'display_notices' ] );

        // Shortcode
        add_shortcode( 'user_notice', [ $this, 'create_shortcode_notice' ] );

        // Ajax
		add_action( 'wp_ajax_usernotices_dismiss', [ $this, 'ajax_dismiss' ] );
        add_action( 'wp_ajax_nopriv_usernotices_dismiss', [ $this, 'ajax_dismiss' ] );
        add_action( 'wp_ajax_usernotices_actions', [ $this, 'ajax_actions' ] );
        add_action( 'wp_ajax_nopriv_usernotices_actions', [ $this, 'ajax_actions' ] );

        // Enqueue scripts
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

    } // End init()


    /**
     * Get the notice data
     *
     * @param int $notice_id
     * @return array|false
     */
    public function get_notice( $notice_id ) {
        if ( $notice = get_post( $notice_id ) ) {

            $notice_array = [
                'ID'      => $notice_id,
                'title'   => $notice->post_title,
                'content' => get_the_content( null, false, $notice ),
                'status'  => $notice->post_status
            ];

            $NOTICE = new Notices();
            $keys = [
                $NOTICE->meta_key_buttons               => 'array',
                $NOTICE->meta_key_site_location_type    => 'sanitize_key',
                $NOTICE->meta_key_site_location         => 'sanitize_text_field',
                $NOTICE->meta_key_screen_location_type  => 'sanitize_key',
                $NOTICE->meta_key_audience_type         => 'sanitize_key',
                $NOTICE->meta_key_audience              => 'sanitize_text_field',
                $NOTICE->meta_key_start_date            => 'sanitize_text_field',
                $NOTICE->meta_key_end_date              => 'sanitize_text_field',
                $NOTICE->meta_key_dismissable           => 'boolean',
                $NOTICE->meta_key_hide_after_action     => 'boolean',
                $NOTICE->meta_key_delay                 => 'absint',
                $NOTICE->meta_key_show_user_actions     => 'boolean',
                $NOTICE->meta_key_debug                 => 'boolean',
                $NOTICE->meta_key_bg_color              => 'sanitize_hex_color',
            ];

            foreach ( $keys as $key => $sanitize_function ) {
                $unsanitized_value = get_post_meta( $notice_id, $key, true );
                
                if ( $sanitize_function == 'array' ) {
                    $value = filter_var_array( $unsanitized_value, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                } elseif ( $sanitize_function == 'boolean' ) {
                    $value = filter_var( $unsanitized_value, FILTER_VALIDATE_BOOLEAN );
                } else {
                    $value = $sanitize_function( $unsanitized_value );
                }

                $notice_array[ $key ] = $value;
            }

            $notice_array = filter_var_array( apply_filters( 'usernotices_notice_args', $notice_array, $notice_id ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            return $notice_array;
        }
        return false;
    } // End get_notice()


    /**
     * Check if we should show the notice
     *
     * @param array $notice
     * @return boolean
     */
    public function should_show_notice( $notice ) {
        $NOTICES = new Notices();
        $user_id = get_current_user_id();

        // Check site location
        $site_location_type = $notice[ $NOTICES->meta_key_site_location_type ];
        if ( $site_location_type != 'all' ) {

            // Homepage
            if ( $site_location_type == 'homepage' && !( is_front_page() || is_home() ) ) {
                return __( 'The notice is set for the homepage only.', 'user-notices' );
            }

            // Otherwise, we must be more specific
            if ( $site_location = $notice[ $NOTICES->meta_key_site_location ] ) {

                // Convert to array
                $site_location_array = explode( ',', $site_location );
                $site_location_array = array_map( 'trim', $site_location_array );
                
                $post_type = get_post_type();

                // Post types only
                if ( $site_location_type == 'post_types' && !in_array( $post_type, $site_location_array ) ) {
                    return __( 'The notice is for other post types: ', 'user-notices' ) . $site_location . '.';

                // Specific pages
                } elseif ( $site_location_type == 'ids' && !in_array( get_the_ID(), $site_location_array ) ) {
                    return __( 'The notice is set for specific posts or pages, and this is not one of them.', 'user-notices' );
                }
            }
        }

        // Check audience
        $audience_type = $notice[ $NOTICES->meta_key_audience_type ];
        if ( $audience_type != 'everyone' ) {

            // Logged-in/out
            if ( $audience_type == 'logged_in' && !is_user_logged_in() ) {
                return __( 'User is logged out, but the notice is set to only display for logged-in users.', 'user-notices' );
            } elseif ( $audience_type == 'logged_out' && is_user_logged_in() ) {
                return __( 'User is logged in, but the notice is set to only display for logged-out users.', 'user-notices' );
            }

            // Otherwise, we must be more specific
            if ( $audience = $notice[ $NOTICES->meta_key_audience ] ) {

                // Get the current user
                $user = wp_get_current_user();

                // Convert to array
                $audience_array = explode( ',', $audience );
                $audience_array = array_map( 'trim', $audience_array );
            
                // Roles
                if ( $audience_type == 'roles' ) {

                    $user_roles = $user->roles;
                    $has_role = false;
                    foreach ( $user_roles as $role ) {
                        if ( in_array( $role, $audience_array ) ) {
                            $has_role = true;
                            break;
                        }
                    }

                    if ( !$has_role ) {
                        return __( 'User does not have correct role.', 'user-notices' );
                    }
                
                // Specific users
                } else {

                    $is_a_user = false;
                    foreach ( $audience_array as $user_identifier ) {

                        // Check IDs
                        if ( is_numeric( $user_identifier ) && $user_identifier == get_current_user_id() ) {
                            $is_a_user = true;
                            break;

                        // Check emails
                        } elseif ( is_email( $user_identifier ) && $user_identifier == $user->user_email ) {
                            $is_a_user = true;
                            break;

                        // Check IP addresses
                        } elseif ( filter_var( $user_identifier, FILTER_VALIDATE_IP ) !== false && $user_identifier == (new Helpers())->get_user_ip() ) {
                            $is_a_user = true;
                            break;
                        }
                    }

                    if ( !$is_a_user ) {
                        return __( 'Incorrect user.', 'user-notices' );
                    }
                }
            }
        }

        // Check dates
        $start_date = $notice[ $NOTICES->meta_key_start_date ];
        $end_date = $notice[ $NOTICES->meta_key_end_date ];

        if ( !empty( $start_date ) || !empty( $end_date ) ) {
            $now = strtotime( (new Helpers())->convert_timezone() );
            if ( ( !empty( $start_date ) && $now < $start_date ) ||
                 ( !empty( $end_date ) && $now > $end_date ) ) {
                return __( 'Not scheduled.', 'user-notices' );
            }
        }

        // Conditions
        $is_dismissable = filter_var( $notice[ $NOTICES->meta_key_dismissable ], FILTER_VALIDATE_BOOLEAN );
        $hide_after_action = filter_var( $notice[ $NOTICES->meta_key_hide_after_action ], FILTER_VALIDATE_BOOLEAN );

        // Check if user is logged in
        if ( $user_id ) {

            $DATABASE = new Database();

            if ( $is_dismissable ) {
                $dismissal_record = $DATABASE->get( $user_id, null, $notice[ 'ID' ], 'dismiss' );
                if ( !empty( $dismissal_record ) ) {
                    return __( 'User dismissed the notice.', 'user-notices' );
                }
            }
            
            if ( $hide_after_action ) {
                $action_record = $DATABASE->get( $user_id, null, $notice[ 'ID' ], 'action' );
                if ( !empty( $action_record ) ) {
                    return __( 'User took action and notice is hidden after actions taken.', 'user-notices' );
                }
            }

        // User is logged out, check session
        } else {

            if ( !session_id() ) {
                session_start();
            }

            if ( $is_dismissable && isset( $_SESSION[ $NOTICES->session_key_dismissals ] ) ) {
                $dismissals = filter_var_array( $_SESSION[ $NOTICES->session_key_dismissals ], FILTER_SANITIZE_FULL_SPECIAL_CHARS ); // phpcs:ignore 
                if ( isset( $dismissals[ $notice[ 'ID' ] ] ) ) {
                    return __( 'User dismissed the notice.', 'user-notices' );
                }
            }

            if ( $hide_after_action && isset( $_SESSION[ $NOTICES->session_key_actions ] ) ) {
                $actions = filter_var_array( $_SESSION[ $NOTICES->session_key_actions ], FILTER_SANITIZE_FULL_SPECIAL_CHARS ); // phpcs:ignore 
                if ( isset( $actions[ $notice[ 'ID' ] ] ) ) {
                    return __( 'User took action and notice is hidden after actions taken.', 'user-notices' );
                }
            }
        }

        // Default
        return true;
    } // End should_show_notice()


    /**
     * Print an error message just for admins
     *
     * @param string $string
     * @return string
     */
    public function admin_error( $string ) {
        if ( current_user_can( 'administrator' ) ) {
            return '<div class="user-notice-admin-error">' . $string . '</div>';
        }
    } // End admin_error()


    /**
     * Swap out the merge tags for values dynamically
     *
     * @param int $notice_id
     * @param string $content
     * @return string
     */
    public function swap_out_merge_tags( $notice_id, $content ) {
        if ( empty( $content ) ) {
            return $content;
        }

        global $post, $current_user;
        wp_get_current_user();

        $NOTICES = new Notices();
        $default_date_format = $NOTICES->default_date_format;
        $default_time_format = $NOTICES->default_time_format;
        $HELPERS = new Helpers();        

        $content = preg_replace_callback( '/\{([a-zA-Z_]+):([a-zA-Z0-9_]+)(?::([^}]+))?\}/', function( $matches ) use ( $post, $current_user, $notice_id, $default_date_format, $default_time_format, $NOTICES, $HELPERS ) {
            $group = $matches[1];
            $key = $matches[2];
            $format = isset( $matches[3] ) ? $matches[3] : '';

            /**
             * USER AND POST META
             */

            if ( $group === 'user' ) {
                if ( $key === 'user_registered' ) {
                    $user_registered = strtotime( $current_user->$key );
                    $format = isset( $matches[3] ) ? $matches[3] : $default_date_format;
                    return $user_registered ? gmdate( $format, $user_registered ) : 'N/A';
                }
                return isset( $current_user->$key ) ? $current_user->$key : get_user_meta( $current_user->ID, $key, true );
            }

            if ( $group === 'post' ) {
                if ( $key === 'post_author' ) {
                    return get_the_author_meta( 'display_name', $post->$key );
                } elseif ( $key === 'post_date' || $key === 'modified_date' ) {
                    $timestamp = strtotime( $post->$key );
                    $format = isset( $matches[3] ) ? $matches[3] : $default_date_format;
                    return $timestamp ? gmdate( $format, $timestamp ) : 'N/A';
                } elseif ( $key === 'post_type' ) {
                    $post_type_object = get_post_type_object( $post->post_type );
                    return $post_type_object ? $post_type_object->labels->singular_name : 'N/A';
                } elseif ( $key === 'post_status' ) {
                    $post_status_object = get_post_status_object( $post->post_status );
                    return $post_status_object ? $post_status_object->label : 'N/A';
                }
                return isset( $post->$key ) ? $post->$key : get_post_meta( $post->ID, $key, true );
            }


            /**
             * DATE AND TIME
             */

            if ( $group !== 'date' && $group !== 'time' ) {
                return $matches[0];
            }

            if ( empty( $format ) ) {
                $format = ( $group === 'time' ) ? $default_time_format : $default_date_format;
            }

            if ( $key === 'current' ) {
                return $HELPERS->convert_timezone( null, $format );
            }

            if ( in_array( $key, [ 'start', 'end' ], true ) ) {
                $timestamp_key = $key === 'start' ? $NOTICES->meta_key_start_date : $NOTICES->meta_key_end_date;
                $timestamp = get_post_meta( $notice_id, $timestamp_key, true );

                return $timestamp ? gmdate( $format, $timestamp ) : 'N/A';
            }

            return $matches[0];
        }, $content );

        // Return the content
        return $content;
    } // End swap_out_merge_tags()


    /**
     * Shortcode
     * USAGE: [user_notice id="" force_display="true"]
     *
     * @param array $atts
     * @return string|false
     */
    public function create_shortcode_notice( $atts ) {
        if ( (new Helpers())->dont_run_shortcode_on_edit_screen() ) return '';

        $atts = shortcode_atts( [ 
            'id'            => 0,
            'force_display' => false
        ], $atts );
        $notice_id = absint( $atts[ 'id' ] );
        if ( !$notice_id ) {
            return $this->admin_error( __( 'Shortcode must include an id="" parameter with the Notice ID.', 'user-notices' ) );
        }

        if ( $notice = $this->get_notice( $notice_id ) ) {
            if ( $notice[ 'status' ] != 'publish' ) {
                return $this->admin_error( __( 'Notice is not published. Status:', 'user-notices' ) . ' <code>' . $notice[ 'status' ] . '</code>.' );
            }

            $NOTICES = new Notices();

            if ( $notice[ $NOTICES->meta_key_site_location_type ] != 'shortcode' ) {
                return false;
            }

            // Check if we should show the notice
            $force_display = sanitize_key( $atts[ 'force_display' ] );
            if ( $force_display !== 'true' ) {
                $should_show_notice = $this->should_show_notice( $notice );
                $should_show_notice = sanitize_text_field( apply_filters( 'usernotices_show_notice', $should_show_notice, $notice ) );
                if ( $should_show_notice != 1 ) {
                    $debugging = filter_var( $notice[ $NOTICES->meta_key_debug ], FILTER_VALIDATE_BOOLEAN );
                    if ( $debugging ) {
                        echo '<script> console.error( "Notice #' . esc_attr( $notice_id ) . ' | ' . esc_html( $should_show_notice) . '" ); </script>';
                    }
                    return false;
                }
            }

            // Buttons
            $buttons = $notice[ $NOTICES->meta_key_buttons ];

            // Start the styles
            $styles = [];

            // Background and padding
            $bg_color = $notice[ $NOTICES->meta_key_bg_color ];
            $styles[ 'background-color' ] = ( $bg_color && $bg_color != '' ) ? $bg_color : '#FFFFFF';

            // Delay
            $delay = $notice[ $NOTICES->meta_key_delay ];
            $delay = ( $delay && $delay != '' ) ? $delay : false;

            // Instantiate Helpers
            $HELPERS = new Helpers();

            // Decode the content
            $content = html_entity_decode( $notice[ 'content' ] );
            $content = wp_kses( apply_filters( 'usernotices_content', $content, $notice ), $HELPERS->allowed_html() );
            $content = do_shortcode( $content );
            $content = $this->swap_out_merge_tags( $notice_id, $content );

            $font_color = $HELPERS->get_contrast_color( $bg_color );

            // Complete the styles
            $styles_array = filter_var_array( apply_filters( 'usernotices_styles', $styles, 'shortcode', $notice ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
            $style = '';
            foreach ( $styles_array as $key => $value ) {
                $style .= $key . ': ' . $value . '; ';
            }
            $style = trim( $style );

            // Dismiss button
            $dismiss_btn = $notice[ 'dismissable' ] ? '<div class="usernotice-dismiss" data-id="' . $notice_id . '" style="position: absolute; top: 5px; right: 10px; color: ' . $font_color . '; font-size: 1.5rem; cursor: pointer;">&times;</div>' : '';
                
            // Other action buttons
            if ( !empty( $buttons ) ) {
                $button_links = [];
                foreach ( $buttons as $button ) {
                    $url = $button[ 'url' ] && $button[ 'url' ] != '' ? $button[ 'url' ] : '#';
                    $button_links[] = '<a href="' . $url . '" class="usernotice-btn" data-id="' . $notice_id . '" data-metakey="' . $button[ 'metakey' ] . '" style="background-color: ' . $button[ 'bgcolor' ] . '; color: ' . $button[ 'color' ] . ';">' . $button[ 'text' ] . '</a>';
                }
                $incl_btns = implode( ' ', $button_links );
            } else {
                $incl_btns = '';
            }

            // Extra classes
            $show_class = !$delay ? ' show' : '';

            // Return it
            $results = '<div id="user-notice-' . esc_attr( $notice_id ) . '" class="user-notice shortcode' . esc_attr( $show_class ) . '" data-id="' . esc_html( $notice_id) . '" style="' . esc_html( $style ) . '">
                ' . wp_kses_post( $dismiss_btn ) . '
                <div class="usernotice-content">
                    <div class="usernotice-text">' . wp_kses( $content, $HELPERS->allowed_html() ) . '</div>
                    <div class="usernotice-buttons">' . wp_kses_post( $incl_btns ) . '</div>
                </div>
            </div>';
            
            // Delay
            if ( $delay ) {
                $results .= '<script id="user-notice-delay-' . esc_attr( $notice_id ) . '">
                    document.addEventListener( "DOMContentLoaded", function() {
                        var notice = document.getElementById( "user-notice-' . absint( $notice_id ) . '" );
                        if ( notice ) {
                            setTimeout( function() {
                                notice.classList.add( "show" );
                            }, ' . $delay . '000 );
                        }
                    } ) ;
                </script>';
            }

            return $results;

        } else {
            /* translators: %d: Notice ID */
            return $this->admin_error( sprintf( __( 'Notice with ID %d does not exist.', 'user-notices' ), 
                $notice_id 
            ) );
        }
        
    } // End create_shortcode_notice()


    /**
     * Create the notice, ready for display
     *
     * @param int $notice_id
     * @return string|false
     */
    public function create_footer_notice( $notice_id, $force_display = false ) {
        if ( $notice = $this->get_notice( $notice_id ) ) {
            $NOTICES = new Notices();

            // Check if we should show the notice
            if ( !$force_display ) {
                $should_show_notice = $this->should_show_notice( $notice );
                $should_show_notice = sanitize_text_field( apply_filters( 'usernotices_show_notice', $should_show_notice, $notice ) );
                if ( $should_show_notice != 1 ) {
                    $debugging = filter_var( $notice[ $NOTICES->meta_key_debug ], FILTER_VALIDATE_BOOLEAN );
                    if ( $debugging ) {
                        echo '<script> console.error( "Notice #' . esc_attr( $notice_id ) . ' | ' . esc_html( $should_show_notice) . '" ); </script>';
                    }
                    return false;
                }
            }

            // Screen location
            $screen_location_type = $notice[ 'screen_location_type' ];

            // Buttons
            $buttons = $notice[ $NOTICES->meta_key_buttons ];

            // Start the styles
            $styles = [];

            // Background and padding
            $bg_color = $notice[ $NOTICES->meta_key_bg_color ];
            if ( $screen_location_type == 'console' && $bg_color ) {
                $styles[ 'background-color' ] = $bg_color;
                $styles[ 'border-radius' ]    = '5px';
                $styles[ 'padding' ]          = '10px 20px';
            } else {
                $styles[ 'background-color' ] = ( $bg_color && $bg_color != '' ) ? $bg_color : '#FFFFFF';
            }

            // Delay
            $delay = $notice[ $NOTICES->meta_key_delay ];
            $delay = ( $delay && $delay != '' ) ? $delay : false;

            // Instantiate Helpers
            $HELPERS = new Helpers();

            // Decode the content
            $content = html_entity_decode( $notice[ 'content' ] );
            $content = wp_kses( apply_filters( 'usernotices_content', $content, $notice ), $HELPERS->allowed_html() );
            $content = do_shortcode( $content );
            $content = $this->swap_out_merge_tags( $notice_id, $content );

            // If not an alert or console
            if ( $screen_location_type != 'alert' ) {
                $font_color = $HELPERS->get_contrast_color( $bg_color );

                // Console strip
                if ( $screen_location_type == 'console' ) {
                
                    // Extract styles and formatting
                    preg_match_all( '/<(em|strong|b|span)([^>]*)>/i', $content, $matches, PREG_SET_ORDER );
                
                    foreach ( $matches as $match ) {
                        $tag = strtolower( $match[1] );
                        $attributes = $match[2];
                
                        switch ( $tag ) {
                            case 'em':
                                $styles[ 'font-style' ] = 'italic';
                                break;
                            case 'strong':
                            case 'b':
                                $styles[ 'font-weight' ] = 'bold';
                                break;
                            case 'span':
                                if ( preg_match( '/color:\s*(#[a-f0-9]{3,6}|rgba?\(.*?\));?/i', $attributes, $color_match ) ) {
                                    $styles[ 'color' ] = $color_match[1];
                                }
                                break;
                        }
                    }

                    // Color default
                    if ( !isset( $styles[ 'color' ] ) ) {
                        $styles[ 'color' ] = $font_color;
                    }
                }
                // dpr( $styles );
    
                // Complete the styles
                $styles_array = filter_var_array( apply_filters( 'usernotices_styles', $styles, $screen_location_type, $notice ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $style = '';
                foreach ( $styles_array as $key => $value ) {
                    $style .= $key . ': ' . $value . '; ';
                }
                $style = trim( $style );
    
                // Console
                if ( $screen_location_type == 'console' ) {

                    $plain_text_content = wp_strip_all_tags( $content );
                    $plain_text_content = preg_replace( '/\s+/', ' ', $plain_text_content );
                    $plain_text_content = trim( $plain_text_content );

                    echo '<script id="user-notice-' . esc_attr( $notice_id ) . '">';
                        if ( $delay ) { echo 'setTimeout( function() {'; }
                        echo 'console.log( "%c' . esc_html( $plain_text_content ) . '", "' . esc_html( $style ) . '" );';
                        if ( $delay ) { echo '}, ' . esc_attr( $delay ) . '000 );'; }
                    echo '</script>';
                    
                // Everything else
                } else {

                    // Dismiss button
                    $right = ( $screen_location_type == 'modal' ) ? '15' : '10';
                    $dismiss_btn = $notice[ 'dismissable' ] ? '<div class="usernotice-dismiss" data-id="' . $notice_id . '" style="position: absolute; top: 5px; right: ' . $right . 'px; color: ' . $font_color . '; font-size: 1.5rem; cursor: pointer;">&times;</div>' : '';
                    
                    // Other action buttons
                    if ( !empty( $buttons ) ) {
                        $button_links = [];
                        foreach ( $buttons as $button ) {
                            $url = $button[ 'url' ] && $button[ 'url' ] != '' ? $button[ 'url' ] : '#';
                            $button_links[] = '<a href="' . $url . '" class="usernotice-btn" data-id="' . $notice_id . '" data-metakey="' . $button[ 'metakey' ] . '" style="background-color: ' . $button[ 'bgcolor' ] . '; color: ' . $button[ 'color' ] . ';">' . $button[ 'text' ] . '</a>';
                        }
                        $incl_btns = implode( ' ', $button_links );
                    } else {
                        $incl_btns = '';
                    }

                    // Extra classes
                    $top_admin_bar_class = is_admin_bar_showing() ? ' has-admin-bar' : '';
                    $show_class = !$delay ? ' show' : '';

                    // If the notice is modal, add the backdrop div
                    if ( $screen_location_type == 'modal' ) {
                        echo '<div id="user-notice-backdrop-' . esc_attr( $notice_id ) . '" class="user-notice-backdrop' . esc_attr( $show_class ) . '"></div>';
                    }

                    // Return it
                    echo '<div id="user-notice-' . esc_attr( $notice_id ) . '" class="user-notice ' . esc_attr( $screen_location_type . $top_admin_bar_class . $show_class ) . '" data-id="' . esc_html( $notice_id) . '" style="' . esc_html( $style ) . '">
                        ' . wp_kses_post( $dismiss_btn ) . '
                        <div class="usernotice-content">
                            <div class="usernotice-text">' . wp_kses( $content, $HELPERS->allowed_html() ) . '</div>
                            <div class="usernotice-buttons">' . wp_kses_post( $incl_btns ) . '</div>
                        </div>
                    </div>';

                    // Above content
                    if ( $screen_location_type == 'above_content' ) {
                        echo '<script id="user-notice-above-content-' . esc_attr( $notice_id ) . '">
                            document.addEventListener( "DOMContentLoaded", function() {
                                var notice = document.getElementById( "user-notice-' . esc_attr( $notice_id ) . '" );
                                var content = document.getElementById( "content" );
                                
                                if ( notice && content ) {
                                    content.parentNode.insertBefore( notice, content );
                                } else if ( notice ) {
                                    notice.remove();
                                    console.log( "Notice #' . esc_attr( $notice_id ) . ' | Could not find #content to place notice above content." );
                                }
                            });
                        </script>';

                    // Below content
                    } else if ( $screen_location_type == 'below_content' ) {
                        echo '<script id="user-notice-below-content-' . esc_attr( $notice_id ) . '">
                            document.addEventListener( "DOMContentLoaded", function() {
                                var notice = document.getElementById( "user-notice-' . esc_attr( $notice_id ) . '" );
                                var content = document.getElementById( "content" );
                                
                                if ( notice && content ) {
                                    content.parentNode.insertBefore( notice, content.nextSibling );
                                } else if ( notice ) {
                                    notice.remove();
                                    console.log( "Notice #' . esc_attr( $notice_id ) . ' | Could not find #content to place notice below content." );
                                }
                            });
                        </script>';
                    } else if ( $screen_location_type == 'top' ) {
                        echo '<script id="user-notice-top-' . esc_attr( $notice_id ) . '">
                            document.addEventListener( "DOMContentLoaded", function() {
                                var notice = document.getElementById( "user-notice-' . esc_attr( $notice_id ) . '" );
                                if ( notice ) {
                                    var body = document.body;
                                    if ( body.firstChild ) {
                                        body.insertBefore( notice, body.firstChild );
                                    } else {
                                        body.appendChild( notice );
                                    }
                                } else {
                                    console.log( "Notice #' . esc_attr( $notice_id ) . ' | Could not find notice element for top placement." );
                                }
                            });
                        </script>';
                    }
                    
                    // Delay
                    if ( $delay ) {
                        echo '<script id="user-notice-delay-' . esc_attr( $notice_id ) . '">
                            document.addEventListener( "DOMContentLoaded", function() {
                                var notice = document.getElementById( "user-notice-' . absint( $notice_id ) . '" );
                                if ( notice ) {
                                    setTimeout( function() {
                                        notice.classList.add( "show" );';
                                        if ( $screen_location_type == 'modal' ) {
                                            echo 'var backdrop = document.getElementById( "user-notice-backdrop-' . absint( $notice_id ) . '" );
                                            backdrop.classList.add( "show" );';
                                        }
                                    echo '}, ' . esc_attr( $delay ) . '000 );
                                }
                            } ) ;
                        </script>';
                    }
                }

            // Alert
            } elseif ( $screen_location_type == 'alert' ) {
                $plain_text_content = wp_strip_all_tags( $content );
                $plain_text_content = preg_replace( '/\s+/', ' ', $plain_text_content );
                $plain_text_content = trim( $plain_text_content );

                echo '<script id="user-notice-' . esc_attr( $notice_id ) . '">';
                if ( $delay ) { echo 'setTimeout( function() {'; }
                    echo 'alert( "' . esc_html( $plain_text_content ) . '" );';
                if ( $delay ) { echo '}, ' . esc_attr( $delay ) . '000 );'; }
                echo '</script>';
            }
        }
    } // End create_footer_notice()


    /**
     * Display the notices
     */
    public function display_notices() {
        $args = [
            'post_type'      => (new Notices())->post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];
    
        $notices = get_posts( $args );
        if ( !empty( $notices ) ) {
            $mk = (new Notices())->meta_key_site_location_type;
            foreach ( $notices as $key => $notice ) {
                if ( $notice->$mk == 'shortcode' ) {
                    unset( $notices[ $key] );
                }
            }
        }

        if ( !empty( $notices ) ) {
            
            echo '<div id="user-notices" plugin="User Notices by ' . esc_html( USERNOTICES_AUTHOR ) . ': ' . esc_url( USERNOTICES_AUTHOR_URI ) . '">';
            
                foreach ( $notices as $notice ) {
                    $this->create_footer_notice( $notice->ID );
                }

            echo '</div>';
        }
    } // End display_notices()


    /**
	 * Dismiss button ajax
	 *
	 * @return void
	 */
	public function ajax_dismiss() {
		check_ajax_referer( $this->nonce, 'nonce' );
        $id = isset( $_REQUEST[ 'id' ] ) ? absint( $_REQUEST[ 'id' ] ) : false;
        if ( $id ) {
            (new Notices())->dismiss_notice( $id );
            wp_send_json_success();
        } else {
            wp_send_json_error( 'No ID found.' );
        }		
	} // End ajax_dismiss()


    /**
	 * Action button ajax
	 *
	 * @return void
	 */
	public function ajax_actions() {
		check_ajax_referer( $this->nonce, 'nonce' );
        $id = isset( $_REQUEST[ 'id' ] ) ? absint( $_REQUEST[ 'id' ] ) : false;
        if ( $id ) {

            $NOTICES = new Notices();

            // Log the action
            $text = isset( $_REQUEST[ 'text' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'text' ] ) ) : false;
            $NOTICES->log_action( $id, $text );
            
            // Save the meta key to the user
            $meta_key = isset( $_REQUEST[ 'metakey' ] ) ? sanitize_key( wp_unslash( $_REQUEST[ 'metakey' ] ) ) : false;
            if ( $meta_key ) {
                $NOTICES->save_metakey( $id, $text, $meta_key );
            }

            wp_send_json_success();
        } else {
            wp_send_json_error( 'No metakey found.' );
        }		
	} // End ajax_actions()


    /**
	 * Enqueue Scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
        if ( is_admin() ) {
            return;
        }

        // Jquery
        $js_handle = USERNOTICES_TEXTDOMAIN.'-notice-script';
        wp_enqueue_script( 'jquery' );
        wp_register_script( $js_handle, USERNOTICES_JS_PATH . 'notice.js', [ 'jquery' ], time(), true );
        wp_localize_script( $js_handle, USERNOTICES__TEXTDOMAIN, [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( $this->nonce )
        ] );
        wp_enqueue_script( $js_handle );

        // CSS
        $css_handle = USERNOTICES_TEXTDOMAIN.'-notice-style';
        wp_register_style( $css_handle, USERNOTICES_CSS_PATH . 'notice.css', [], time() );
        wp_enqueue_style( $css_handle );
    } // End enqueue_scripts()

}