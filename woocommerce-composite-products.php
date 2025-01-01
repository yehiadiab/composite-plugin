<?php
/*
* Plugin Name: WooCommerce Composite Products
* Plugin URI: https://woocommerce.com/products/composite-products/
* Description: Create personalized product kits and configurable products.
* Version: 10.2.3
* Author: Woo
* Author URI: https://woocommerce.com/
*
* Woo: 216836:0343e0115bbcb97ccd98442b8326a0af
*
* Text Domain: woocommerce-composite-products
* Domain Path: /languages/
*
* Requires PHP: 7.4
*
* Requires at least: 6.2
* Tested up to: 6.7
*
* WC requires at least: 8.2
* WC tested up to: 9.2
*
* Requires Plugins: woocommerce
*
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

set_transient('_woocommerce_helper_product_usage_notice_rules', '');


/**
 * Main plugin class.
 *
 * @class    WC_Composite_Products
 * @version  10.2.3
 */
class WC_Composite_Products {

	/**
	 * The plugin version
	 *
	 * @var string
	 */
	public $version = '10.2.3';

	/**
	 * The minimum required WooCommerce version.
	 *
	 * @var string
	 */
	public $required = '8.2.0';

	/**
	 * The product ID for Composite Products at WooCommerce.com
	 *
	 * @var int
	 *
	 * @since 10.1.1
	 */
	public $wccom_product_id = 216836;

	/**
	 * The single instance of the class.
	 *
	 * @var WC_Composite_Products
	 *
	 * @since 3.2.3
	 */
	protected static $_instance = null;

	/**
	 * Product data object.
	 *
	 * @var WC_CP_Product_Data
	 *
	 * @since 9.0.0
	 */
	public $product_data;

	/**
	 * Main WC_Composite_Products instance.
	 *
	 * Ensures only one instance of WC_Composite_Products is loaded or can be loaded - @see 'WC_CP()'.
	 *
	 * @since  3.2.3
	 *
	 * @static
	 * @return WC_Composite_Products - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 3.2.3
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce-composite-products' ), '3.2.3' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 3.2.3
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce-composite-products' ), '3.2.3' );
	}

	/**
	 * Contructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Entry point.
		add_action( 'plugins_loaded', array( $this, 'initialize_plugin' ), 9 );
	}

	/**
	 * Auto-load in-accessible properties.
	 *
	 * @param  mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'compatibility', 'cart', 'order', 'display' ) ) ) {
			$classname = 'WC_CP_' . ucfirst( $key );
			return call_user_func( array( $classname, 'instance' ) );
		}
	}

	/**
	 * Gets the plugin url.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Gets the plugin path.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Plugin base path name getter.
	 *
	 * @since  3.7.0
	 *
	 * @return string
	 */
	public function plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	/**
	 * Indicates whether the plugin has been fully initialized.
	 *
	 * @since  6.2.0
	 *
	 * @return boolean
	 */
	public function plugin_initialized() {
		return class_exists( 'WC_CP_Helpers' );
	}

	/**
	 * Define constants if not present.
	 *
	 * @since  6.2.0
	 *
	 * @return boolean
	 */
	protected function maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Plugin version getter.
	 *
	 * @since  3.14.0
	 *
	 * @param  boolean $base
	 * @param  string  $version
	 * @return string
	 */
	public function plugin_version( $base = false, $version = '' ) {

		$version = $version ? $version : $this->version;

		if ( $base ) {
			$version_parts = explode( '-', $version );
			$version       = sizeof( $version_parts ) > 1 ? $version_parts[0] : $version;
		}

		return $version;
	}

