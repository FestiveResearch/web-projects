<?php
/**
 * WooCommerce Twilio SMS API Class
 *
 * Wrapper for the Twilio SMS API
 * @see specs: https://www.twilio.com/docs/api/rest/sending-sms
 *
 * @package		WooCommerce Twilio SMS Notifications
 * @subpackage  WC_Twilio_SMS_API
 * @category    Class
 * @author		Max Rice
 * @since		1.0
 */

class WC_Twilio_SMS_API
{
	/** @var string API host */
	private $host = 'https://api.twilio.com/2010-04-01/Accounts/';

	/** @var string API endpoint */
	private $endpoint;

	/** @var string phone number to send SMS messages from */
	private $from_number;

	/** @var string SMS uri */
	private $sms_uri = '/SMS/Messages.json';

	/** @var string IncomingPhoneNumbers uri */
	private $incoming_phone_numbers_uri = '/IncomingPhoneNumbers.json';

	/** @var string twilio account sid */
	private $account_sid;

	/** @var string twilio auth token */
	private $auth_token;

	/** @var mixed response string or array  */
	private $response;

	/** @var array args to use with wp_remote_*() */
	private $wp_remote_http_args = array(
		'method'		=> '',
		'timeout'		=> '10',
		'redirection'	=> '5',
		'httpversion'	=> '1.0',
		'sslverify'		=> 'false',
		'blocking'		=> 'true',
		'headers'		=> array(
			'Authorization'	=> ''
		),
		'body'					=> '',
		'cookies'				=> array()
	);

	/**
	 * Constructor
	 *
	 * @access public
	 * @since  1.0
	 * @param string : $account_sid : required
	 * @param string $auth_token : required
	 * @param string $from_number : required - number to send SMS messages from
	 * @param array $options optional : API options
	 * @return \WC_Twilio_SMS_API
	 */
	public function __construct( $account_sid, $auth_token, $from_number, $options = array() ) {

		$this->account_sid = $account_sid;
		
		$this->auth_token = $auth_token;

		// don't format to e.164, as the country code is not available
		$this->from_number = preg_replace( '[\D]', '', $from_number );

		// Set Basic HTTP Auth
		$this->wp_remote_http_args['headers']['Authorization'] = sprintf( 'Basic %s', base64_encode( $this->account_sid . ':' . $this->auth_token ) );
	}

	/**
	 * Send an SMS
	 *
	 * POST /SMS/Messages.json
	 *
	 * @access public
	 * @since  1.0
	 * @param string $to required : Destination Phone Number
	 * @param string $body required : Body of Message
	 * @param string $country_code required: country code in ISO_3166-1_alpha-2 format
	 * @throws Exception
	 * @return array $response
	 */
	public function send( $to, $body, $country_code = '' ) {

		if( ! $to || ! $body )
			throw new Exception( __( 'Send SMS: To number / Body is blank!', WC_Twilio_SMS::$text_domain ) );

		// format number to E.164 format
		$to = $this->format_e164( $to, $country_code );

		// truncate the message to 160 characters
		$body = substr( $body, 0, 160 );

		// set request body
		$this->wp_remote_http_args['body'] = http_build_query( array( 'From' => $this->from_number,'To' => $to, 'Body' => $body ) );

		// set SMS endpoint
		$this->set_endpoint( $this->sms_uri );

		// send the POST request
		$this->http_request( 'POST' );

		// parse the response JSON
		$this->parse_response();

		// return parsed response
		return $this->response;
	}

