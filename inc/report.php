<?php
/**
 * Report
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserNotices;
use PluginRx\UserNotices\Notices;
use PluginRx\UserNotices\Database;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
	(new Report())->init();
} );


/**
 * The class
 */
class Report {

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
            USERNOTICES_NAME . ' — ' . __( 'Report', 'user-notices' ),
            __( 'Report', 'user-notices' ),
            'manage_options',
            USERNOTICES__TEXTDOMAIN . '_report',
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
        if ( $current_screen->id != USERNOTICES_REPORT_SCREEN_ID ) {
            return;
        }
    
        // Fetch data for reports
        $DB = new Database();
        $top_dismissals = $DB->get_top_dismissals();
        $top_notices = $DB->get_top_actions();
        $top_users = $DB->get_top_users();
        $actions_by_type = $DB->get_actions_by_type();
        ?>
    
        <div class="wrap">
            <h1><?php echo esc_attr( get_admin_page_title() ) ?></h1>
            <p><?php echo esc_html__( 'Insights on notice interactions.', 'user-notices' ); ?></p>

            <div id="usernotice-report">
                <div class="report-section">
                    <h2><?php echo esc_html__( 'Top Notices Clicked', 'user-notices' ); ?></h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th class="id"><?php echo esc_html__( 'Notice ID', 'user-notices' ); ?></th>
                                <th class="item"><?php echo esc_html__( 'Title', 'user-notices' ); ?></th>
                                <th class="count"><?php echo esc_html__( 'Actions Taken', 'user-notices' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $top_notices as $notice ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $notice->notice_id ); ?></td>
                                    <td><a href="<?php echo esc_url( get_edit_post_link( $notice->notice_id ) ); ?>"><?php echo esc_html( get_the_title( $notice->notice_id ) ); ?></a></td>
                                    <td><?php echo esc_html( $notice->actions ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <h2><?php echo esc_html__( 'Top Notices Dismissed', 'user-notices' ); ?></h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th class="id"><?php echo esc_html__( 'Notice ID', 'user-notices' ); ?></th>
                                <th class="item"><?php echo esc_html__( 'Title', 'user-notices' ); ?></th>
                                <th class="count"><?php echo esc_html__( 'Dismissals', 'user-notices' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $top_dismissals as $dismissal ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $dismissal->notice_id ); ?></td>
                                    <td><a href="<?php echo esc_url( get_edit_post_link( $dismissal->notice_id ) ); ?>"><?php echo esc_html( get_the_title( $dismissal->notice_id ) ); ?></a></td>
                                    <td><?php echo esc_html( $dismissal->dismissals ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
    
                <div class="report-section">
                    <h2><?php echo esc_html__( 'Top Users Taking Action', 'user-notices' ); ?></h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th class="id"><?php echo esc_html__( 'User ID', 'user-notices' ); ?></th>
                                <th class="item"><?php echo esc_html__( 'Display Name', 'user-notices' ); ?></th>
                                <th class="count"><?php echo esc_html__( 'Actions Taken', 'user-notices' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $top_users as $user ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $user->user_id ); ?></td>
                                    <td><a href="<?php echo esc_url( get_edit_profile_url( $user->user_id ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a></td>
                                    <td><?php echo esc_html( $user->actions ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
    
                <div class="report-section">
                    <h2><?php echo esc_html__( 'Actions Taken by Type', 'user-notices' ); ?></h2>
                    <div id="actions-by-type" class="chart"></div>
                    <script>
                        // Data for the chart
                        const actionsData = <?php echo json_encode( $actions_by_type ); ?>;

                        // Generate a simple bar chart
                        const chartContainer = document.getElementById( 'actions-by-type' );
                        let chartHTML = '<svg width="220px" height="400">';
                        const maxCount = Math.max( ...actionsData.map( action => action.count ) );
                        const barWidth = 100;
                        const gap = 20;

                        actionsData.forEach( ( action, index ) => {
                            const height = ( action.count / maxCount ) * 300;
                            const label = action.type == "dismiss" ? "<?php echo esc_html__( 'Dismissals', 'user-notices' ); ?>" : "<?php echo esc_html__( 'Actions', 'user-notices' ); ?>";
                            const color = action.type == "dismiss" ? "1E427F" : "38B3F1";
                            chartHTML += `<rect x="${index * ( barWidth + gap )}" y="${400 - height}" width="${barWidth}" height="${height}" fill="#${color}"></rect>`;
                            chartHTML += `<text x="${index * ( barWidth + gap ) + barWidth / 2}" y="${400 - height - 10}" text-anchor="middle">${label} (${action.count})</text>`;
                        } );

                        chartHTML += '</svg>';
                        chartContainer.innerHTML = chartHTML;
                    </script>
                </div>
            </div>
        </div>
        <?php
    } // End page()
    

	/**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        // Check if we are on the correct admin page
        if ( $hook !== USERNOTICES_REPORT_SCREEN_ID ) {
            return;
        }

		// Register and enqueue your CSS
		wp_enqueue_style( USERNOTICES_TEXTDOMAIN . '-styles', USERNOTICES_CSS_PATH . 'report.css', [], time() );
    } // End enqueue_scripts()
    
}