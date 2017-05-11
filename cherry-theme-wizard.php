<?php
/**
 * Plugin Name: Cherry Theme Wizard
 * Plugin URI:  http://cherryframework.com/plugins/
 * Description: Themes installation wizard.
 * Version:     1.0.0
 * Author:      Cherry Team
 * Author URI:  http://cherryframework.com/
 * Text Domain: cherry-theme-wizard
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

/**
 * Main plugin class
 *
 * @package   TM_Theme_Wizard
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'TM_Theme_Wizard' ) ) {

	/**
	 * Define TM_Theme_Wizard class
	 */
	class TM_Theme_Wizard {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Plugin base url
		 *
		 * @var string
		 */
		private $url = null;

		/**
		 * Plugin base path
		 *
		 * @var string
		 */
		private $path = null;

		/**
		 * Menu page slug.
		 * @var string
		 */
		private $slug = 'cherry-theme-wizard';

		/**
		 * Nonce action name.
		 * @var string
		 */
		public $_nonce = 'tm-wizard-nonce';

		/**
		 * Plugin version
		 *
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * Plugin files list
		 *
		 * @var array
		 */
		private $files = array(
			'interface'   => 'includes/class-ttw-interface.php',
			'ajax'        => 'includes/class-ttw-ajax-handlers.php',
			'updater-api' => 'includes/class-ttw-updater-api.php',
			'child-api'   => 'includes/class-ttw-child-api.php',
			'install-api' => 'includes/class-ttw-install-api.php',
			'compat'      => 'includes/class-ttw-compat.php',
		);

		/**
		 * Settings list
		 *
		 * @var array
		 */
		public $settings = array();

		/**
		 * Plugin functions prefix
		 *
		 * @var string
		 */
		public $prefix = 'ttw_';

		/**
		 * Constructor for the class
		 */
		function __construct() {

			if ( ! is_admin() ) {
				return;
			}

			$this->settings = array(
				'options' => array(
					'parent_data' => 'cherry-theme-wizard-installed-parent',
					'child_data'  => 'cherry-theme-wizard-installed-child',
				),
			);

			add_action( 'after_setup_theme', array( $this, 'hooks' ) );

			register_activation_hook( __FILE__,   array( $this, '_activation' ) );
			register_deactivation_hook( __FILE__, array( $this, '_deactivation' ) );

		}

		/**
		 * Attach required hooks
		 *
		 * @return void
		 */
		public function hooks() {

			add_action( 'init',                  array( $this, 'lang' ), 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			add_action( 'init', array( $this, 'activation_redirect' ) );

			$this->dependencies( array( 'interface', 'ajax', 'compat' ) );
			add_action( 'admin_menu', array( ttw_interface(), 'register_page' ) );
			add_action( 'admin_menu', array( ttw_interface(), 'register_page' ) );
		}

		/**
		 * Load required dependencies if not loaded before
		 *
		 * @param  array $load Array of required dependencies to load
		 * @return void
		 */
		public function dependencies( $load = array() ) {

			foreach ( $load as $handle ) {
				if ( isset( $this->files[ $handle ] ) && ! function_exists( $this->prefix . $handle ) ) {
					require_once $this->path( $this->files[ $handle ] );
				}
			}

		}

		/**
		 * Create wizard nonce
		 *
		 * @return void
		 */
		public function nonce() {
			wp_create_nonce( $this->_nonce );
		}

		/**
		 * Verify nonce
		 *
		 * @param  string $nonce Nonce value.
		 * @return bool
		 */
		public function verify_nonce( $nonce ) {
			return wp_verify_nonce( $nonce, $this->_nonce );
		}

		/**
		 * Do stuff on the wiard activation.
		 *
		 * @return void
		 */
		public function _activation() {
			set_transient( $this->slug() . '_redirect', true, 120 );
		}

		/**
		 * Do stuff on wizard deactivation
		 *
		 * @return void
		 */
		public function _deactivation() {
			delete_transient( $this->slug() . '_redirect' );
		}

		/**
		 * Loads the translation files.
		 *
		 * @since 1.0.0
		 */
		public function lang() {
			load_plugin_textdomain( 'cherry-theme-wizard', false, $this->path( 'languages' ) );
		}

		/**
		 * Redirect to installation page after activation
		 *
		 * @return void
		 */
		public function activation_redirect() {

			$enbled = get_transient( $this->slug() . '_redirect' );
			$enbled = apply_filters( 'ttw_activation_redirect_enabled', $enbled );

			if ( ! $enbled ) {
				return;
			}

			delete_transient( $this->slug() . '_redirect' );
			wp_redirect( ttw_interface()->get_page_link() );
			die();
		}

		/**
		 * Returns plugin slug
		 *
		 * @return string
		 */
		public function slug() {
			return $this->slug;
		}

		/**
		 * Returns path to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function path( $path = null ) {

			if ( ! $this->path ) {
				$this->path = trailingslashit( plugin_dir_path( __FILE__ ) );
			}

			return $this->path . $path;

		}

		/**
		 * Returns url to file or dir inside plugin folder
		 *
		 * @param  string $path Path inside plugin dir.
		 * @return string
		 */
		public function url( $path = null ) {

			if ( ! $this->url ) {
				$this->url = trailingslashit( plugin_dir_url( __FILE__ ) );
			}

			return $this->url . $path;

		}

		/**
		 * Register plugin assets
		 *
		 * @return void
		 */
		public function register_assets() {

			$handle = $this->slug();

			wp_register_script(
				$handle,
				$this->url( 'assets/js/theme-wizard.js' ),
				array( 'wp-util' ),
				$this->version,
				true
			);

			wp_register_style(
				$handle,
				$this->url( 'assets/css/theme-wizard.css' ),
				false,
				$this->version
			);
		}

		/**
		 * Enqueue required assets
		 *
		 * @return void
		 */
		public function enqueue_assets( $hook ) {

			if ( ! $this->is_wizard() ) {
				return;
			}

			wp_enqueue_script( $this->slug() );

			wp_localize_script( $this->slug(), 'tmThemeWizardSettings', array(
				'nonce'  => wp_create_nonce( $this->slug() ),
				'errors' => array(
					'empty' => esc_html__( '* Please, fill this field', 'cherry-theme-wizard' ),
				),
			) );

			wp_enqueue_style( $this->slug() );

			/**
			 * Hook fires on wizard assets enqueue
			 */
			do_action( 'ttw_enqueue_assets' );
		}

		/**
		 * Check if is wizard-related page.
		 *
		 * @param  bool|int $step Current step.
		 * @return bool
		 */
		public function is_wizard( $step = false ) {

			if ( ! isset( $_GET['page'] ) || $this->slug() !== $_GET['page'] ) {
				return false;
			}

			return true;
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
 * Returns instance of TM_Theme_Wizard
 *
 * @return object
 */
function ttw() {
	return TM_Theme_Wizard::get_instance();
}

ttw();