	/**
	 * Format a given number to e164 format
	 *
	 * e164 format = +<3 digit country code><12-14 digit country number>
	 *
	 * @see http://en.wikipedia.org/wiki/E.164
	 *
	 * @access private
	 * @since  1.0
	 * @param string $number : number to format
	 * @param string $country_code : in ISO_3166-1_alpha-2 format (WC uses this standard)
	 * @return string number in e164 format
	 */
	private function format_e164( $number, $country_code = '' ) {

		// if there's no country code, just return the number stripped of any non-digits
		if( ! $country_code )
			return $number;

		// remove any non-number characters
		$number = preg_replace( '[\D]', '', $number );

		// get the phone country code for given country
		$country_calling_code = $this->get_country_calling_code( $country_code );

		// add the country code if it doesn't exist
		if( $country_calling_code != substr( $number, 0, strlen( $country_calling_code ) ) )
			$number = $country_calling_code . $number;

		// remove any leading zeroes after the country code
		if( '0' == substr( $number, strlen( $country_calling_code ), 1 ) )
			$number = str_replace( $country_calling_code . '0', $country_calling_code, $number );

		// prepend +
		$number = '+' . $number;

		return $number;
	}


	/**
	 * Get the calling code of a given country
	 *
	 * @see http://en.wikipedia.org/wiki/List_of_country_calling_codes
	 *
	 * @access private
	 * @since  1.0
	 * @param string $country : ISO_3166-1_alpha-2 country code
	 * @return string : country calling code
	 */
	private function get_country_calling_code( $country ) {

		$country = strtoupper( $country );

		$country_codes = array(
			'AC' => '247',
			'AD' => '376',
			'AE' => '971',
			'AF' => '93',
			'AG' => '1268',
			'AI' => '1264',
			'AL' => '355',
			'AM' => '374',
			'AO' => '244',
			'AQ' => '672',
			'AR' => '54',
			'AS' => '1684',
			'AT' => '43',
			'AU' => '61',
			'AW' => '297',
			'AX' => '358',
			'AZ' => '994',
			'BA' => '387',
			'BB' => '1246',
			'BD' => '880',
			'BE' => '32',
			'BF' => '226',
			'BG' => '359',
			'BH' => '973',
			'BI' => '257',
			'BJ' => '229',
			'BL' => '590',
			'BM' => '1441',
			'BN' => '673',
			'BO' => '591',
			'BQ' => '599',
			'BR' => '55',
			'BS' => '1242',
			'BT' => '975',
			'BW' => '267',
			'BY' => '375',
			'BZ' => '501',
			'CA' => '1',
			'CC' => '61',
			'CD' => '243',
			'CF' => '236',
			'CG' => '242',
			'CH' => '41',
			'CI' => '225',
			'CK' => '682',
			'CL' => '56',
			'CM' => '237',
			'CN' => '86',
			'CO' => '57',
			'CR' => '506',
			'CU' => '53',
			'CV' => '238',
			'CW' => '599',
			'CX' => '61',
			'CY' => '357',
			'CZ' => '420',
			'DE' => '49',
			'DJ' => '253',
			'DK' => '45',
			'DM' => '1767',
			'DO' => '1809',
			'DO' => '1829',
			'DO' => '1849',
			'DZ' => '213',
			'EC' => '593',
			'EE' => '372',
			'EG' => '20',
			'EH' => '212',
			'ER' => '291',
			'ES' => '34',
			'ET' => '251',
			'EU' => '388',
			'FI' => '358',
			'FJ' => '679',
			'FK' => '500',
			'FM' => '691',
			'FO' => '298',
			'FR' => '33',
			'GA' => '241',
			'GB' => '44',
			'GD' => '1473',
			'GE' => '995',
			'GF' => '594',
			'GG' => '44',
			'GH' => '233',
			'GI' => '350',
			'GL' => '299',
			'GM' => '220',
			'GN' => '224',
			'GP' => '590',
			'GQ' => '240',
			'GR' => '30',
			'GT' => '502',
			'GU' => '1671',
			'GW' => '245',
			'GY' => '592',
			'HK' => '852',
			'HN' => '504',
			'HR' => '385',
			'HT' => '509',
			'HU' => '36',
			'ID' => '62',
			'IE' => '353',
			'IL' => '972',
			'IM' => '44',
			'IN' => '91',
			'IO' => '246',
			'IQ' => '964',
			'IR' => '98',
			'IS' => '354',
			'IT' => '39',
			'JE' => '44',
			'JM' => '1876',
			'JO' => '962',
			'JP' => '81',
			'KE' => '254',
			'KG' => '996',
			'KH' => '855',
			'KI' => '686',
			'KM' => '269',
			'KN' => '1869',
			'KP' => '850',
			'KR' => '82',
			'KW' => '965',
			'KY' => '1345',
			'KZ' => '7',
			'LA' => '856',
			'LB' => '961',
			'LC' => '1758',
			'LI' => '423',
			'LK' => '94',
			'LR' => '231',
			'LS' => '266',
			'LT' => '370',
			'LU' => '352',
			'LV' => '371',
			'LY' => '218',
			'MA' => '212',
			'MC' => '377',
			'MD' => '373',
			'ME' => '382',
			'MF' => '590',
			'MG' => '261',
			'MH' => '692',
			'MK' => '389',
			'ML' => '223',
			'MM' => '95',
			'MN' => '976',
			'MO' => '853',
			'MP' => '1670',
			'MQ' => '596',
			'MR' => '222',
			'MS' => '1664',
			'MT' => '356',
			'MU' => '230',
			'MV' => '960',
			'MW' => '265',
			'MX' => '52',
			'MY' => '60',
			'MZ' => '258',
			'NA' => '264',
			'NC' => '687',
			'NE' => '227',
			'NF' => '672',
			'NG' => '234',
			'NI' => '505',
			'NL' => '31',
			'NO' => '47',
			'NP' => '977',
			'NR' => '674',
			'NU' => '683',
			'NZ' => '64',
			'OM' => '968',
			'PA' => '507',
			'PE' => '51',
			'PF' => '689',
			'PG' => '675',
			'PH' => '63',
			'PK' => '92',
			'PL' => '48',
			'PM' => '508',
			'PR' => '1787',
			'PR' => '1939',
			'PS' => '970',
			'PT' => '351',
			'PW' => '680',
			'PY' => '595',
			'QA' => '974',
			'QN' => '374',
			'QS' => '252',
			'QY' => '90',
			'RE' => '262',
			'RO' => '40',
			'RS' => '381',
			'RU' => '7',
			'RW' => '250',
			'SA' => '966',
			'SB' => '677',
			'SC' => '248',
			'SD' => '249',
			'SE' => '46',
			'SG' => '65',
			'SH' => '290',
			'SI' => '386',
			'SJ' => '47',
			'SK' => '421',
			'SL' => '232',
			'SM' => '378',
			'SN' => '221',
			'SO' => '252',
			'SR' => '597',
			'SS' => '211',
			'ST' => '239',
			'SV' => '503',
			'SX' => '1721',
			'SY' => '963',
			'SZ' => '268',
			'TA' => '290',
			'TC' => '1649',
			'TD' => '235',
			'TG' => '228',
			'TH' => '66',
			'TJ' => '992',
			'TK' => '690',
			'TL' => '670',
			'TM' => '993',
			'TN' => '216',
			'TO' => '676',
			'TR' => '90',
			'TT' => '1868',
			'TV' => '688',
			'TW' => '886',
			'TZ' => '255',
			'UA' => '380',
			'UG' => '256',
			'UK' => '44',
			'US' => '1',
			'UY' => '598',
			'UZ' => '998',
			'VA' => '379',
			'VA' => '39',
			'VC' => '1784',
			'VE' => '58',
			'VG' => '1284',
			'VI' => '1340',
			'VN' => '84',
			'VU' => '678',
			'WF' => '681',
			'WS' => '685',
			'XC' => '991',
			'XD' => '888',
			'XG' => '881',
			'XL' => '883',
			'XN' => '857',
			'XN' => '858',
			'XN' => '870',
			'XP' => '878',
			'XR' => '979',
			'XS' => '808',
			'XT' => '800',
			'XV' => '882',
			'YE' => '967',
			'YT' => '262',
			'ZA' => '27',
			'ZM' => '260',
			'ZW' => '263',
		);

		// return valid country code if country exists or blank country code if not found
		return ( isset( $country_codes[$country] ) ) ? $country_codes[$country] : '';

	}

