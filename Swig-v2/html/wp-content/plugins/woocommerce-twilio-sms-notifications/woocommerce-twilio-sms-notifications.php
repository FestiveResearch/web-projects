<?php
/**
 * Plugin Name: WooCommerce Twilio SMS Notifications
 * Plugin URI: http://www.woothemes.com/product/twilio-sms-notifications/
 * Description: Adds Twilio SMS Notifications to WooCommerce
 * Version: 1.0.3
 * Author: Max Rice
 * Author URI: http://www.maxrice.com
 * 
 * 
 * Copyright: © 2012 Max Rice (max@maxrice.com)
 * 	
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * @package		WooCommerce Twilio SMS Notifications
 * @category	Class
 * @author		Max Rice
 * @since		1.0
 */

/**
 * Plugin Setup
 *
 * @since 1.0
 */

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '2b17098ebabfc218a552515202cf973a', '132190' );

// Check if WooCommerce is active and deactivate extension if it's not
if ( ! is_woocommerce_active() ) {
	add_action( 'admin_notices', 'WC_Twilio_SMS::woocommerce_inactive_notice' );
	return;
}

/**
 * Main SMS Notifications Class
 * 
 * @since 1.0
 */		
class WC_Twilio_SMS {

	/** @var string plugin text domain */
	public static $text_domain = 'wc_twilio_sms_notifications';

	/** @var string options table prefix */
	public static $settings_prefix = 'wc_twilio_sms_';

	/** @var string order meta prefix */
	public static $meta_prefix = '_wc_twilio_sms_';

	/** @var string plugin version number */
	public static $version = '1.0.1';

	/** @var string plugin file path without trailing slash */
	public static $plugin_path;

	/** @var string plugin uri */
	public static $plugin_url;

	/** @var \WC_Twilio_SMS_API class instance */
	public static $api;

	/**
	 * Setup main plugin class
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public static function init() {

		// Plugin Setup
		self::$plugin_path	= untrailingslashit( plugin_dir_path( __FILE__ ) );
		self::$plugin_url	= plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );

		// Load Twilio API
		self::init_api();

		// Load classes
		add_action( 'woocommerce_loaded', __CLASS__ . '::load_wc_dependent_classes' );

		/** Frontend Hooks */

		// Add opt-in checkbox to checkout
		add_action( 'woocommerce_after_checkout_billing_form', __CLASS__ . '::add_opt_in_checkbox' );

		// Process opt-in checkbox after order is processed
		add_action( 'woocommerce_checkout_update_order_meta', __CLASS__ . '::process_opt_in_checkbox', 10, 2 );

		// Customer order status change hooks
		$order_statuses = array( 'pending', 'failed', 'on-hold', 'processing', 'completed', 'refunded', 'cancelled' );
		foreach( $order_statuses as $status )
			add_action( 'woocommerce_order_status_' . $status, 'WC_Twilio_SMS_Order::automated_customer_notification' );

		// Admin new order hooks
		$admin_order_statuses = array( 'pending_to_on-hold', 'pending_to_processing', 'pending_to_completed', 'failed_to_on-hold', 'failed_to_processing', 'failed_to_completed' );
		foreach( $admin_order_statuses as $status )
			add_action( 'woocommerce_order_status_' . $status, 'WC_Twilio_SMS_Order::admin_notification' );

