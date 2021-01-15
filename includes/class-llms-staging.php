<?php
/**
 * Handle warnings and notices when staging is enabled.
 *
 * @package LifterLMS/Classes
 *
 * @since 3.32.0
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_Staging class.
 *
 * @since 3.32.0
 */
class LLMS_Staging {

	/**
	 * Static Constructor.
	 *
	 * @since 3.32.0
	 * @since [version] Add hook on `llms_site_clone_detected` action.
	 *
	 * @return void
	 */
	public static function init() {

		add_action( 'llms_site_clone_detected', array( __CLASS__, 'clone_detected' ) );

		add_action( 'admin_menu', array( __CLASS__, 'menu_warning' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_staging_notice_actions' ) );

	}

	/**
	 * Callback function to automatically disable site features when a clone is detected
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public static function clone_detected() {

		LLMS_Site::update_feature( 'recurring_payments', false );

		if ( is_admin() ) {
			self::notice();
		}

	}

	/**
	 * Retrieves the HTML for the "warning bubble" displayed in the admin menu when staging mode is active
	 *
	 * @since [version]
	 *
	 * @return string
	 */
	protected static function get_menu_warning_bubble() {
		return ' <span class="update-plugins">' . esc_html__( 'Staging', 'lifterlms' ) . '</span>';
	}

	/**
	 * Handle the action buttons present in the recurring payments staging notice.
	 *
	 * @since 3.32.0
	 * @since 3.35.0 Sanitize input data.
	 * @since [version] Use `llms_filter_input()` for retrieval of `$_GET` data.
	 *
	 * @return void
	 */
	public static function handle_staging_notice_actions() {

		if ( ! isset( $_GET['llms-staging-status'] ) || ! isset( $_GET['_llms_staging_nonce'] ) ) {
			return;
		}

		if ( ! llms_verify_nonce( '_llms_staging_nonce', 'llms_staging_status', 'GET' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'lifterlms' ) );
		}

		$action = llms_filter_input( INPUT_GET, 'llms-staging-status', FILTER_SANITIZE_STRING );
		if ( 'enable' === $action ) {
			LLMS_Site::set_lock_url();
			LLMS_Site::update_feature( 'recurring_payments', true );
		} elseif ( 'disable' === $action ) {
			LLMS_Site::clear_lock_url();
			LLMS_Site::update_feature( 'recurring_payments', false );
			update_option( 'llms_site_url_ignore', 'yes' );
		}

		LLMS_Admin_Notices::delete_notice( 'maybe-staging' );

		if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			llms_redirect_and_exit( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
		}

	}



	/**
	 * Adds a "bubble" to the "Orders" menu item when recurring payments are disabled.
	 *
	 * @since 3.32.0
	 * @since [version] Moved HTML for the warning bubble into it's own method.
	 *
	 * @return void
	 */
	public static function menu_warning() {

		if ( LLMS_Site::get_feature( 'recurring_payments' ) ) {
			return;
		}

		global $menu;
		foreach ( $menu as $index => $item ) {

			if ( 'edit.php?post_type=llms_order' === $item[2] ) {
				$menu[ $index ][0] .= self::get_menu_warning_bubble();
			}
		}

	}

	/**
	 * Output a notice informing the use the site was put into staging mode.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public static function notice() {

		$id = 'maybe-staging';

		if ( ! LLMS_Admin_Notices::has_notice( $id ) ) {

			LLMS_Admin_Notices::add_notice(
				$id,
				array(
					'type'        => 'info',
					'dismissible' => false,
					'remindable'  => false,
					'template'    => 'admin/notices/staging.php',
				)
			);

		}

	}

}

return LLMS_Staging::init();
