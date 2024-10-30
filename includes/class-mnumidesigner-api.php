<?php
/**
 * Used for communication with MnumiDesigner API.
 *
 * @package MnumiDesigner/API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'MnumiDesigner_API' ) ) :
	/**
	 * MnumiDesigner_API class.
	 */
	class MnumiDesigner_API {
		/**
		 * Singleton instance of MnumiDesigner_API.
		 *
		 * @var MnumiDesigner_API
		 */
		private static $instance;

		/**
		 * Last HTTP error.
		 *
		 * @var WP_Error|bool
		 */
		private static $last_error = false;

		/**
		 * Last HTTP response.
		 *
		 * @var array|bool
		 */
		private static $last_response = false;

		/**
		 * Indicates if Api access is in demo version.
		 *
		 * @var bool
		 */
		private static $is_demo = null;

		/**
		 * Indicates if Api access is a free version.
		 *
		 * @var bool
		 */
		private static $is_free = null;

		/**
		 * Date demo access is active.
		 *
		 * @var DateTimeImmutable
		 */
		private static $demo_valid_to = null;

		/**
		 * Get or create MnumiDesigner_API instance.
		 *
		 * @return MnumiDesigner_API
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Class constructor.
		 */
		private function __construct() {
			$this->reset();
		}

		/**
		 * Reset last error and response.
		 */
		private function reset() {
			self::$last_error    = false;
			self::$last_response = false;
		}

		/**
		 * Actions done before performing request.
		 */
		private function pre_request() {
			$this->reset();
		}

		/**
		 * Actions done after performing request.
		 *
		 * @param WP_Error|array $response MnumiDesigner Api response.
		 */
		private function post_request( $response ) {
			if ( is_wp_error( $response ) ) {
				self::$last_error = $response;
			} elseif ( 401 === $response['http_response']->get_status() ) {
				self::$last_error = new WP_Error(
					'rest_mnumidesigner_unauthorized',
					__( 'Unable to authorize with MnumiDesigner server. Check your API Access credentials.', 'mnumidesigner' ),
					array( 'status' => 401 )
				);
			} else {
				self::$last_response = $response;

				if ( isset( $response['headers'] ) ) {
					/**
					* Response headers.
					*
					* @var Requests_Utility_CaseInsensitiveDictionary $headers
					*/
					$headers       = $response['headers'];
					self::$is_demo = isset( $headers['x-mnumi-demo'] );
					if ( self::$is_demo ) {
						self::$demo_valid_to = new DateTimeImmutable( $headers['x-mnumi-demo'] );
					}
					self::$is_free = isset( $headers['x-mnumi-free'] );
				}
			}
		}

		/**
		 * Check if is Demo Api Access.
		 *
		 * @return bool
		 */
		public function is_demo() {
			if ( null === self::$is_demo ) {
				self::instance()->get_settings();
			}

			return self::$is_demo;
		}

		/**
		 * Check if is Free version Api Access.
		 *
		 * @return bool
		 */
		public function is_free() {
			if ( null === self::$is_free ) {
				self::instance()->get_settings();
			}

			return self::$is_free;
		}

		/**
		 * Check if Demo Api Access is still active.
		 *
		 * @return bool|null
		 */
		public function is_demo_active() {
			if ( $this->is_demo() ) {
				$now = new DateTime( 'now' );
				return $this->demo_active_to() > $now;
			}
		}

		/**
		 * Get Demo active access date.
		 *
		 * @return DateTimeImmutable|null
		 */
		public function demo_active_to() {
			if ( $this->is_demo() ) {
				return self::$demo_valid_to;
			}
		}

		/**
		 * Get last HTTP error.
		 *
		 * @return WP_Error|bool
		 */
		public function get_last_error() {
			return self::$last_error;
		}

		/**
		 * Get last HTTP response.
		 *
		 * @return array|bool
		 */
		public function get_last_response() {
			if ( ! self::$last_response ) {
				return false;
			}
			return self::$last_response;
		}

		/**
		 * Get last HTTP response code.
		 *
		 * @return int|bool
		 */
		public function get_last_response_code() {
			if ( ! self::$last_response ) {
				return false;
			}
			return self::$last_response['response']['code'];
		}

		/**
		 * Get last HTTP response total results count.
		 *
		 * @return int
		 */
		public function get_last_response_total_results() {
			if ( ! self::$last_response ) {
				return 0;
			}

			$headers = self::$last_response['headers'];
			if ( ! isset( $headers['X-Total-Results'] ) ) {
				return 0;
			}

			return $headers['X-Total-Results'];
		}

		/**
		 * Request new free version access for given e-mail address.
		 *
		 * @param string $email Email for registering Demo access.
		 * @return MnumiDesigner_Setting|array
		 */
		public function register_free( $email ) {
			$this->pre_request();

			$response = MnumiDesigner_API_Client::request(
				'register/free',
				array(
					'method' => 'POST',
					'body'   => array(
						'email' => $email,
					),
				)
			);

			$this->post_request( $response );

			if ( $this->get_last_error() ) {
				return array();
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			return new MnumiDesigner_Setting( $data );
		}

		/**
		 * Request settings for current API access.
		 *
		 * @return MnumiDesigner_Setting|array
		 */
		public function get_settings() {
			$this->pre_request();

			$response = MnumiDesigner_API_Client::request(
				'system',
				array(
					'method' => 'GET',
				)
			);

			$this->post_request( $response );

			if ( $this->get_last_error() ) {
				return array();
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			$data['id']   = MnumiDesigner_Settings::instance()->get_api_key_id();
			$data['name'] = MnumiDesigner_Settings::instance()->get_api_key();

			return new MnumiDesigner_Setting( $data );
		}

		/**
		 * Request translations for passed locale.
		 *
		 * @param string $locale Locale for which request translations.
		 * @return MnumiDesigner_Translation|array
		 */
		public function get_translations( $locale ) {
			$query = array(
				'locale' => substr( $locale, 0, 2 ),
			);

			$this->pre_request();

			$response = MnumiDesigner_API_Client::request(
				'translations',
				array(
					'method' => 'GET',
					'query'  => $query,
				)
			);

			$this->post_request( $response );

			if ( $this->get_last_error() ) {
				return array();
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			return new MnumiDesigner_Translation( $data );
		}

		/**
		 * Update local settings for current Api key.
		 *
		 * @param array $args Request arguments.
		 * @return MnumiDesigner_Setting|array
		 */
		public function update_settings( $args ) {
			$this->pre_request();

			$response = MnumiDesigner_API_Client::request(
				'system',
				array(
					'method' => 'GET',
					'body'   => $args,
				)
			);

			$this->post_request( $response );

			if ( $this->get_last_error() ) {
				return array();
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			return new MnumiDesigner_Setting( $data );
		}

		/**
		 * Request projects collection.
		 *
		 * @param int    $offset Offset of results.
		 * @param int    $limit How many results to return.
		 * @param string $orderby Sort by given Project property.
		 * @param string $order Sort order for collection.
		 * @param array  $extra_query Additional request arguments.
		 * @return array
		 */
		public function get_projects( $offset = 0, $limit = 5, $orderby = 'updated', $order = 'desc', $extra_query = array() ) {
			$query = array(
				'limit'   => $limit,
				'offset'  => $offset,
				'orderBy' => $orderby,
				'order'   => strtoupper( $order ),
			);

			$query = array_merge( $extra_query, $query );

			$this->pre_request();

			$response = MnumiDesigner_API_Client::request(
				'projects',
				array(
					'method' => 'GET',
					'query'  => $query,
				)
			);

			$this->post_request( $response );

			if ( $this->get_last_error() ) {
				return array();
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			$results = array();

			if ( is_array( $data ) ) {
				foreach ( $data as $project_data ) {
					if ( ! is_array( $project_data ) ) {
						continue;
					}
					$results[] = new MnumiDesigner_Project( $project_data );
				}
			}

			return $results;
		}

		/**
		 * Request templates only collection.
		 *
		 * @param int    $offset Offset of results.
		 * @param int    $limit How many results to return.
		 * @param string $orderby Sort by given Project property.
		 * @param string $order Sort order for collection.
		 * @param array  $extra_query Additional request arguments.
		 * @return array
		 */
		public function get_templates( $offset = 0, $limit = 5, $orderby = 'updated', $order = 'desc', $extra_query = array() ) {
			return $this->get_projects(
				$offset,
				$limit,
				$orderby,
				$order,
				array_merge(
					$extra_query,
					array( 'is_derived' => false )
				)
			);
		}

		/**
		 * Request duplication of given project.
		 *
		 * @param string $id Project Id.
		 * @return bool
		 */
		public function duplicate_template( $id ) {
			$this->pre_request();

			$response = MnumiDesigner_API_Client::request(
				sprintf( 'projects/%s/duplicate', $id ),
				array(
					'method' => 'POST',
				)
			);

			$this->post_request( $response );

			if ( $this->get_last_error() ) {
				return false;
			}
			return true;
		}

		/**
		 * Request deletion of given project.
		 *
		 * @param string $id Project Id.
		 * @param array  $extra_query Additional query.
		 * @return bool
		 */
		public function delete_project( $id, $extra_query = array() ) {
			$this->pre_request();

			$response = MnumiDesigner_API_Client::request(
				sprintf( 'projects/%s', $id ),
				array(
					'method' => 'DELETE',
					'query'  => $extra_query,
				)
			);

			if ( 400 === $response['response']['code'] ) {
				$r        = json_decode( $response['body'], true );
				$response = new WP_Error(
					'rest_cannot_delete',
					__( 'The project cannot be deleted.', 'mnumidesigner' ),
					array(
						'status'      => 400,
						'description' => $r['message'],
					)
				);
			}

			$this->post_request( $response );

			if ( $this->get_last_error() ) {
				return false;
			}

			return true;
		}

		/**
		 * Request deletion of given template.
		 *
		 * @param string $id Project Id.
		 * @return bool
		 */
		public function delete_template( $id ) {
			return $this->delete_project( $id, array( 'isSourceProject' => true ) );
		}

		/**
		 * Request deletion of given user project.
		 *
		 * @param string $id Project Id.
		 * @return bool
		 */
		public function delete_user_project( $id ) {
			return $this->delete_project( $id, array( 'isSourceProject' => false ) );
		}

		/**
		 * Request canceling deletion of given project.
		 *
		 * @param string $id Project Id.
		 * @return bool
		 */
		public function restore_project( $id ) {
			$this->pre_request();

			$response = MnumiDesigner_API_Client::request(
				sprintf( 'projects/%s/cancel-delete', $id ),
				array(
					'method' => 'POST',
				)
			);

			$this->post_request( $response );

			if ( $this->get_last_error() ) {
				return false;
			}
			return true;
		}
	}

endif;