		/** Admin */
		if( is_admin() ) {

			// load admin classes (including AJAX)
			self::admin_includes();

			// add a 'Configure' link to the plugin action links
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), __CLASS__ . '::plugin_configure_link' );
		}

		// Load translation files
		add_action( 'plugins_loaded', __CLASS__ . '::load_translation' );
	}


	/**
	 * Adds checkbox to checkout page to opt-in to SMS notifications
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public static function add_opt_in_checkbox() {

		// use previous value or default value when loading checkout page
		if( isset( $_POST[self::$settings_prefix . 'optin'] ) && ! empty( $_POST[self::$settings_prefix . 'optin'] ) )
			$value = esc_attr( $_POST[self::$settings_prefix . 'optin'] );
		else
			$value = ( 'checked' == get_option( self::$settings_prefix . 'checkout_optin_checkbox_default', 'unchecked' ) ) ? 1 : 0;

		// output checkbox
		woocommerce_form_field( self::$settings_prefix . 'optin', array(
			'type' 			=> 'checkbox',
			'class'			=> array( 'form-row-wide' ),
			'label' 		=> get_option( self::$settings_prefix . 'checkout_optin_checkbox_label' )
		), $value );
	}

	/**
	 * Save opt-in as order meta
	 *
	 * @access public
	 * @since  1.0
	 * @param int $order_id
	 * @param array $posted
	 * @return void
	 */
	public static function process_opt_in_checkbox( $order_id, $posted ) {

		if( isset( $_POST[self::$settings_prefix . 'optin'] ) && ! empty( $_POST[self::$settings_prefix . 'optin'] ) )
			update_post_meta( $order_id, self::$meta_prefix . 'optin', 1 );

	}

	/**
	 * Initializes the WC Twilio API class
	 *
	 * @access public
	 * @since  1.0
	 * @see \WC_Twilio_SMS_API class
	 * @return void
	 */
	public static function init_api() {

		require_once( 'classes/class-wc-twilio-sms-api.php' );

		$account_sid = get_option( self::$settings_prefix . 'account_sid' );
		$auth_token = get_option( self::$settings_prefix . 'auth_token' );
		$from_number = get_option( self::$settings_prefix . 'from_number' );

		self::$api = new WC_Twilio_SMS_API( $account_sid, $auth_token, $from_number );
	}

	/**
	 * Loads classes which require WooCommerce classes
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public static function load_wc_dependent_classes() {

		// WC_Twilio_SMS_Order class
		require_once( 'classes/class-wc-twilio-sms-order.php' );

	}

	/**
	 * Loads the Admin & AJAX classes
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	private static function admin_includes() {

		require_once( 'admin/class-wc-twilio-sms-admin.php' );
		require_once( 'admin/class-wc-twilio-sms-ajax.php' );

	}

	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @access public
	 * @since 1.0
	 * @param array $actions associative array of action names to anchor tags
	 * @return array associative array of plugin action links
	 */
	public static function plugin_configure_link( $actions ) {
		// add the link to the front of the actions list
		return ( array_merge( array( 'configure' => sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=woocommerce_settings&tab=twilio_sms' ), __( 'Configure', self::$text_domain ) ) ),
			$actions )
		);
	}

	/**
	 * Adds an inactive notice when plugin is active but WooCommerce is not
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public static function woocommerce_inactive_notice() {
		?>
    <div id="message" class="error">
        <p><?php printf( __( '%sWooCommerce Twilio SMS Notifications is inactive.%s The %sWooCommerce plugin%s must be active for the WooCommerce Twilio SMS Notifications to work. Please %sinstall & activate WooCommerce%s', WC_Twilio_SMS::$text_domain ),
			'<strong>', '</strong>',
			'<a href="http://wordpress.org/extend/plugins/woocommerce/">','</a>',
		  '<a href="' . admin_url( 'plugins.php' ) . '">', '</a>.'
		);
			?>
        </p>
    </div>
	<?php
	}

	/**
	 * Load plugin text domain
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public static function load_translation() {

		load_plugin_textdomain( self::$text_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	}

	/**
	 * Log errors to WooCommerce error log if error logging is enabled
	 *
	 * @access public
	 * @since  1.0
	 * @param string $error : error message
	 * @return void
	 */
	public static function log_error( $error ) {
		global $woocommerce;

		if( 'yes' == get_option( self::$settings_prefix . 'log_errors' ) ) {

			$log = $woocommerce->logger();

			$log->add( 'twilio-sms-notifications', $error );
		}
	}
			
			
			
} // end class

WC_Twilio_SMS::init();

//end file