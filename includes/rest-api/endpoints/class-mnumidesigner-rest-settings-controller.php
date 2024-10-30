<?php
/**
 * Used for managing MnumiDesigner settings
 *
 * @category API
 * @package MnumiDesigner/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * MnumiDesigner_REST_Settings_Controller class.
 *
 * @package MnumiDesigner/API
 * @extends WP_REST_Controller
 */
class MnumiDesigner_REST_Settings_Controller extends WP_REST_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'mnumidesigner/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'domain' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to get MnumiDesigner settings.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'manage_mnumidesigner' );
	}

	/**
	 * Check if a given request has access to set MnumiDesigner settings.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'manage_mnumidesigner' );
	}

	/**
	 * Check if a given request has access to update MnumiDesigner settings.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function update_item_permissions_check( $request ) {
		return current_user_can( 'manage_mnumidesigner' );
	}

	/**
	 * Gets MnumiDesigner settings
	 *
	 * @return MnumiDesigner_Setting
	 */
	private function get_settings() {
		$api  = MnumiDesigner_API::instance();
		$item = $api->get_settings();
		return $item;
	}

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$settings = $this->get_settings();
		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		$settings = $this->prepare_item_for_response( $settings, $request );
		$response = rest_ensure_response( $settings );

		return $response;
	}

	/**
	 * Create settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$api      = MnumiDesigner_API::instance();
		$settings = $api->register_free( get_option( 'admin_email' ) );

		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		// When user requested free version, automatically save api access data.
		update_option( MnumiDesigner_Settings::API_KEY_ID, $settings->get_id() );
		update_option( MnumiDesigner_Settings::API_KEY, $settings->get_key() );

		$settings = $this->prepare_item_for_response( $settings, $request );
		$response = rest_ensure_response( $settings );

		return $response;
	}

	/**
	 * Update settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$settings = array(
			'domain' => $request->get_param( 'domain' ),
		);

		$api  = MnumiDesigner_API::instance();
		$item = $api->update_settings( $settings );

		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		return rest_ensure_response( $api->get_settings() )->set_status( 201 );
	}

	/**
	 * Prepare a single setting output for response.
	 *
	 * @param MnumiDesigner_Setting $setting Settings object.
	 * @param WP_REST_Request       $request Full data about the request.
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $setting, $request ) {
		$data   = array();
		$fields = $this->get_fields_for_response( $request );

		if ( in_array( 'id', $fields, true ) ) {
			$data['id'] = $setting->get_id();
		}
		if ( in_array( 'key', $fields, true ) ) {
			$data['key'] = $setting->get_key();
		}
		if ( in_array( 'domain', $fields, true ) ) {
			$data['domain'] = $setting->get_domain();
		}
		if ( in_array( 'available_project_types', $fields, true ) ) {
			$data['available_project_types'] = $setting->get_available_project_types();
		}

		$api = MnumiDesigner_API::instance();
		if ( in_array( 'is_demo', $fields, true ) ) {
			$data['is_demo'] = $api->is_demo();
		}
		if ( in_array( 'is_demo_active', $fields, true ) ) {
			$data['is_demo_active'] = $api->is_demo_active();
		}
		if ( in_array( 'demo_active_to', $fields, true ) ) {
			$date = $api->demo_active_to();
			if ( $date ) {
				$data['demo_active_to'] = $date->format( 'Y-m-d H:i:s' );
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'embed';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $setting ) );

		return $response;
	}


	/**
	 * Retrieves the projects's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'mnumidesigner-setting',
			'type'       => 'object',
			'properties' => array(
				'id'                      => array(
					'description' => __( 'Secret key ID', 'mnumidesigner' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view' ),
					'readonly'    => true,
				),
				'key'                     => array(
					'description' => __( 'Secret key', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view' ),
					'readonly'    => true,
				),
				'domain'                  => array(
					'description' => __( 'Domain to which redirect to when opening designer.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view' ),
					'readonly'    => true,
				),
				'available_project_types' => array(
					'description' => __( 'Available project types', 'mnumidesigner' ),
					'type'        => 'array',
					'context'     => array( 'embed', 'view' ),
					'readonly'    => true,
				),
				'is_demo'                 => array(
					'description' => __( 'Is demo version access', 'mnumidesigner' ),
					'type'        => 'bool',
					'context'     => array( 'embed', 'view' ),
					'readonly'    => true,
				),
				'is_demo_active'          => array(
					'description' => __( 'Is demo version active', 'mnumidesigner' ),
					'type'        => 'bool',
					'context'     => array( 'embed', 'view' ),
					'readonly'    => true,
				),
				'demo_active_to'          => array(
					'description' => __( 'Is demo version access', 'mnumidesigner' ),
					'type'        => 'date-time',
					'context'     => array( 'embed', 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @return array Links for the given product.
	 */
	protected function prepare_links() {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		$api = MnumiDesigner_API::instance();
		if ( $api->is_demo() ) {
			$links['buy'] = array(
				'href' => 'http://mnumidesigner.com',
			);
		}

		return $links;
	}
}