	/**
	 * Fire in the hole!
	 */
	public function initialize_plugin() {

		$this->define_constants();
		$this->maybe_create_store();

		// WC version sanity check.
		if ( ! function_exists( 'WC' ) || version_compare( WC()->version, $this->required ) < 0 ) {
			/* translators: Required version. */
			$notice = sprintf( __( 'WooCommerce Composite Products requires at least WooCommerce <strong>%s</strong>.', 'woocommerce-composite-products' ), $this->required );
			require_once WC_CP_ABSPATH . 'includes/admin/class-wc-cp-admin-notices.php';
			WC_CP_Admin_Notices::add_notice( $notice, 'error' );

			return false;
		}

		// PHP version check.
		if ( ! function_exists( 'phpversion' ) || version_compare( phpversion(), '7.4.0', '<' ) ) {
			/* translators: %1$s: PHP version, %2$s: Documentation link. */
			$notice = sprintf(
				__(
					'WooCommerce Composite Products requires at least PHP <strong>%1$s</strong>. Learn <a href="%2$s">how to update PHP</a>.',
					'woocommerce-composite-products'
				),
				'7.4.0',
				'https://woocommerce.com/document/how-to-update-your-php-version/'
			);
			require_once WC_CP_ABSPATH . 'includes/admin/class-wc-cp-admin-notices.php';
			WC_CP_Admin_Notices::add_notice( $notice, 'error' );
		}

		$this->includes();

		WC_CP_Compatibility::instance();
		WC_CP_Cart::instance();
		WC_CP_Order::instance();
		WC_CP_Display::instance();

		// Load translations hook.
		add_action( 'init', array( $this, 'load_translation' ) );
	}

	/**
	 * Constants.
	 */
	public function define_constants() {

		$this->maybe_define_constant( 'WC_CP_VERSION', $this->version );
		$this->maybe_define_constant( 'WC_CP_SUPPORT_URL', 'https://woocommerce.com/my-account/marketplace-ticket-form/' );
		$this->maybe_define_constant( 'WC_CP_ABSPATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );

		if ( defined( 'WC_CP_DEBUG_QUERY_TRANSIENTS' ) ) {
			/**
			 * 'WC_CP_DEBUG_QUERY_TRANSIENTS' constant.
			 *
			 * Disables the query transients cache.
			 */
			$this->maybe_define_constant( 'WC_CP_DEBUG_QUERY_TRANSIENTS', true );
		}

		if ( defined( 'WC_CP_DEBUG_RUNTIME_CACHE' ) ) {
			/**
			 * 'WC_CP_DEBUG_RUNTIME_CACHE' constant.
			 *
			 * Disables the runtime object cache.
			 */
			$this->maybe_define_constant( 'WC_CP_DEBUG_RUNTIME_CACHE', true );
		}
	}

	/**
	 * A simple dumb datastore for sharing information accross our plugins.
	 *
	 * @since  7.0.3
	 *
	 * @return void
	 */
	private function maybe_create_store() {
		if ( ! isset( $GLOBALS['sw_store'] ) ) {
			$GLOBALS['sw_store'] = array();
		}
	}

	/**
	 * Includes.
	 */
	public function includes() {

		// Product data.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-product-data.php';

		$this->product_data = WC_CP_Product_Data::instance();

		// Class containing extensions compatibility functions and filters.
		require_once WC_CP_ABSPATH . 'includes/compatibility/class-wc-cp-compatibility.php';

		// Install.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-install.php';

		// CRUD.
		require_once WC_CP_ABSPATH . 'includes/data/class-wc-cp-data.php';

		// CP functions.
		require_once WC_CP_ABSPATH . 'includes/wc-cp-functions.php';

		// Composite widget.
		require_once WC_CP_ABSPATH . 'includes/wc-cp-widget-functions.php';

		require_once WC_CP_ABSPATH . 'includes/wc-cp-generator-functions.php';

		// Handles component option queries.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-query.php';

		// Component abstraction.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-component.php';

		// Component view state.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-component-view.php';

		// Query string compressor and expanded.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-query-string.php';

		// Composited product wrapper.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-product.php';

		// Filters and functions to support the "composited product" concept.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-products.php';

		// Composite products Scenarios API.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-scenario.php';
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-scenarios-manager.php';

		// Helper functions.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-helpers.php';

		// Composite products AJAX handlers.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-ajax.php';

		// Composite product class.
		require_once WC_CP_ABSPATH . 'includes/class-wc-product-composite.php';

		// Stock manager.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-stock-manager.php';

		// Cart-related functions and filters.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-cart.php';

		// Order-related functions and filters.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-order.php';

		// Order-again functions and filters.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-order-again.php';

		// Coupon-related composite functions and hooks.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-coupon.php';

		// Front-end functions and filters.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-display.php';

		// REST API hooks.
		require_once WC_CP_ABSPATH . 'includes/api/class-wc-cp-rest-api.php';

		// Notices handling.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-notices.php';

		// Analytics.
		require_once WC_CP_ABSPATH . 'includes/admin/analytics/class-wc-cp-admin-analytics.php';

		// Tracker.
		require_once WC_CP_ABSPATH . 'includes/class-wc-cp-tracker.php';

		// Blocks.
		require_once WC_CP_ABSPATH . 'includes/blocks/class-wc-cp-summary-block.php';

		// Admin functions and meta-boxes.
		if ( is_admin() ) {
			$this->admin_includes();
		}
	}

	/**
	 * Loads the Admin filters / hooks.
	 */
	private function admin_includes() {

		// Admin notices handling.
		require_once WC_CP_ABSPATH . 'includes/admin/class-wc-cp-admin-notices.php';

		// Admin hooks.
		require_once WC_CP_ABSPATH . 'includes/admin/class-wc-cp-admin.php';

		// Tracks event
		require_once WC_CP_ABSPATH . 'includes/admin/class-wc-cp-admin-tracks.php';

		// Admin usage restrictions.
		require_once WC_CP_ABSPATH . 'includes/admin/class-wc-cp-admin-restrictions.php';
	}

	/**
	 * Load textdomain.
	 */
	public function load_translation() {
		load_plugin_textdomain( 'woocommerce-composite-products', false, dirname( $this->plugin_basename() ) . '/languages/' );
		// Subscribe to automated translations.
		add_filter( 'woocommerce_translations_updates_for_' . basename( __FILE__, '.php' ), '__return_true' );
	}

	/**
	 * Returns URL to a doc or support resource.
	 *
	 * @since  7.0.3
	 *
	 * @param  string $handle
	 * @return string
	 */
	public function get_resource_url( $handle ) {

		$resource = false;

		if ( 'pricing-options' === $handle ) {
			$resource = 'https://woocommerce.com/document/composite-products/composite-products-configuration/#pricing';
		} elseif ( 'shipping-options' === $handle ) {
			$resource = 'https://woocommerce.com/document/composite-products/composite-products-configuration/#shipping';
		} elseif ( 'catalog-price-option' === $handle ) {
			$resource = 'https://woocommerce.com/document/composite-products/composite-products-configuration/#catalog-price';
		} elseif ( 'update-php' === $handle ) {
			$resource = 'https://woocommerce.com/document/how-to-update-your-php-version/';
		} elseif ( 'docs-contents' === $handle ) {
			$resource = 'https://woocommerce.com/document/composite-products/';
		} elseif ( 'guide' === $handle ) {
			$resource = 'https://woocommerce.com/document/composite-products/composite-products-configuration/';
		} elseif ( 'advanced-guide' === $handle ) {
			$resource = 'https://woocommerce.com/document/composite-products/composite-products-advanced-configuration/';
		} elseif ( 'max-input-vars' === $handle ) {
			$resource = 'https://woocommerce.com/document/composite-products/composite-products-faq/#faq_items_dont_save';
		} elseif ( 'updating' === $handle ) {
			$resource = 'https://woocommerce.com/document/how-to-update-woocommerce/';
		} elseif ( 'ticket-form' === $handle ) {
			$resource = WC_CP_SUPPORT_URL;
		}

		return $resource;
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @since 8.4.0
	 *
	 * @param  string $file
	 * @return string
	 */
	public function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}
		return $this->plugin_version();
	}

