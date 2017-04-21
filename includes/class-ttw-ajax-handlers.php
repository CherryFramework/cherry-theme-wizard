<?php
/**
 * Class description
 *
 * @package   package_name
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'TTW_Ajax_Handlers' ) ) {

	/**
	 * Define TTW_Ajax_Handlers class
	 */
	class TTW_Ajax_Handlers {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Constructor for the class
		 */
		function __construct() {

			//add_action( 'init', array( $this, 'activate_child' ) );

			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				return;
			}

			$actions = array(
				'verify_data',
				'install_parent',
				'activate_parent',
				'get_child',
				'install_child',
				'activate_child',
				'skip_child',
			);

			foreach ( $actions as $action ) {
				if ( is_callable( array( $this, $action ) ) ) {
					add_action( 'wp_ajax_tm_theme_wizard_' . $action, array( $this, $action ) );
					add_action( 'init' );
				}
			}

			add_action( 'ttw_source_rename_done', array( $this, 'store_theme_data' ) );
		}

		/**
		 * Perforem child theme installation
		 *
		 * @return void
		 */
		public function get_child() {

			$this->verify_request();

			ttw()->dependencies( array( 'child-api' ) );

			$theme_data   = get_option( ttw()->settings['options']['parent_data'] );
			$install_data = get_transient( ttw()->slug() );
			$id           = isset( $install_data['id'] ) ? esc_attr( $install_data['id'] ) : false;
			$slug         = isset( $theme_data['TextDomain'] ) ? esc_attr( $theme_data['TextDomain'] ) : false;
			$name         = isset( $theme_data['ThemeName'] ) ? esc_attr( $theme_data['ThemeName'] ) : false;

			if ( ! $id || ! $slug || ! $name ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'Installation data lost, please return to previous step and try again.', 'cherry-theme-wizard' ),
				) );
			}

			$api        = ttw_child_api( $id, $slug, $name );
			$child_data = $api->api_call();

			if ( empty( $child_data['success'] ) ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'Request failed. Please, try again later.', 'cherry-theme-wizard' ),
				) );
			}

			if ( false === $child_data['success'] ) {
				wp_send_json_error( $child_data['data']['message'] );
			}

			$theme_url = $child_data['data']['theme'];

			wp_send_json_success( array(
				'message'     => esc_html__( 'Child theme generated. Installing...', 'cherry-theme-wizard' ),
				'doNext'      => true,
				'nextRequest' => array(
					'action' => 'tm_theme_wizard_install_child',
					'child'  => $theme_url,
				),
			) );
		}

		/**
		 * Skip child theme installation
		 *
		 * @return void
		 */
		public function skip_child() {

			$this->verify_request();

			do_action( 'ttw_skip_child_installation' );

			wp_send_json_success( array(
				'message'  => esc_html__( 'Child theme installation skipped. Continue with parent theme...' ),
				'redirect' => ttw_interface()->success_page_link(),
			) );

		}

		/**
		 * Perfrorm child theme activation
		 *
		 * @return void
		 */
		public function activate_child() {
			$this->activate_theme( 'child', ttw_interface()->success_page_link() );
		}

		/**
		 * Perform child theme installation
		 *
		 * @return void
		 */
		public function install_child() {

			$this->verify_request();

			$theme_url = isset( $_REQUEST['child'] ) ? esc_url( $_REQUEST['child'] ) : false;

			if ( false !== $theme_url && false === strpos( $theme_url, 'http' ) ) {
				$theme_url = 'http:' . $theme_url;
			}

			/**
			 * Allow to rewrite child theme URL.
			 *
			 * @var string
			 */
			$theme_url = apply_filters( 'ttw_child_theme_url', $theme_url );

			ttw()->dependencies( array( 'install-api' ) );
			$api = ttw_install_api( $theme_url );

			$result = $api->do_theme_install();

			if ( true !== $result['success'] ) {
				wp_send_json_error( array(
					'message' => $result['message'],
				) );
			}

			/**
			 * Fires when child theme installed before sending result.
			 */
			do_action( 'tm_theme_wizard_child_installed' );

			wp_send_json_success( array(
				'message'     => $result['message'],
				'doNext'      => true,
				'nextRequest' => array(
					'action' => 'tm_theme_wizard_activate_child',
				),
			) );
		}

		/**
		 * Process parent theme activation.
		 *
		 * @return void
		 */
		public function activate_parent() {
			$this->activate_theme( 'parent', ttw_interface()->get_page_link( 'child-theme' ) );
		}

		/**
		 * Process parent theme installation
		 *
		 * @return void
		 */
		public function install_parent() {

			$this->verify_request();

			$install_data = get_transient( ttw()->slug() );
			$theme_url    = isset( $install_data['link'] ) ? $install_data['link'] : false;

			/**
			 * Allow to filter parent theme URL
			 *
			 * @var string
			 */
			$theme_url = apply_filters( 'tm_theme_wizard_parent_zip_url', $theme_url );

			if ( ! $theme_url ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'Theme URL was lost. Please refresh page and try again.', 'cherry-theme-wizard' ),
				) );
			}

			ttw()->dependencies( array( 'install-api' ) );
			$api = ttw_install_api( $theme_url );

			$result = $api->do_theme_install();

			if ( true !== $result['success'] ) {
				wp_send_json_error( array(
					'message' => $result['message'],
				) );
			}

			/**
			 * Fires when parent installed before sending result.
			 */
			do_action( 'tm_theme_wizard_parent_installed' );

			wp_send_json_success( array(
				'message'     => $result['message'],
				'doNext'      => true,
				'nextRequest' => array(
					'action' => 'tm_theme_wizard_activate_parent',
				),
			) );
		}

		/**
		 * Store parent theme data after successfull source renaming.
		 *
		 * @param  array $theme_data Theme data to store.
		 * @return void
		 */
		public function store_theme_data( $theme_data ) {

			if ( isset( $_REQUEST['action'] ) && 'tm_theme_wizard_install_parent' === $_REQUEST['action'] ) {
				update_option( ttw()->settings['options']['parent_data'], $theme_data );
				return;
			}

			if ( isset( $_REQUEST['action'] ) && 'tm_theme_wizard_install_child' === $_REQUEST['action'] ) {
				update_option( ttw()->settings['options']['child_data'], $theme_data );
				return;
			}

		}

		/**
		 * Perform theme activation by type.
		 *
		 * @param  string $type Paretn/child.
		 * @return void
		 */
		public function activate_theme( $type = 'parent', $redirect = false ) {

			$this->verify_request();

			if ( ! in_array( $type, array( 'parent', 'child' ) ) ) {
				$type = 'parent';
			}

			$option     = $type . '_data';
			$theme_data = get_option( ttw()->settings['options'][ $option ] );

			/**
			 * Fires before theme activation
			 */
			do_action( 'tm_theme_wizard_before_activation', $type, $theme_data );

			if ( empty( $theme_data['TextDomain'] ) ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'Can\'t find theme to activate', 'cherry-theme-wizard' ),
				) );
			}

			$theme_name    = $theme_data['TextDomain'];
			$current_theme = wp_get_theme();

			if ( $current_theme->stylesheet === $theme_name ) {
				$message = esc_html__( 'The theme is already active. Redirecting...', 'cherry-theme-wizard' );
			} else {
				$message = esc_html__( 'The theme is sucessfully activated. Redirecting...', 'cherry-theme-wizard' );
				switch_theme( $theme_name );
			}

			/**
			 * Fires after parent theme activation
			 */
			do_action( 'tm_theme_wizard_after_activation', $type, $theme_data );

			$response = apply_filters( 'ttw_activate_theme_response', array(
				'message'  => $message,
				'redirect' => $redirect,
			), $type );

			wp_send_json_success( $response );

		}

		/**
		 * Verfify template ID and orrder ID.
		 *
		 * @return void
		 */
		public function verify_data() {

			$this->verify_request();

			$template_id = isset( $_REQUEST['template_id'] ) ? esc_attr( $_REQUEST['template_id'] ) : false;
			$order_id    = isset( $_REQUEST['order_id'] ) ? esc_attr( $_REQUEST['order_id'] ) : false;

			if ( ! $template_id || ! $order_id ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'Please fill in Template ID and Order ID fields and try again', 'cherry-theme-wizard' ),
				) );
			}

			ttw()->dependencies( array( 'updater-api' ) );

			$api  = ttw_updater_api( $template_id, $order_id );
			$link = $api->get_latest_release_link();

			if ( ! $link ) {
				wp_send_json_error( array(
					'message' => $api->get_error(),
				) );
			} else {

				$install_data = array(
					'id'   => $template_id,
					'link' => $link,
				);

				set_transient( ttw()->slug(), $install_data, DAY_IN_SECONDS );
				wp_send_json_success( array(
					'message'     => esc_html__( 'Your template ID and order ID are verified. Downloading and installing theme...', 'cherry-theme-wizard' ),
					'doNext'      => true,
					'nextRequest' => array(
						'action' => 'tm_theme_wizard_install_parent',
					),
				) );
			}
		}

		/**
		 * Verify AJAX request.
		 *
		 * @return void
		 */
		public function verify_request() {

			if ( ! current_user_can( 'install_themes' ) ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'You are not allowed to access this', 'cherry-theme-wizard' ),
				) );
			}

			$nonce = isset( $_REQUEST['nonce'] ) ? esc_attr( $_REQUEST['nonce'] ) : false;

			if ( ! $nonce || ! wp_verify_nonce( $nonce, ttw()->slug() ) ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'Nonce verfictaion failed', 'cherry-theme-wizard' ),
				) );
			}
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}
	}

}

/**
 * Returns instance of TTW_Ajax_Handlers
 *
 * @return object
 */
function ttw_ajax_handlers() {
	return TTW_Ajax_Handlers::get_instance();
}

ttw_ajax_handlers();
