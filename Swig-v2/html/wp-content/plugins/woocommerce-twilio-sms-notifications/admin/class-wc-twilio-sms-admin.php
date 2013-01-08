<?php
/**
 * Twilio SMS Notifications Admin Class
 *
 * Loads admin settings page and adds related hooks / filters
 *
 *
 * @package		WooCommerce Twilio SMS Notifications
 * @subpackage	WC_Twilio_SMS_Admin
 * @category	Class
 * @author		Max Rice
 * @since		1.0
 */

class WC_Twilio_SMS_Admin {

	/** @var string id of tab on WooCommerce Settings page */
	public static $tab_id = 'twilio_sms';

	/** @var string meta box id */
	public static $metabox_id = 'twilio_sms';


	/**
	 * Setup admin class
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public static function init() {

		/** General Admin Hooks */

		// maybe install plugin
		add_action( 'admin_init', __CLASS__ . '::maybe_install_default_settings' );

		// Add SMS tab
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab' );

		// Show SMS settings page
		add_action( 'woocommerce_settings_tabs_twilio_sms', __CLASS__ . '::settings_page' );

		// Save SMS settings page
		add_action( 'woocommerce_update_options_' . self::$tab_id, __CLASS__ . '::update_settings' );

		// Add custom 'link' form field type
		add_action( 'woocommerce_admin_field_wc_twilio_sms_link', __CLASS__ . '::add_link_field' );

		/** Order Admin Hooks */

		// Add 'Send an SMS' meta-box on Order page to send SMS to customer
		add_action( 'add_meta_boxes', __CLASS__ . '::add_order_meta_box' );

