<?php
/**
 * Database-related operations
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserNotices;
use PluginRx\UserNotices\Helpers;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * The Database class
 */
class Database {

    /**
     * Table name
     *
     * @var string
     */
    public $table_name;


    /**
     * Constructor
     */
    public function __construct() {

        // Table name
        global $wpdb;
        $this->table_name = $wpdb->prefix . USERNOTICES__TEXTDOMAIN;

    } // End __construct()


    /**
     * Create the custom table if it doesn't exist
     *
     * @return void
     */
    public function maybe_create_table() {
        global $wpdb;

        // Check if the table exists
        $wpdb->get_results( "SHOW TABLES LIKE '$this->table_name'" );
        if ( !empty( $result ) ) {
            return;
        }

        // SQL to create the table
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "
            CREATE TABLE $this->table_name (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                user_ip VARCHAR(45) DEFAULT NULL,
                notice_id BIGINT(20) UNSIGNED NOT NULL,
                time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                type ENUM('dismiss', 'action') NOT NULL,
                action VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY notice_id (notice_id)
            ) $charset_collate;
        ";

        // Include the required file for dbDelta function
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    } // End maybe_create_table()


    /**
     * Delete the custom table
     *
     * @return int|bool
     */
    public function delete_table() {
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS $this->table_name";
        return $wpdb->query( $sql );
    } // End delete_table()


    /**
     * Add a record to track user interaction with a notice.
     *
     * @param int $user_id
     * @param int $notice_id
     * @param string $type
     * @param string $action
     * 
     * @return int|false
     */
    public function add_record( $user_id, $notice_id, $type, $action = null ) {
        global $wpdb;

        // Sanitize the action to avoid any issues with special characters.
        $action = sanitize_text_field( $action );

        // Get the ip address if no user_id
        $user_ip = !$user_id ? (new Helpers())->get_user_ip() : null;

        // Prepare the data to be inserted into the table.
        $data = [
            'user_id'   => $user_id,
            'user_ip'   => $user_ip,
            'notice_id' => $notice_id,
            'time'      => current_time( 'mysql' ),
            'type'      => $type,
            'action'    => $action,
        ];

        // Insert the record into the table.
        return $wpdb->insert( $this->table_name, $data );
    } // End add_record()


    /**
     * Delete a record for a specific user and notice.
     *
     * @param int $user_id
     * @param int $notice_id
     *
     * @return int|false
     */
    public function delete_record( $user_id, $notice_id ) {
        global $wpdb;

        // Prepare the conditions to delete the record.
        $where = [
            'user_id'   => $user_id,
            'notice_id' => $notice_id,
        ];

        // Delete the record from the table.
        return $wpdb->delete( $this->table_name, $where );
    } // End delete_record()


    /**
     * Delete all records associated with a specific notice when it is permanently deleted.
     *
     * @param int $notice_id
     * @return int|false
     */
    public function delete_notice_records( $notice_id ) {
        if ( empty( $notice_id ) || !is_numeric( $notice_id ) ) {
            return;
        }

        global $wpdb;
        return $wpdb->delete( $this->table_name, [ 'notice_id' => $notice_id ] );
    } // End delete_notice_records()


    /**
     * Delete all records associated with a specific user
     *
     * @param int $user_id
     * @return int|false
     */
    public function delete_user_records( $user_id ) {
        if ( empty( $user_id ) || !is_numeric( $user_id ) ) {
            return;
        }

        global $wpdb;
        return $wpdb->delete( $this->table_name, [ 'user_id' => $user_id ] );
    } // End delete_user_records()


    /**
     * Get records based on optional filters: user_id, notice_id, and type.
     *
     * @param int $user_id
     * @param int $notice_id
     * @param string $type 
     *
     * @return array
     */
    public function get( $user_id = null, $user_ip = null, $notice_id = null, $type = null, $orderby = null ) {
        global $wpdb;

        // Start building the query
        $query = "SELECT * FROM $this->table_name WHERE 1=1"; // Always true condition to add optional filters

        // Add filters for user_id, notice_id, and type if provided
        if ( !is_null( $user_id ) ) {
            $query .= $wpdb->prepare( " AND user_id = %d", $user_id );
        }
        if ( !is_null( $user_ip ) ) {
            $query .= $wpdb->prepare( " AND user_ip = %s", $user_ip );
        }
        if ( !is_null( $notice_id ) ) {
            $query .= $wpdb->prepare( " AND notice_id = %d", $notice_id );
        }
        if ( !is_null( $type ) && in_array( $type, [ 'dismiss', 'action' ] ) ) {
            $query .= $wpdb->prepare( " AND type = %s", $type );
        }

        // Add order by clause to get most recent first
        if ( !is_null( $orderby ) ) {
            $query .= " ORDER BY " . $orderby . " DESC";
        }

        // Execute the query and return the results
        return $wpdb->get_results( $query ); // phpcs:ignore 
    } // End get()


    /**
     * Get the top dismissals
     *
     * @return array
     */
    public function get_top_dismissals() {
        global $wpdb;
        return $wpdb->get_results( "
            SELECT notice_id, COUNT(*) AS dismissals
            FROM {$this->table_name}
            WHERE type = 'dismiss'
            GROUP BY notice_id
            ORDER BY dismissals DESC
            LIMIT 10
        " );
    } // End get_top_dismissals()


    /**
     * Get the top actions
     *
     * @return array
     */
    public function get_top_actions() {
        global $wpdb;
        return $wpdb->get_results( "
            SELECT notice_id, COUNT(*) AS actions
            FROM {$this->table_name}
            WHERE type = 'action'
            GROUP BY notice_id
            ORDER BY actions DESC
            LIMIT 10
        " );
    } // End get_top_actions()
    

    /**
     * Get the top user interactions
     *
     * @return array
     */
    public function get_top_users() {
        global $wpdb;
        return $wpdb->get_results( "
            SELECT user_id, COUNT(*) AS actions, display_name
            FROM {$this->table_name}
            JOIN {$wpdb->users} ON {$this->table_name}.user_id = {$wpdb->users}.ID
            WHERE type = 'action'
            GROUP BY user_id
            ORDER BY actions DESC
            LIMIT 10
        " );
    } // End get_top_users()


    /**
     * Get the actions taken by type
     *
     * @return array
     */
    public function get_actions_by_type() {
        global $wpdb;
        return $wpdb->get_results( "
            SELECT type, COUNT(*) AS count
            FROM {$this->table_name}
            GROUP BY type
        " );
    } // End get_actions_by_type()
}