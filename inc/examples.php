<?php
/**
 * Example notices
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
	(new Examples())->init();
} );


/**
 * The class
 */
class Examples {

	/**
     * Nonce
     *
     * @var string
     */
    private $nonce = 'usernotices_nonce';


	/**
	 * Load on init
	 *
	 * @return void
	 */
	public function init() {
        
		// Submenu
        add_action( 'admin_menu', [ $this, 'submenu' ] );

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
            'edit.php?post_type='.(new Notices())->post_type,
           USERNOTICES_NAME . ' — ' . __( 'Examples', 'user-notices' ),
            __( 'Examples', 'user-notices' ),
            'manage_options',
            USERNOTICES__TEXTDOMAIN . '_examples',
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
        if ( $current_screen->id != USERNOTICES_EXAMPLES_SCREEN_ID ) {
            return;
        }

        // Install example notices
        $msg = '';

        if ( isset( $_GET[ 'install' ] ) && isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ) ), $this->nonce ) ) {
            $install_key = sanitize_key( $_GET[ 'install' ] );
            $notice = $this->create( $install_key );

            if ( is_wp_error( $notice ) ) {
                $msg = '<div class="notice notice-error"><p>' . __( 'Error: ', 'user-notices' ) . $notice->get_error_message() . '</p></div>';
            } elseif ( $notice === false ) {
                $msg = '<div class="notice notice-warning"><p>' . __( 'Notice already exists.', 'user-notices' ) . '</p></div>';
            } else {
                /* translators: %s: URL to the notice edit page */
                $msg = '<div class="notice notice-success"><p>' . sprintf( __( 'Notice created successfully. <a href="%s">Go now!</a>', 'user-notices' ),
                    admin_url( 'post.php?post=' . $notice . '&action=edit' )
                ) . '</p></div>';
            }
        }

        ?>
        <style>
        h2 { margin: 3rem 0 1rem 0; }
        </style>
		<div class="wrap">
			<h1><?php echo esc_attr( get_admin_page_title() ) ?></h1>
			<p><?php echo esc_html__( 'Quickly install pre-built notices and edit them to your liking.', 'user-notices' ); ?></p>

            <?php
            // Display the admin notice
            if ( !empty( $msg ) ) {
                echo wp_kses_post( $msg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>

            <div id="usernotice-examples">
                <?php
                // Let's add some examples
                $examples = $this->get();
                foreach ( $examples as $key => $example ) {
                    ?>
                    <div class="usernotice-example">
                        <div class="usernotice-example-title"><?php echo esc_attr( $example[ 'title' ] ); ?></div>
                        <div class="usernotice-example-desc"><?php echo esc_attr( $example[ 'description' ] ); ?></div>
                        <a class="usernotice-example-btn button button-secondary" href="<?php echo esc_url( add_query_arg( [
                            'install'  => $key,
                            '_wpnonce' => wp_create_nonce( $this->nonce )
                        ] ) ); ?>"><?php echo esc_html__( 'Install', 'user-notices' ); ?></a>
                    </div>
                    <?php
                }
            ?>
            </div>
		</div>
        <?php
		(new Helpers())->remove_qs_without_refresh( 'install' );
    } // End page()


	/**
	 * Example args
	 *
	 * @param string $key
	 * @return array
	 */
	public function get( $key = null ) {
		$examples = [
			'accept_cookies' => [
				'description'			=> __( 'Inform users that your website uses cookies to improve their experience, allowing them to accept or decline.', 'user-notices' ),
				'title'                 => __( 'Accept Cookies', 'user-notices' ),
				'content'               => '<span style="color: #ffffff">' . __( 'We use cookies to improve your experience on our website. By continuing to browse, you agree to our use of cookies.', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons'               => [
						[
							'text'    => __( 'Accept', 'user-notices' ),
							'bgcolor' => '#2069A5',
							'color'   => '#ffffff',
							'url'     => '',
							'metakey' => ''
						],
						[
							'text'    => __( 'Decline', 'user-notices' ),
							'bgcolor' => '#ffffff',
							'color'   => '#000000',
							'url'     => get_privacy_policy_url(),
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'bottom',
					'audience_type'         => 'logged_out',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => false,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#000000'
				]
			],
			'gdpr_consent' => [
				'description'               => __( 'Notify users of your compliance with GDPR and request consent to process their personal data.', 'user-notices' ),
				'title'                     => __( 'GDPR Consent', 'user-notices' ),
				'content'                   => '<span style="color: #ffffff">' . __( 'We use cookies to ensure that we give you the best experience on our website. By using our website, you consent to our use of cookies and data processing under GDPR regulations.', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Accept', 'user-notices' ),
							'bgcolor' => '#2069A5',
							'color'   => '#ffffff',
							'url'     => '',
							'metakey' => ''
						],
						[
							'text'    => __( 'Learn More', 'user-notices' ),
							'bgcolor' => '#ffffff',
							'color'   => '#000000',
							'url'     => get_privacy_policy_url(),
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'bottom',
					'audience_type'         => 'everyone',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => false,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#000000'
				]
			],
			'age_verification' => [
				'description'            => __( 'This notice asks users to confirm that they are over a specific age before they can continue to use your website.', 'user-notices' ),
				'title'                  => __( 'Age Verification', 'user-notices' ),
				'content'                => __( 'This website contains age-restricted content. You must be over the age of 18 to continue.', 'user-notices' ),
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'I am over 18', 'user-notices' ),
							'bgcolor' => '#2069A5',
							'color'   => '#ffffff',
							'url'     => '',
							'metakey' => ''
						],
						[
							'text'    => __( 'Go to Privacy Policy', 'user-notices' ),
							'bgcolor' => '#ffffff',
							'color'   => '#000000',
							'url'     => get_privacy_policy_url(),
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'bottom',
					'audience_type'         => 'everyone',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => false,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#ffffff'
				]
			],
			'newsletter_signup' => [
				'description'            => __( 'Prompt users to subscribe to your newsletter to stay updated with the latest news and offers.', 'user-notices' ),
				'title'                  => __( 'Newsletter Signup', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( 'Subscribe to our newsletter for the latest updates and special offers.', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Sign Up Now', 'user-notices' ),
							'bgcolor' => '#ff7b00',
							'color'   => '#ffffff',
							'url'     => '#signup',
							'metakey' => ''
						],
						[
							'text'    => __( 'No Thanks', 'user-notices' ),
							'bgcolor' => '#ffffff',
							'color'   => '#000000',
							'url'     => '',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'below_content',
					'audience_type'         => 'everyone',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#000000'
				]
			],
			'sale_or_discount_promotion' => [
				'description'            => __( 'Inform users about an ongoing sale or discount promotion on your products or services.', 'user-notices' ),
				'title'                  => __( 'Sale or Discount Promotion', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( 'Hurry! We are offering exclusive discounts on all items. Don’t miss out on great savings!', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Shop Now', 'user-notices' ),
							'bgcolor' => '#ff7b00',
							'color'   => '#ffffff',
							'url'     => '/shop',
							'metakey' => ''
						],
						[
							'text'    => __( 'No Thanks', 'user-notices' ),
							'bgcolor' => '#ffffff',
							'color'   => '#000000',
							'url'     => '',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'bottom',
					'audience_type'         => 'everyone',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#000000'
				]
			],
			'urgent_announcement' => [
				'description'            => __( 'Make users aware of an urgent update or announcement about your website or service.', 'user-notices' ),
				'title'                  => __( 'Urgent Announcement', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( 'Our website is experiencing temporary issues. We are working to resolve them as quickly as possible. Please bear with us.', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Got It', 'user-notices' ),
							'bgcolor' => '#ff0000',
							'color'   => '#ffffff',
							'url'     => '',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'center',
					'audience_type'         => 'everyone',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => false,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#ff0000'
				]
			],
			'seasonal_sale' => [
				'description'            => __( 'We are having a seasonal sale! Get up to 50% off on selected items.', 'user-notices' ),
				'title'                  => __( 'Seasonal Sale - 50% OFF', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( ' Hurry! Our seasonal sale is live now. Don’t miss out on amazing discounts! 😍', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Shop Now', 'user-notices' ),
							'bgcolor' => '#0C1117',
							'color'   => '#ffffff',
							'url'     => '/shop',
							'metakey' => ''
						],
						[
							'text'    => __( 'No Thanks', 'user-notices' ),
							'bgcolor' => '#ffffff',
							'color'   => '#000000',
							'url'     => '',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'top',
					'audience_type'         => 'everyone',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#ff7b00'
				]
			],
			'flash_sale' => [
				'description'            => __( 'Exclusive flash sale for our loyal members. Shop now before the timer runs out!', 'user-notices' ),
				'title'                  => __( 'Flash Sale - Limited Time!', 'user-notices' ),
				'content'                => '<span style="color: #ffffff; font-size: 2rem;">' . __( 'You have less than 30 minutes to shop! Don’t miss out on these discounts!', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Shop Now', 'user-notices' ),
							'bgcolor' => '#ff7b00',
							'color'   => '#ffffff',
							'url'     => '/shop',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'modal',
					'audience_type'         => 'logged_in',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#000000'
				]
			],
			'exclusive_content' => [
				'description'            => __( 'Exclusive content for Premium Members only.', 'user-notices' ),
				'title'                  => __( 'Exclusive Content for Premium Members', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( 'You need to be a Premium Member to view this content. Upgrade now for full access!', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Upgrade Now', 'user-notices' ),
							'bgcolor' => '#2069A5',
							'color'   => '#ffffff',
							'url'     => '/premium',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'post_types',
					'screen_location_type'  => 'above_content',
					'audience_type'         => 'roles',
					'audience'				=> 'subscriber',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => false,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#2069A5'
				]
			],
			'free_shipping' => [
				'description'            => __( 'Enjoy free shipping on your next order! Limited time offer for logged-in members.', 'user-notices' ),
				'title'                  => __( 'Free Shipping - Limited Time Offer!', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( 'Free shipping on all orders. Login to take advantage of this exclusive offer!', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Shop Now', 'user-notices' ),
							'bgcolor' => '#ff7b00',
							'color'   => '#ffffff',
							'url'     => '/shop',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'homepage',
					'screen_location_type'  => 'below_content',
					'audience_type'         => 'logged_in',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#2069A5'
				]
			],
			'new_feature' => [
				'description'            => __( 'We have added an exciting new feature! Check it out now on our homepage.', 'user-notices' ),
				'title'                  => __( 'Introducing New Feature!', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( 'Check out our brand new feature designed to enhance your experience on the site.', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Learn More', 'user-notices' ),
							'bgcolor' => '#ff7b00',
							'color'   => '#ffffff',
							'url'     => '/new-feature',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'homepage', // Only on the homepage
					'screen_location_type'  => 'top', // Displayed at the top
					'audience_type'         => 'everyone', // All users
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => false,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#ff7b00'
				]
			],
			'event_reminder' => [
				'description'            => __( 'Don’t forget to register for the upcoming event. Limited spots available!', 'user-notices' ),
				'title'                  => __( 'Event Registration Reminder', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( 'Hurry! The registration for the event closes soon. Secure your spot today.', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Register Now', 'user-notices' ),
							'bgcolor' => '#2069A5',
							'color'   => '#ffffff',
							'url'     => '/event-registration',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'above_content',
					'audience_type'         => 'everyone',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#2069A5'
				]
			],
			'survey_request' => [
				'description'            => __( 'We value your feedback! Take a quick survey to help us improve.', 'user-notices' ),
				'title'                  => __( 'We Want Your Feedback', 'user-notices' ),
				'content'                => '<span style="color: #ffffff; font-size: 2rem;">' . __( 'Take a 2-minute survey and tell us what you think about our site.', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Take Survey', 'user-notices' ),
							'bgcolor' => '#ff7b00',
							'color'   => '#ffffff',
							'url'     => '/survey',
							'metakey' => ''
						],
						[
							'text'    => __( 'No Thanks', 'user-notices' ),
							'bgcolor' => '#ffffff',
							'color'   => '#000000',
							'url'     => '',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'modal',
					'audience_type'         => 'logged_out',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => false,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#ff7b00'
				]
			],
			'maintenance_notice' => [
				'description'            => __( 'Scheduled maintenance on our site. Some features may be temporarily unavailable.', 'user-notices' ),
				'title'                  => __( 'Scheduled Maintenance', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( 'We will be performing scheduled maintenance on the site. Some pages may be temporarily unavailable.', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Learn More', 'user-notices' ),
							'bgcolor' => '#2069A5',
							'color'   => '#ffffff',
							'url'     => '/maintenance-info',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'post_types',
					'screen_location_type'  => 'bottom',
					'audience_type'         => 'everyone',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#2069A5'
				]
			],
			'referral_program' => [
				'description'            => __( 'Invite friends to join and earn rewards!', 'user-notices' ),
				'title'                  => __( 'Referral Program - Earn Rewards!', 'user-notices' ),
				'content'                => '<span style="color: #ffffff">' . __( 'Invite your friends to sign up and earn discounts or rewards for every successful referral.', 'user-notices' ) . '</span>',
				'custom' => [
					'buttons' => [
						[
							'text'    => __( 'Invite Now', 'user-notices' ),
							'bgcolor' => '#ff7b00',
							'color'   => '#ffffff',
							'url'     => '/referral',
							'metakey' => ''
						]
					],
					'site_location_type'    => 'all',
					'screen_location_type'  => 'bottom_content',
					'audience_type'         => 'logged_in',
					'start_date'            => '',
					'end_date'              => '',
					'delay'                 => 0,
					'dismissable'           => true,
					'hide_after_action'     => true,
					'debug'                 => false,
					'show_user_actions'     => false,
					'bg_color'              => '#ff7b00'
				]
			]
		];

		if ( is_null( $key ) ) {
			return $examples;
		}

		if ( isset( $examples[ $key ] ) ) {
			return $examples[ $key ];
		}

		return false;
	} // End get()


    /**
	 * Create notice
	 *
	 * @return mixed
	 */
	public function create( $example_key ) {
		// Post type
		$post_type = (new Notices())->post_type;

		// Get the example
		$example = $this->get( $example_key );
		if ( !$example ) {
			return new \WP_Error( 'invalid_example', __( 'Invalid example key.', 'user-notices' ) );
		}
	
		// Create the new post
		$post_data = [
			'post_title'    => $example[ 'title' ],
			'post_content'  => $example[ 'content' ],
			'post_status'   => 'draft',
			'post_type'     => $post_type
		];
		$notice_id = wp_insert_post( $post_data );

		// Check for errors
		if ( is_wp_error( $notice_id ) ) {
			return new \WP_Error( 'creation_failed', __( 'Notice creation failed: ', 'user-notices' ) . $notice_id->get_error_message() );
		}

		// Add custom meta
		if ( $notice_id ) {
			foreach ( $example[ 'custom' ] as $meta_key => $meta_value ) {
				$updated = update_post_meta( $notice_id, $meta_key, $meta_value );
				if ( !$updated ) {
					error_log( "Failed to update meta '{$meta_key}' for user notice ID {$notice_id}." ); // phpcs:ignore 
				}
			}
		}

		return $notice_id;
	} // End create()


	/**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        // Check if we are on the correct admin page
        if ( $hook !== USERNOTICES_EXAMPLES_SCREEN_ID ) {
            return;
        }

		// Register and enqueue your CSS
		wp_enqueue_style( USERNOTICES_TEXTDOMAIN . '-styles', USERNOTICES_CSS_PATH . 'examples.css', [], time() );
    } // End enqueue_scripts()
    
}