		add_action( 'woocommerce_process_shop_order_meta', __CLASS__ . '::process_order_meta_box' );

	}


	/**
	 * Maybe install default settings on first run
	 *
	 * @uses  WC_Twilio_SMS_Admin::get_settings()
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public static function maybe_install_default_settings() {

		// Check if we're installed already
		if ( ! get_option( WC_Twilio_SMS::$settings_prefix . 'is_installed' ) ) {

			// not installed, install default options
			foreach ( self::get_settings() as $setting ) {

				if ( isset( $setting['std'] ) )
					add_option( $setting['id'], $setting['std'] );

			}

			// set is_installed option
			add_option( WC_Twilio_SMS::$settings_prefix . 'is_installed', 1 );
		}
	}

	/**
	 * Add SMS tab to WooCommerce Settings after 'Email' tab
	 *
	 * @access public
	 * @since 1.0
	 * @param $settings_tabs : tabs array sans 'SMS' tab
	 * @return array $settings_tabs : now with 100% more 'SMS' tab!
	 */
	public static function add_settings_tab( $settings_tabs ) {

		$new_settings_tabs = array();

		foreach ( $settings_tabs as $tab_id => $tab_title ) {

			$new_settings_tabs[$tab_id] = $tab_title;

			// Add our tab after 'Email' tab
			if ( 'email' == $tab_id ) {
				$new_settings_tabs[self::$tab_id] = __( 'SMS', WC_Twilio_SMS::$text_domain );
			}
		}

		return $new_settings_tabs;
	}

	/**
	 * Show SMS settings page. Uses WC Admin Settings API to output form fields -
	 * @see woocommerce_admin_fields()
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 * @uses self::test_sms_form()
	 * @access public
	 * @since 1.0
	 */
	public static function settings_page() {

		// output settings
		woocommerce_admin_fields( self::get_settings() );

		// Hide admin notification settings if unchecked
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#<?php echo WC_Twilio_SMS::$settings_prefix . 'enable_admin_sms'; ?>').change(function() {

					if( $(this).is(':checked') ) {
						$(this).closest('tr').nextUntil('p').show();
					} else {
						$(this).closest('tr').nextUntil('p').hide();
					}
				}).change();
			});
		</script>
		<?php

		// output test sms form
		self::test_sms_form();
	}

	/**
	 * Builds & Outputs form for Test SMS send
	 *
	 * @uses woocommerce_admin_fields()
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public static function test_sms_form() {
		global $woocommerce;

		$fields = array(

			array(
				'name' => __( 'Send Test SMS', WC_Twilio_SMS::$text_domain ),
				'type' => 'title'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'test_mobile_number',
				'name'			=> __( 'Mobile Number', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Enter the mobile number (starting with the country code) where the test SMS should be send. Note that if you are using a trial Twilio account, this number must be verified first.', WC_Twilio_SMS::$text_domain ),
				'type'			=> 'text'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'test_message',
				'name'			=> __( 'Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Enter the test message to be sent. Remember that SMS messages are limited to 160 characters.', WC_Twilio_SMS::$text_domain ),
				'type'			=> 'textarea',
				'css'			=> 'min-width: 500px;'
			),

			array(
				'anchor_text'	=> __( 'Send', WC_Twilio_SMS::$text_domain ),
				'anchor'		=> '#',
				'class'			=> WC_Twilio_SMS::$settings_prefix . 'test_sms_button' . ' button',
				'type'			=> 'wc_twilio_sms_link'
			),

			array( 'type' => 'sectionend', 'id' => WC_Twilio_SMS::$settings_prefix . 'send_test_section' )

		);

		// output fields
		?><div id="<?php echo WC_Twilio_SMS::$settings_prefix . 'test_sms'; ?>"><?php
		woocommerce_admin_fields( $fields );
		?></div>

		<script type="text/javascript">

			jQuery('a.<?php echo WC_Twilio_SMS::$settings_prefix . 'test_sms_button'; ?>').click(function () {

                var number_id		= '<?php echo WC_Twilio_SMS::$settings_prefix . 'test_mobile_number'; ?>';
                var message_id		= '<?php echo WC_Twilio_SMS::$settings_prefix . 'test_message'; ?>';
                var div_id			= '<?php echo WC_Twilio_SMS::$settings_prefix . 'test_sms'; ?>';

				// make sure values are not empty
				if (( !jQuery('input#'+number_id).val() ) || ( !jQuery('textarea#'+message_id).val() )) {
					alert("<?php _e( 'Please make sure you have entered a mobile phone number and test message.', WC_Twilio_SMS::$text_domain ); ?>");
					return;
				}

				// block UI
				jQuery('#'+div_id).block({ message:null, overlayCSS:{ background:'#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity:0.6 } });

				// build data
				var data = {
					mobile_number	: jQuery('input#'+number_id).val(),
					message			: jQuery('textarea#'+message_id).val(),
					security		: '<?php echo wp_create_nonce( WC_Twilio_SMS::$settings_prefix . 'send_test_sms' ); ?>'
				};
				jQuery.post('<?php echo admin_url( 'admin-ajax.php?action=woocommerce_twilio_sms_send_test_sms' ); ?>', data, function (response) {

					// unblock UI
					jQuery('#'+div_id).unblock();

					// clear posted values
					jQuery('input#'+number_id).val('');
					jQuery('textarea#'+message_id).val('');

					// Display Success or Failure message from response
					alert(response);

				});

				return false;

			});

		</script>

	<?php

	}


	/**
	 * Add 'Send an SMS' meta-box to Orders page
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public static function add_order_meta_box() {
		add_meta_box(
			WC_Twilio_SMS::$settings_prefix . 'order_meta_box',
			__( 'Send SMS Message', WC_Twilio_SMS::$text_domain ),
		  __CLASS__ . '::display_order_meta_box',
			'shop_order',
			'side',
			'default'
		);
	}

	/**
	 * Display the 'Send an SMS' meta-box on the Orders page
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public static function display_order_meta_box() {
		?>
    <p>
        <textarea type="text" name="<?php echo WC_Twilio_SMS::$settings_prefix . 'order_message'; ?>" id="<?php echo WC_Twilio_SMS::$settings_prefix . 'order_message'; ?>" class="input-text" style="width: 100%;" rows="4"></textarea>
    </p>
    <p>
        <input type="submit" class="button tips" name="<?php echo WC_Twilio_SMS::$settings_prefix . 'order_send_message'; ?>" value="<?php _e( 'Send SMS', WC_Twilio_SMS::$text_domain ); ?>" data-tip="<?php _e( 'Send an SMS to the billing phone number for this order.', WC_Twilio_SMS::$text_domain ); ?>" />
        <span id="<?php echo WC_Twilio_SMS::$settings_prefix . 'order_message_char_count'; ?>" style="color: green; float: right; font-size: 16px;">0</span>
    </p>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var sms_text_area = '#<?php echo WC_Twilio_SMS::$settings_prefix . 'order_message'; ?>';
            var sms_char_count = '#<?php echo WC_Twilio_SMS::$settings_prefix . 'order_message_char_count'; ?>';
			$(sms_text_area).keyup(function() {
                $(sms_char_count).text($(this).val().length);
                if($(this).val().length > 160) {
                    $(sms_char_count).css('color','red');
                }
            })
        });
    </script>
	<?php
	}

	/**
	 * Process actions from the order meta box
	 *
	 * @access public
	 * @since 1.0
	 * @param int $post_id
	 * @return void
	 */
	public static function process_order_meta_box( $post_id ) {

		if( isset( $_POST[WC_Twilio_SMS::$settings_prefix . 'order_message'] ) && ! empty( $_POST[WC_Twilio_SMS::$settings_prefix . 'order_message'] ) ) :

			// sanitize message
			$message = sanitize_text_field( $_POST[WC_Twilio_SMS::$settings_prefix . 'order_message'] );

			// send the SMS
			WC_Twilio_SMS_Order::manual_customer_notification( $post_id, $message );

		endif;
	}

	/**
	 * Update options on SMS settings page. Uses WC Admin Settings API to validate / save form fields -
	 * @see   woocommerce_update_options()
	 *
	 * @uses  woocommerce_update_options()
	 * @uses  self::get_settings()
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public static function update_settings() {

		woocommerce_update_options( self::get_settings() );
	}

	/**
	 * Build array of plugin settings in format needed to use
	 * @see woocommerce_admin_fields()
	 *
	 * @uses woocommerce_admin_fields()
	 * @access public
	 * @since 1.0
	 * @returns array : settings
	 */
	private static function get_settings() {
		return array(

			array(
				'name' => __( 'General Settings', WC_Twilio_SMS::$text_domain ),
				'type' => 'title'
			),

			array(
				'id'		=> WC_Twilio_SMS::$settings_prefix . 'checkout_optin_checkbox_label',
				'name'		=> __( 'Opt-in Checkbox Label', WC_Twilio_SMS::$text_domain ),
				'desc_tip'	=> __( 'Label for the Opt-in checkbox on the Checkout page. Leave blank to disable the opt-in and force ALL customers to receive SMS updates.', WC_Twilio_SMS::$text_domain ),
				'css'		=> 'min-width: 275px;',
				'std'		=> __( 'Please send me order updates via text message', WC_Twilio_SMS::$text_domain ),
				'type'		=> 'text'
			),

			array(
				'id'		=> WC_Twilio_SMS::$settings_prefix . 'checkout_optin_checkbox_default',
				'name'		=> __( 'Opt-in Checkbox Default', WC_Twilio_SMS::$text_domain ),
				'desc_tip'	=> __( 'Default status for the Opt-in checkbox on the Checkout page.', WC_Twilio_SMS::$text_domain ),
				'std'		=> 'unchecked',
				'type'		=> 'select',
				'options'	=> array(
					'unchecked'	=> __( 'Unchecked', WC_Twilio_SMS::$text_domain ),
					'checked'	=> __( 'Checked', WC_Twilio_SMS::$text_domain )
				)
			),

			array(
				'id'		=> WC_Twilio_SMS::$settings_prefix . 'shorten_urls',
				'name'		=> __( 'Shorten URLs', WC_Twilio_SMS::$text_domain ),
				'desc_tip'	=> __( 'Enable to automatically shorten links in SMS messages with Google URL Shortener (goo.gl)', WC_Twilio_SMS::$text_domain ),
				'std'		=> 'yes',
				'type'		=> 'checkbox'
			),

			array( 'type' => 'sectionend' ),

			array(
				'name' => __( 'Admin Notifications', WC_Twilio_SMS::$text_domain ),
				'type' => 'title'
			),

			array(
				'id'	=> WC_Twilio_SMS::$settings_prefix . 'enable_admin_sms',
				'name'	=> __( 'Enable new order SMS admin notifications.', WC_Twilio_SMS::$text_domain ),
				'std'	=> 'no',
				'type'	=> 'checkbox'
			),

			array(
				'id'		=> WC_Twilio_SMS::$settings_prefix . 'admin_sms_recipients',
				'name'		=> __( 'Admin Mobile Number', WC_Twilio_SMS::$text_domain ),
				'desc_tip'	=> __( 'Enter the mobile number (starting with the country code) where the New Order SMS should be sent. Send to multiple recipients by separating numbers with commas.', WC_Twilio_SMS::$text_domain ),
				'std'		=> '15558675309',
				'type'		=> 'text'
			),

			array(
				'id'		=> WC_Twilio_SMS::$settings_prefix . 'admin_sms_template',
				'name'		=> __( 'Admin SMS Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'	=> __( 'Use these tags to customize your message: %shop_name%, %order_id%, %order_amount%. Remember that SMS messages are limited to 160 characters.', WC_Twilio_SMS::$text_domain ),
				'css'		=> 'min-width:500px;',
				'std'		=> __( '%shop_name% : You have a new order (#%order_id%) for %order_amount%!', WC_Twilio_SMS::$text_domain ),
				'type'		=> 'textarea'
			),

			array( 'type' => 'sectionend' ),

			array(
				'name' => __( 'Customer Notifications', WC_Twilio_SMS::$text_domain ),
				'type' => 'title'
			),

			array(
				'name'			=> __( 'Send SMS Notifications for these statuses:', WC_Twilio_SMS::$text_domain ),
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'send_sms_pending',
				'desc'			=> __( 'Pending', WC_Twilio_SMS::$text_domain ),
				'std'			=> 'yes',
				'type'			=> 'checkbox',
				'checkboxgroup'	=> 'start'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'send_sms_on-hold',
				'desc'			=> __( 'On-Hold', WC_Twilio_SMS::$text_domain ),
				'std'			=> 'yes',
				'type'			=> 'checkbox',
				'checkboxgroup'	=> ''
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'send_sms_processing',
				'desc'			=> __( 'Processing', WC_Twilio_SMS::$text_domain ),
				'std'			=> 'yes',
				'type'			=> 'checkbox',
				'checkboxgroup'	=> ''
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'send_sms_completed',
				'desc'			=> __( 'Completed', WC_Twilio_SMS::$text_domain ),
				'std'			=> 'yes',
				'type'			=> 'checkbox',
				'checkboxgroup'	=> ''
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'send_sms_cancelled',
				'desc'			=> __( 'Cancelled', WC_Twilio_SMS::$text_domain ),
				'std'			=> 'yes',
				'type'			=> 'checkbox',
				'checkboxgroup'	=> ''
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'send_sms_refunded',
				'desc'			=> __( 'Refunded', WC_Twilio_SMS::$text_domain ),
				'std'			=> 'yes',
				'type'			=> 'checkbox',
				'checkboxgroup'	=> ''
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'send_sms_failed',
				'desc'			=> __( 'Failed', WC_Twilio_SMS::$text_domain ),
				'std'			=> 'yes',
				'type'			=> 'checkbox',
				'checkboxgroup'	=> 'end'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'default_sms_template',
				'name'			=> __( 'Default Customer SMS Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Use these tags to customize your message: %shop_name%, %order_id%, %order_amount%, %order_status%. Remember that SMS messages are limited to 160 characters.', WC_Twilio_SMS::$text_domain ),
				'css'			=> 'min-width:500px;',
				'std'			=> __( '%shop_name% : Your order (#%order_id%) is now %order_status%.', WC_Twilio_SMS::$text_domain ),
				'type'			=> 'textarea'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'pending_sms_template',
				'name'			=> __( 'Pending SMS Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Add a custom SMS message for pending orders or leave blank to use the default message above.', WC_Twilio_SMS::$text_domain ),
				'css'			=> 'min-width:500px;',
				'type'			=> 'textarea'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'on-hold_sms_template',
				'name'			=> __( 'On-Hold SMS Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Add a custom SMS message for on-hold orders or leave blank to use the default message above.', WC_Twilio_SMS::$text_domain ),
				'css'			=> 'min-width:500px;',
				'type'			=> 'textarea'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'processing_sms_template',
				'name'			=> __( 'Processing SMS Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Add a custom SMS message for processing orders or leave blank to use the default message above.', WC_Twilio_SMS::$text_domain ),
				'css'			=> 'min-width:500px;',
				'type'			=> 'textarea'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'completed_sms_template',
				'name'			=> __( 'Completed SMS Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Add a custom SMS message for completed orders or leave blank to use the default message above.', WC_Twilio_SMS::$text_domain ),
				'css'			=> 'min-width:500px;',
				'type'			=> 'textarea'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'cancelled_sms_template',
				'name'			=> __( 'Cancelled SMS Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Add a custom SMS message for cancelled orders or leave blank to use the default message above.', WC_Twilio_SMS::$text_domain ),
				'css'			=> 'min-width:500px;',
				'type'			=> 'textarea'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'refunded_sms_template',
				'name'			=> __( 'Refunded SMS Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Add a custom SMS message for refunded orders or leave blank to use the default message above.', WC_Twilio_SMS::$text_domain ),
				'css'			=> 'min-width:500px;',
				'type'			=> 'textarea'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'failed_sms_template',
				'name'			=> __( 'Failed SMS Message', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Add a custom SMS message for failed orders or leave blank to use the default message above.', WC_Twilio_SMS::$text_domain ),
				'css'			=> 'min-width:500px;',
				'type'			=> 'textarea'
			),

			array( 'type' => 'sectionend' ),

			array(
				'name' => __( 'Twilio Settings', WC_Twilio_SMS::$text_domain ),
				'type' => 'title'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'account_sid',
				'name'			=> __( 'Account SID', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Log into your Twilio Account to find your Account SID.', WC_Twilio_SMS::$text_domain ),
				'type'			=> 'text'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'auth_token',
				'name'			=> __( 'Auth Token', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Log into your Twilio Account to find your Auth Token.', WC_Twilio_SMS::$text_domain ),
				'type'			=> 'text'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'from_number',
				'name'			=> __( 'From Number', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Enter the number to send SMS messages from. This must be a purchased number from Twilio.', WC_Twilio_SMS::$text_domain ),
				'type'			=> 'text'
			),

			array(
				'id'			=> WC_Twilio_SMS::$settings_prefix . 'log_errors',
				'name'			=> __( 'Log Errors', WC_Twilio_SMS::$text_domain ),
				'desc_tip'		=> __( 'Enable this to log Twilio API errors to the PHP error log. Use this if you are having issues sending SMS.', WC_Twilio_SMS::$text_domain ),
				'std'			=> 'no',
				'type'			=> 'checkbox'
			),

			array( 'type' => 'sectionend' )

		);
	}

	/**
	 * Adds custom woocommerce form field via woocommerce_admin_field_* hook
	 *
	 * @access public
	 * @since 1.0
	 * @param array $field :
	 * @return void
	 */
	public static function add_link_field( $field ) {

		if ( isset( $field['anchor_text'] ) AND isset( $field['class'] ) AND isset( $field['anchor'] ) ) :
			?>

        <tr valign="top">
            <th scope="row" class="titledesc"></th>
            <td class="forminp">
                <a href="<?php echo esc_attr( $field['anchor'] ); ?>" class="<?php echo $field['class']; ?>"><?php echo esc_attr( $field['anchor_text'] ); ?></a>
            </td>
        </tr>

		<?php
		endif;

	}


} // end class

WC_Twilio_SMS_Admin::init();

// end file