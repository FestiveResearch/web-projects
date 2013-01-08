<?php
/**
 * WooCommerce Twilio SMS Notifications AJAX class
 *
 * Handles all AJAX actions
 *
 *
 * @package		WooCommerce Twilio SMS Notifications
 * @subpackage  WC_Twilio_SMS_AJAX
 * @category    Class
 * @author		Max Rice
 * @since		1.0
 */

class WC_Twilio_SMS_AJAX {

	/**
	 * Adds required wp_ajax_* hooks
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public static function init() {

		add_action( 'wp_ajax_woocommerce_twilio_sms_send_test_sms', __CLASS__ . '::send_test_sms' );
	}

	public static function send_test_sms() {

		self::verify_request( $_POST['security'], WC_Twilio_SMS::$settings_prefix . 'send_test_sms' );

		// sanitize input
		$mobile_number		= $_POST['mobile_number'];
		$message			= $_POST['message'];

		try {
			WC_Twilio_SMS::$api->send( $mobile_number, $message );
			die( __( 'Test message sent successfully', WC_Twilio_SMS::$text_domain ) );
		}
		catch( Exception $e ) {

			die( sprintf( __( 'Error sending SMS: %s', WC_Twilio_SMS::$text_domain ), $e->getMessage() ) );
		}
	}

	/**
	 * Verifies AJAX request is valid
	 *
	 * @access public
	 * @since  1.0
	 * @param string $nonce
	 * @param string $action
	 * @return void|bool
	 */
	private static function verify_request( $nonce, $action ) {

			if( ! is_admin() || ! current_user_can( 'edit_posts' ) )
				wp_die( __( 'You do not have sufficient permissions to access this page.', WC_Twilio_SMS::$text_domain ) );

			if( ! wp_verify_nonce( $nonce, $action ) )
				wp_die( __( 'You have taken too long, please go back and try again.', WC_Twilio_SMS::$text_domain ) );

			return true;
	}

} // end class

// Initialize class
WC_Twilio_SMS_AJAX::init();

// end file