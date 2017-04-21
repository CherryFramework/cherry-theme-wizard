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

if ( ! class_exists( 'TTW_Updater_API' ) ) {

	/**
	 * Define TTW_Updater_API class
	 */
	class TTW_Updater_API {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Passed Template ID holder.
		 *
		 * @var int
		 */
		private $template_id = null;

		/**
		 * Passed Order ID holder.
		 *
		 * @var string
		 */
		private $order_id = null;

		/**
		 * Storage for error data.
		 *
		 * @var null
		 */
		private $error = null;

		/**
		 * Endpoint for updated list
		 *
		 * @var string
		 */
		protected $endpoints = array(
			'updates'  => 'http://updates.templatemonster.com/update/%template_id%/release',
			'download' => 'http://cloud.cherryframework.com/cherry5-update/wp-json/tm-dashboard-api/get-update?template=%template_id%&order_id=%order_id%&update_version=%ver%',
		);

		/**
		 * Constructor for the class
		 *
		 * @param int    $template_id Template ID from templatemonster.com.
		 * @param string $order_id    Order ID from user order details.
		 */
		function __construct( $template_id = null, $order_id = null ) {
			$this->template_id = $template_id;
			$this->order_id    = $order_id;
		}

		/**
		 * Returns link to latest template relese
		 *
		 * @return string
		 */
		public function get_latest_release_link() {

			$latest = $this->get_latest_release_version();

			if ( ! $latest ) {
				return false;
			}

			$request_data = array(
				'template_id' => $this->template_id,
				'order_id'    => $this->order_id,
				'ver'         => $latest,
			);

			$release_data = $this->api_call( 'download', $request_data );

			if ( empty( $release_data ) ) {
				$this->error = esc_html__( 'Internal error, please, try again later', 'cherry-theme-wizard' );
				return false;
			}

			if ( isset( $release_data['error'] ) && true === $release_data['error'] ) {
				$this->error = esc_html__( 'Invalid Order ID', 'cherry-theme-wizard' );
				return false;
			}

			$verification_data = array(
				'version'    => $latest,
				'verify'     => true,
				'update'     => $latest,
				'product-id' => $this->template_id,
				'order-id'   => $this->order_id,
			);

			set_transient( 'ttw_verification_data', $verification_data, DAY_IN_SECONDS );

			return $release_data['download_url'];
		}

		/**
		 * Returns error text
		 *
		 * @return void
		 */
		public function get_error() {
			return $this->error;
		}

		/**
		 * Get template releases
		 *
		 * @return string|bool
		 */
		public function get_latest_release_version() {

			$releases = $this->api_call( 'updates', array( 'template_id' => $this->template_id ) );

			if ( empty( $releases ) ) {
				return false;
			}

			if ( isset( $releases['errorMessage'] ) ) {
				$this->error = esc_html__( 'Template not found', 'cherry-theme-wizard' );
				return false;
			}

			if ( empty( $releases['content'] ) ) {
				$this->error = esc_html__( 'Template not found', 'cherry-theme-wizard' );
				return false;
			}

			$releases = $releases['content'];

			$this->ver = '1.0.0';

			array_walk( $releases, array( $this, '_compare_versions' ) );

			return $this->ver;
		}

		/**
		 * Compare existing releses versions and store larger into $this->ver holder.
		 *
		 * @param  array $item Release data list.
		 * @return void
		 */
		public function _compare_versions( $item ) {
			if ( ! isset( $item['version'] ) ) {
				return;
			}

			if ( version_compare( $item['version'], $this->ver, '>' ) ) {
				$this->ver = $item['version'];
			}
		}

		/**
		 * Perform an API call and return call body.
		 *
		 * @param  string $endpoint Requested endpoint.
		 * @param  array  $data     Request data.
		 * @return array
		 */
		public function api_call( $endpoint, $data ) {

			if ( ! isset( $this->endpoints[ $endpoint ] ) ) {
				return array();
			}

			$request = $this->endpoints[ $endpoint ];
			$search  = array_map( array( $this, '_map_macros' ), array_keys( $data ) );
			$replace = array_values( $data );
			$request = str_replace( $search, $replace, $request );

			$response = wp_remote_get( $request );
			$result   = wp_remote_retrieve_body( $response );

			$result = json_decode( $result, true );

			return $result;
		}

		/**
		 * Prepare macros
		 *
		 * @param  string $item
		 * @return string
		 */
		public function _map_macros( $item ) {
			return '%' . $item . '%';
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @param  int    $template_id Template ID from templatemonster.com.
		 * @param  string $order_id    Order ID from user order details.
		 * @return object
		 */
		public static function get_instance( $template_id = null, $order_id = null ) {
			return new self( $template_id, $order_id );
		}
	}

}

/**
 * Returns instance of TTW_Updater_API
 *
 * @param  int    $template_id Template ID from templatemonster.com.
 * @param  string $order_id    Order ID from user order details.
 * @return object
 */
function ttw_updater_api( $template_id = null, $order_id = null ) {
	return TTW_Updater_API::get_instance( $template_id, $order_id );
}