	/**
	 * Performs HTTP Request
	 *
	 * @access public
	 * @since  1.0
	 * @uses wp_remote_request()
	 * @param string $method : HTTP method to use for request
	 * @throws Exception : Blank/invalid endpoint or HTTP method, WP HTTP API error
	 * @return array
	 */
	private function http_request( $method ) {
		
		// Check for blank endpoint or method
		if( ! $this->endpoint || ! $method )
			throw new Exception( __( 'Endpoint and / or HTTP Method is blank.', WC_Twilio_SMS::$text_domain ) );

		// Check that method is a valid http method
		if( ! in_array( $method, array( 'GET', 'POST', 'PUT', 'DELETE' ) ) )
			throw new Exception( __( 'Requested HTTP Method is invalid.', WC_Twilio_SMS::$text_domain ) );

		// set the method
		$this->wp_remote_http_args['method'] = $method;

		// perform HTTP request with endpoint / args
		$this->response = wp_remote_request( esc_url_raw( $this->endpoint ), $this->wp_remote_http_args );

		// WP HTTP API error like Network timeout, etc.
		if( is_wp_error( $this->response ) )
			throw new Exception( $this->response->get_error_message() );

		// Check for proper response / body
		if( ! isset( $this->response['response'] ) )
			throw new Exception( __( 'Empty Response', WC_Twilio_SMS::$text_domain ) );

		if( ! isset( $this->response['body'] ) )
			throw new Exception( __( 'Empty Body', WC_Twilio_SMS::$text_domain ) );

	}

