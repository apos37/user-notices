<?php
/**
 * Helpers
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
 * The class
 */
class Helpers {

	/**
     * Convert timezone
     * 
     * @param string $date
     * @param string $format
     * @param string $timezone
     * @return string
     */
    public function convert_timezone( $date = null, $format = 'F j, Y g:i A', $timezone = null ) {
        // Get today as default
        if ( is_null( $date ) || !$date ) {
            $date = gmdate( 'Y-m-d H:i:s' );

        // Or else format it properly for converting purposes
        } else {

            // Check if the date is a timestamp
            if ( is_numeric( $date ) && (int)$date == $date ) {
                $date = gmdate( 'Y-m-d H:i:s', $date );
            } else {
                $date = gmdate( 'Y-m-d H:i:s', strtotime( $date ) );
            }
        }

        // Get the date in UTC time
        $date = new \DateTime( $date, new \DateTimeZone( 'UTC' ) );

        // Get the timezone string
        if ( !is_null( $timezone ) ) {
            $timezone_string = $timezone;
        } else {
            $timezone_string = wp_timezone_string();
        }

        // Set the timezone to the new one
        $date->setTimezone( new \DateTimeZone( $timezone_string ) );

        // Format it the way we way
        $new_date = $date->format( $format );

        // Return it
        return $new_date;
    } // End convert_timezone()


    /**
	 * Check if the post can be saved based on various conditions.
	 *
	 * @param int    $post_id       The ID of the post being saved.
	 * @param mixed  $the_post_type The post type or array of post types to validate against.
	 * @param string $nonce_value   The nonce value to check for validity.
	 * @param string $nonce_action  The nonce action to verify.
	 * 
	 * @return bool True if the post can be saved, false otherwise.
	 */
    public function can_save_post( $post_id, $the_post_type, $nonce_value, $nonce_action, $quick_edit = false ) {
		// Verify that the nonce is valid
		if ( !isset( $_POST[ $nonce_value ] ) || 
			 !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_value ] ) ), $nonce_action ) ) {
			return false;
		}
	
		// Validate post type
        if ( !$quick_edit ) {
            global $post_type;
            if ( ( is_array( $the_post_type ) && !in_array( $post_type, $the_post_type ) ) ||
                 ( !is_array( $the_post_type ) && $the_post_type !== $post_type ) ) {
                return false;
            }
        
            // Common checks to prevent saving
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ||
                defined( 'DOING_AJAX' ) && DOING_AJAX ||
                get_post_status( $post_id ) === 'auto-draft' ||
                get_post_status( $post_id ) === 'trash' ||
                wp_is_post_revision( $post_id ) ||
                isset( $_REQUEST[ 'bulk_edit' ] ) ) {
                return false;
            }
        }

		// Check user permissions
		if ( $the_post_type == 'page' ) {
			if ( !current_user_can( 'edit_page', $post_id ) ) {
				return false;
			}
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) ) {
				return false;
			}
		}
	
		// All checks passed
		return true;
	} // End can_save_post()


    /**
     * Get the current user's IP address
     *
     * @return string
     */
    public function get_user_ip() {
        $keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxy
            'HTTP_X_REAL_IP',        // Nginx
            'HTTP_CLIENT_IP',        
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'            // Default
        ];
    
        foreach ( $keys as $key ) {
            if ( isset( $_SERVER[ $key ] ) ) {
                $raw_value = filter_var( wp_unslash( $_SERVER[ $key ] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                $ip_list = explode( ',', $raw_value );
    
                foreach ( $ip_list as $ip ) {
                    $ip = trim( $ip );
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                        return $ip;
                    }
                }
            }
        }
    
        return 'Unknown';
    } // End get_user_ip()
    

    /**
     * Get a contrast color
     *
     * @param string $hex
     * @return string
     */
    public function get_contrast_color( $hex ) {
        // Remove the hash symbol if present
        $hex = str_replace( '#', '', $hex );
    
        // Convert the hex color to RGB
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
    
        // Calculate brightness using the luminance formula
        $brightness = ( 0.2126 * $r + 0.7152 * $g + 0.0722 * $b );
    
        // If brightness is higher than 128, return black; otherwise, return white
        return ( $brightness > 128 ) ? '#000000' : '#ffffff';
    } // End get_contrast_color()


    /**
     * Check if the current page is the edit page
     *
     * @return boolean
     */
    public function dont_run_shortcode_on_edit_screen() {
        return ( is_admin() || wp_is_json_request() );
    } // End dont_run_shortcode_on_edit_screen()


    /**
     * Allowed html including forms
     *
     * @return array
     */
    public function allowed_html() {
        global $allowedposttags;
    
        $allowed_html = array_merge( $allowedposttags, [
            'form'    => [
                'action'  => true,
                'method'  => true,
                'enctype' => true,
                'target'  => true,
                'id'      => true,
                'class'   => true,
            ],
            'input'   => [
                'type'        => true,
                'name'        => true,
                'value'       => true,
                'placeholder' => true,
                'required'    => true,
                'checked'     => true,
                'readonly'    => true,
                'disabled'    => true,
                'id'          => true,
                'class'       => true,
            ],
            'textarea' => [
                'name'        => true,
                'rows'        => true,
                'cols'        => true,
                'placeholder' => true,
                'required'    => true,
                'id'          => true,
                'class'       => true,
            ],
            'select'  => [
                'name'     => true,
                'required' => true,
                'id'       => true,
                'class'    => true,
            ],
            'option'  => [
                'value'    => true,
                'selected' => true,
            ],
            'button'  => [
                'type'  => true,
                'name'  => true,
                'value' => true,
                'id'    => true,
                'class' => true,
            ],
            'label'   => [
                'for'   => true,
                'id'    => true,
                'class' => true,
            ],
            'style' => [ 
                'type' => true,
            ],
            'script' => [
                'type' => true,
                'src'  => true,
            ],
        ] );
    
        return $allowed_html;
    } // End allowed_html()


    /**
     * Remove query strings without refresh
     *
     * @param string|array $params
     * @return void
     */
    public function remove_qs_without_refresh( $params = null, $nonce = '_wpnonce' ) {
        if ( $params !== null && !is_array( $params ) ) {
            $params = [ $params ];
        }
    
        // Include nonce if $nonce is not false and $params is not null
        if ( $params !== null && $nonce !== false ) {
            $params[] = $nonce;
        }
    
        // Encode parameters safely (only if params are set)
        $params_json = ( $params !== null ) ? wp_json_encode( array_map( 'sanitize_key', $params ) ) : 'null';
        ?>
        <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const url = new URL( window.location.href );
            const paramsToRemove = <?php echo $params_json; ?>;
    
            let modified = false;
    
            // Always remove empty or blank query parameters
            [...url.searchParams].forEach( ( [ key, value ] ) => {
                if ( value === '' || value.trim() === '' ) {
                    url.searchParams.delete( key );
                    modified = true;
                }
            } );
    
            // Remove specified parameters if params are provided
            if ( paramsToRemove !== null ) {
                paramsToRemove.forEach( param => {
                    if ( url.searchParams.has( param ) ) {
                        url.searchParams.delete( param );
                        modified = true;
                    }
                } );
            }
    
            if ( modified ) {
                window.history.replaceState( null, '', url.toString() );
            }
        } );
        </script>
        <?php
    } // End remove_qs_without_refresh()

}