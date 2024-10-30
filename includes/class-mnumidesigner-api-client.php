<?php
/**
 * Used for HTTP communication with MnumiDesigner API.
 *
 * @package MnumiDesigner/API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'MnumiDesigner_API_Client' ) ) :
	/**
	 * MnumiDesigner_API_Client class.
	 *
	 * Wrapper over wp_remote_* functions for easier usage.
	 */
	final class MnumiDesigner_API_Client {
		/**
		 * Requests MnumiDesigner Api endpoint.
		 *
		 * @param string $endpoint Api endpoint.
		 * @param array  $args Request arguments.
		 * @return WP_Error|array
		 */
		public static function request( $endpoint, $args = array() ) {
			$settings = MnumiDesigner_Settings::instance();

			if ( $settings->get_api_key() && $settings->get_api_key_id() ) {
				$api_key    = $settings->get_api_key();
				$api_key_id = $settings->get_api_key_id();

				$args['headers']['Accept']        = 'application/json';
				$args['headers']['Authorization'] = sprintf(
					'Basic %s',
					base64_encode( $api_key_id . ':' . $api_key )
				);
			}

			if ( $settings->get_api_host() ) {
				$api_host = $settings->get_api_host();
				$base_url = $api_host . 'api';
				$url      = sprintf( '%s/%s.json', $base_url, ltrim( $endpoint ) );

				$query = '';
				if ( isset( $args['query'] ) && count( $args['query'] ) > 0 ) {
					$url .= '?' . http_build_query( $args['query'] );
					unset( $args['query'] );
				}

				$args['timeout'] = 60;
				return wp_remote_request( $url, $args );
			}

			return new WP_Error(
				'rest_mnumidesigner_unconfigured',
				__( 'MnumiDesigner connection is not configured', 'mnumidesigner' )
			);
		}

		/**
		 * Handles response from MnumiDesigner API.
		 *
		 * @param array $response HTTP Response.
		 * @return array
		 */
		public static function handle_response( $response ) {
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}

		/**
		 * Encodes parameters for publicly accessible url.
		 *
		 * @param array $params Parameters to encode.
		 * @return string
		 */
		public static function encode_parameter( $params ) {
			$parameter = base64_encode( wp_json_encode( $params ) );

			return $parameter;
		}

		/**
		 * Gets signature for encoded parameters for publicly accessible url.
		 *
		 * @param array $params Parameters to encode.
		 * @return string
		 */
		public static function get_signature( $params ) {
			$settings = MnumiDesigner_Settings::instance();
			$api_key  = $settings->get_api_key();

			$parameter = self::encode_parameter( $params );
			$signature = base64_encode( hash_hmac( 'sha1', $parameter, $api_key, true ) );

			return $signature;
		}

		/**
		 * Gets publicly accessible url for given parameters.
		 *
		 * @param array $params Parameters.
		 * @return string
		 */
		public static function get_url( $params ) {
			$action = $params['action'];

			$parameter = self::encode_parameter( $params );
			$signature = self::get_signature( $params );

			$settings = MnumiDesigner_Settings::instance();

			$id = '';
			if ( $settings->get_api_key_id() ) {
				$id = '&id=' . $settings->get_api_key_id();
			}

			return sprintf(
				'%s%s?parameter=%s&signature=%s%s',
				$settings->get_api_host(),
				$action,
				$parameter,
				$signature,
				$id
			);
		}

		/**
		 * Gets publicly accessible url for creating new template.
		 *
		 * @param string $type Type of template to create.
		 * @param int    $width Width in milimeters for new template.
		 * @param int    $height Height in milimeters for new template.
		 * @param string $back_url Url to redirecto user to, after saving new template.
		 * @param string $display Display type in MnumiDesigner editor.
		 * @return string
		 */
		public static function get_new_link( $type, $width, $height, $back_url, $display = 'enduser' ) {
			$action = 'initE';

			$params = array(
				'width'   => $width,
				'height'  => $height,
				'type'    => $type,
				'action'  => $action,
				'backUrl' => $back_url,
				'display' => $display,
			);

			return self::get_url( $params );
		}
	}

endif;