	/**
	 * Handle plugin activation process.
	 *
	 * @since  8.5.1
	 *
	 * @return void
	 */
	public function on_activation() {

		// Add daily maintenance process.
		if ( ! wp_next_scheduled( 'wc_cp_daily' ) ) {
			wp_schedule_event( time() + 10, 'daily', 'wc_cp_daily' );
		}

		// Add hourly maintenance process.
		if ( ! wp_next_scheduled( 'wc_cp_hourly' ) ) {
			wp_schedule_event( time() + 10, 'hourly', 'wc_cp_hourly' );
		}
	}

	/**
	 * Handle plugin deactivation process.
	 *
	 * @since  8.5.1
	 *
	 * @return void
	 */
	public function on_deactivation() {
		// Clear daily maintenance process.
		wp_clear_scheduled_hook( 'wc_cp_daily' );
		wp_clear_scheduled_hook( 'wc_cp_hourly' );
	}
}

/**
 * Returns the main instance of WC_Composite_Products to prevent the need to use globals.
 *
 * @since  3.2.3
 *
 * @return WC_Composite_Products
 */
function WC_CP() {
	return WC_Composite_Products::instance();
}

$GLOBALS['woocommerce_composite_products'] = WC_CP();

register_activation_hook( __FILE__, array( WC_CP(), 'on_activation' ) );
register_deactivation_hook( __FILE__, array( WC_CP(), 'on_deactivation' ) );