	/**
	 * Checks response HTTP status code according to Twilio API Documentation
	 * JSON decodes body as an associative array for return by calling function
	 *
	 * @see https://www.twilio.com/docs/api/rest/tips
	 *
	 * @access public
	 * @since  1.0
	 * @throws Exception : If HTTP status code other than 200/201 or if HTTP status code not set
	 * @return void
	 */
	private function parse_response() {

		if( isset( $this->response['response']['code'] ) ) {

			// 200 is returned for successful GET requests and 201 is returned for successful POST requests
			if( $this->response['response']['code'] != 200 && $this->response['response']['code'] != 201 ) :

				$this->response = json_decode( $this->response['body'], true );

				throw new Exception( sprintf( __( '%s See: %s', WC_Twilio_SMS::$text_domain ),
											( isset( $this->response['message'] ) ) ? $this->response['message'] : __( 'No Message Available', WC_Twilio_SMS::$text_domain ),
											( isset( $this->response['more_info'] ) ) ? $this->response['more_info'] : __( 'N/A', WC_Twilio_SMS::$text_domain )
									) );
			endif;

			// JSON decode the body into an associative array
			$this->response = json_decode( $this->response['body'], true );

		} else {

			throw new Exception( __( 'Response HTTP Code Not Set', WC_Twilio_SMS::$text_domain ) );
		}
	}

	/**
	 * Set Endpoint URI
	 *
	 * @access private
	 * @since  1.0
	 * @param $uri : specific API URI
	 * @return void
	 */
	private function set_endpoint( $uri ) {

		$this->endpoint = $this->host . $this->account_sid . $uri;
	}



	/**
	 * Helper to parse options array
	 *
	 * @access private
	 * @since  1.0
	 * @param array  $array                  required : array to parse
	 * @param string $key                    required : key to return value for
	 * @param mixed  $default                optional : return a default value if key not set
	 * @param bool   $treat_empty_as_not_set optional : flag
	 * @return mixed|null
	 */
	private function array_get( $array, $key, $default = null, $treat_empty_as_not_set = false ) {

		if ( ! is_array( $array ) )
			return( $default );

		if ( array_key_exists( $key, $array ) && ( ! $treat_empty_as_not_set || ! empty( $array[$key] ) ) ) {

			return( $array[$key] );

		} else {

			return( $default );

		}
	}

} //end class