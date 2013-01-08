<?php
/**
 * Twilio SMS Notifications Order Class
 *
 * Handles SMS sending Admin & Customer Notifications, as well as manual SMS messages from Order page
 *
 *
 * @package		WooCommerce Twilio SMS Notifications
 * @subpackage	WC_Twilio_SMS_Order
 * @category	Class
 * @author		Max Rice
 * @since		1.0
 */

class WC_Twilio_SMS_Order  {

	/**
	 * Send admin SMS notifications
	 *
	 * @access public
	 * @since  1.0
	 * @param string $order_id
	 * @return void
	 */
	public static function admin_notification( $order_id ) {

		// Check if sending admin SMS updates for new orders
		if( 'yes' == get_option( WC_Twilio_SMS::$settings_prefix . 'enable_admin_sms' ) ) :

			// instantiate order
			$order = new WC_Order( $order_id );

			// get message template
			$message = get_option( WC_Twilio_SMS::$settings_prefix . 'admin_sms_template', '' );

			// replace template variables
			$message = self::replace_message_variables( $message, $order );

			// shorten URLs if enabled
			if( 'yes' == get_option( WC_Twilio_SMS::$settings_prefix . 'shorten_urls' ) )
				$message = self::shorten_urls( $message );

			// get admin phone number (s). Note: explode returns an array even for single elements
			$recipients = explode( ',', trim( get_option( WC_Twilio_SMS::$settings_prefix . 'admin_sms_recipients' ) ) );

			// send the SMS to each recipient
			if( ! empty( $recipients ) ) {
				foreach( $recipients as $recipient )
					self::send_sms( $recipient, $message, $order, false );
			}

		endif;
	}

	/**
	 * Sends SMS to customer from 'Send an SMS' metabox on Orders page
	 *
	 * @access public
	 * @since  1.0
	 * @param string $order_id
	 * @param string $message
	 * @return void
	 */
	public static function manual_customer_notification( $order_id, $message ) {

		$order = new WC_Order( $order_id );

		// shorten URLs if enabled
		if( 'yes' == get_option( WC_Twilio_SMS::$settings_prefix . 'shorten_urls' ) )
			$message = self::shorten_urls( $message );

		// send the SMS!
		self::send_sms( $order->billing_phone, $message, $order );
	}

	/**
	 * Sends customer SMS notifications on order status changes
	 *
	 * @filter : wc_twilio_sms_customer_sms_before_variable_replace
	 * @filter : wc_twilio_sms_customer_sms_after_variable_replace
	 * @filter : wc_twilio_sms_customer_phone
	 * @access public
	 * @since  1.0
	 * @param string $order_id
	 * @return void
	 */
	public static function automated_customer_notification( $order_id ) {

		// get checkbox opt-in label
		$optin = get_option( WC_Twilio_SMS::$settings_prefix . 'checkout_optin_checkbox_label', '' );

		// check if opt-in checkbox is enabled
		if( ! empty( $optin ) ) {

			// get opt-in meta for order
			$optin = get_post_meta( $order_id, WC_Twilio_SMS::$meta_prefix . 'optin', true );

			// check if customer has opted-in
			if( empty( $optin ) )
				// no meta set, so customer has not opted in
				return;
		}

		// instantiate order
		$order = new WC_Order( $order_id );

		// Check if sending SMS updates for this order's status
		if( 'yes' == get_option( WC_Twilio_SMS::$settings_prefix . 'send_sms_' . $order->status ) ) :

			// get message template
			$message = get_option( WC_Twilio_SMS::$settings_prefix . $order->status . '_sms_template', '' );

			// use the default template if status-specific one is blank
			if( empty( $message ) )
				$message = get_option( WC_Twilio_SMS::$settings_prefix . 'default_sms_template' );

			// allow modification of message before variable replace (add additional variables, etc)
			$message = apply_filters( 'wc_twilio_sms_customer_sms_before_variable_replace', $message, $order );

			// replace template variables
			$message = self::replace_message_variables( $message, $order );

			// allow modification of message after variable replace
			$message = apply_filters( 'wc_twilio_sms_customer_sms_after_variable_replace', $message, $order );

			// allow modification of the "to" phone number
			$phone = apply_filters( 'wc_twilio_sms_customer_phone', $order->billing_phone, $order );

			// shorten URLs if enabled
			if( 'yes' == get_option( WC_Twilio_SMS::$settings_prefix . 'shorten_urls' ) )
				$message = self::shorten_urls( $message );

			// send the SMS!
			self::send_sms( $phone, $message, $order );

		endif;

	}


	/**
	 * Actually sends SMS via API wrapper
	 *
	 * @access public
	 * @since  1.0
	 * @param string $to
	 * @param string $message
	 * @param \WC_Order $order
	 * @param bool $customer_notification : order note is added if true
	 * @return void
	 */
	private static function send_sms( $to, $message, $order, $customer_notification = true ) {

		// Default status for SMS message, on error this is replaced with error message
		$status = __( 'Sent', WC_Twilio_SMS::$text_domain );

		// Timestamp of SMS is current time
		$sent_timestamp =  time();

		// error flag
		$error = false;

		try {
			// send the SMS via API
			$response = WC_Twilio_SMS::$api->send( $to, $message, $order->billing_country );

			// use the timestamp from twilio if available
			$sent_timestamp = ( isset( $response['date_created'] ) ) ? strtotime( $response['date_created'] ) : $sent_timestamp;

			// use twilio formatted number if available
			$to = ( isset( $response['to'] ) ) ? $response['to'] : $to;
		}

		catch( Exception $e ) {

			// Set status to error message
			$status = $e->getMessage();

			// set error flag
			$error = true;

			// log to PHP error log
			WC_Twilio_SMS::log_error( $e->getMessage() );
		}

		// Add formatted order note
		if( $customer_notification )
			$order->add_order_note( self::format_order_note( $to, $sent_timestamp, $message, $status, $error ) );

	}

	/**
	 * Extract URLs from SMS message and replace them with shorten URLs via callback
	 *
	 * @access public
	 * @since  1.0
	 * @param string $message : SMS message
	 * @return string $message
	 */
	private static function shorten_urls( $message ) {

		// regex pattern source : http://daringfireball.net/2010/07/improved_regex_for_matching_urls
		$pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";

		// find each URL and replacing using callback
		$message = preg_replace_callback( $pattern, 'self::shorten_url', $message );

		// return message with shortened URLs
		return $message;
	}

	/**
	 * Callback for shorten_urls() preg_replace
	 * By default, uses Google URL Shortener
	 *
	 * @filter wc_twilio_sms_shorten_url
	 * @access public
	 * @since  1.0
	 * @param array $matches : matches found via preg_replace
	 * @return shortened url
	 */
	private static function shorten_url( $matches ) {

		// get first match
		$url = reset( $matches );

		// shorten via google
		$shortened_url = self::google_shorten_url( $url );

		// allow use of other URL shorteners
		return apply_filters( 'wc_twilio_sms_shorten_url', $shortened_url, $url );

	}


	/**
	 * Shortens a given URL via Google URL Shortener
	 * @see : https://developers.google.com/url-shortener/v1/getting_started
	 *
	 * @access public
	 * @since  1.0
	 * @param string $url : URL to shorten
	 * @return string : shortened URL
	 */
	private static function google_shorten_url( $url ) {

		// set wp_remote_post arguments
		$args = array(
			'method'		=> 'POST',
			'timeout'		=> '10',
			'redirection'	=> '5',
			'httpversion'	=> '1.0',
			'sslverify'		=> 'false',
			'blocking'		=> 'true',
			'headers'		=> array(
				'Content-Type'	=> 'application/json'
			),
			'body'					=> json_encode( array( 'longUrl' => $url ) ),
			'cookies'				=> array()
		);

		// perform POST request
		$short_url = wp_remote_post( 'https://www.googleapis.com/urlshortener/v1/url', $args );

		// check for WP Error
		if( is_wp_error( $short_url ) ) {
			$short_url = $url;
			WC_Twilio_SMS::log_error( $short_url->get_error_message() );
		}

		// JSON decode response body into associative array
		if( isset( $short_url['response']['code'] ) && 200 == $short_url['response']['code'] && isset( $short_url['body']) )
			$short_url = json_decode( $short_url['body'], true );

		// if short url was decoded successfully, use it
		if( isset( $short_url['id'] ) && ! empty( $short_url['id'] ) )
			$url = $short_url['id'];

		return $url;
	}

	/**
	 * Replaces template variables in SMS message
	 *
	 * @access private
	 * @since  1.0
	 * @param string $message :
	 * @param \WC_Order $order :
	 * @return string : message with variables replaced with indicated values
	 */
	private static function replace_message_variables( $message, $order ) {

		$message = str_replace( '%shop_name%', get_bloginfo( 'name' ), $message );
		$message = str_replace( '%order_id%', $order->id, $message );
		$message = str_replace( '%order_amount%', $order->get_total(), $message );
		$message = str_replace( '%order_status%', ucfirst( $order->status ), $message );

		return $message;
	}

	/**
	 * Formats order note
	 *
	 * @access private
	 * @since  1.0
	 * @param string $to :
	 * @param int $sent_timestamp :
	 * @param string $message :
	 * @param string $status :
	 * @param bool $error :
	 * @return string : HTML-formatted order note
	 */
	private static function format_order_note( $to, $sent_timestamp, $message, $status, $error ) {

		$status_style = ( $error ) ? 'color: red;' : 'color: green;';

		// Get site timezone setting string
		$timezone = get_option( 'timezone_string' );

		// if timezone is set to UTC (or UTC +/- number), this will be blank, so set it to UTC
		if( ! $timezone )
			$timezone = 'UTC';

		// DateTime classes throw exceptions for instantiating
		try {

			// setup datetime with UTC timestamp of send
			$datetime = new DateTime( '@' . $sent_timestamp );

			// set timezone to site timezone or UTC
			$datetime->setTimezone( new DateTimeZone( $timezone ) );

			// format date / time into site format
			$datetime = $datetime->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		}

		catch( Exception $e ) {
			// log error and set datetime for SMS to 'N/A'
			WC_Twilio_SMS::log_error( $e->getMessage() );
			$datetime = 'N/A';
		}

		ob_start();
		?>
	  	<p><strong><?php _e( 'SMS Notification', WC_Twilio_SMS::$text_domain ); ?></strong></p>
		<p><strong><?php _e( 'To', WC_Twilio_SMS::$text_domain ); ?>: </strong><?php echo esc_html( $to ); ?></p>
		<p><strong><?php _e( 'Date Sent', WC_Twilio_SMS::$text_domain ); ?>: </strong><?php echo esc_html( $datetime ); ?></p>
		<p><strong><?php _e( 'Message', WC_Twilio_SMS::$text_domain ); ?>: </strong><?php echo esc_html( $message ); ?></p>
		<p><strong><?php _e( 'Status', WC_Twilio_SMS::$text_domain ); ?>: <span style="<?php echo esc_attr( $status_style ); ?> "><?php echo esc_html( $status ); ?></span></strong></p>
		<?php

		return ob_get_clean();
	}
}
