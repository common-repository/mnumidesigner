<?php
/**
 * Used for integrating products with MnumiDesigner templates
 *
 * @category API
 * @package MnumiDesigner/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * MnumiDesigner_REST_Products_Controller class.
 *
 * @package MnumiDesigner/API
 * @extends WP_REST_Controller
 */
class MnumiDesigner_REST_Products_Controller extends WP_REST_Controller {
	/**
	 * Parent route base.
	 *
	 * @var string
	 */
	protected $parent_base = 'products';

	/**
	 * Route base.
	 *
	 * @var MnumiDesigner_REST_Projects_Controller
	 */
	protected $parent_controller;

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
	protected $rest_base = 'projects';

	/**
	 * Class constructor.
	 *
	 * Initializes required variables.
	 */
	public function __construct() {
		$this->parent_controller = new MnumiDesigner_REST_Projects_Controller();
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->parent_base . '/(?P<parent>[\d]+)/' . $this->rest_base,
			array(
				'args' => array(
					'parent' => array(
						'description' => __( 'The ID for the parent of the object.', 'mnumidesigner' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'string',
							'description'       => __( 'Template ID of project to attach to product.', 'mnumidesigner' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->parent_base . '/(?P<parent>[\d]+)/' . $this->rest_base . '/(?P<id>[\w]+)',
			array(
				'args'   => array(
					'parent' => array(
						'description' => __( 'The ID for the parent of the object.', 'mnumidesigner' ),
						'type'        => 'integer',
					),
					'id'     => array(
						'description' => __( 'Template ID of project .', 'mnumidesigner' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->parent_base . '/(?P<parent>[\d]+)/' . $this->rest_base . '/(?P<id>[\w]+)/attach',
			array(
				'args' => array(
					'parent' => array(
						'description' => __( 'The ID for the parent of the object.', 'mnumidesigner' ),
						'type'        => 'integer',
					),
					'id'     => array(
						'description' => __( 'Template ID of project .', 'mnumidesigner' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'attach_item' ),
					'permission_callback' => array( $this, 'attach_item_permissions_check' ),
					'args'                => array(
						'variation_id' => array(
							'required'          => false,
							'type'              => 'integer',
							'description'       => __( 'Product variation id.', 'mnumidesigner' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to get product templates.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'view_mnumidesigner_template' );
	}

	/**
	 * Check if a given request has access to attach templates to product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function attach_item_permissions_check( $request ) {
		return current_user_can( 'attach_mnumidesigner_template' );
	}

	/**
	 * Check if a given request has access to get product template details.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'view_mnumidesigner_template' );
	}

	/**
	 * Check if a given request has access to attach templates to product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'create_mnumidesigner_template' );
	}

	/**
	 * Check if a given request has access to detach templates from product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function delete_item_permissions_check( $request ) {
		return current_user_can( 'delete_mnumidesigner_template' );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();
		unset( $query_params['search'] );

		$query_params['offset'] = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'mnumidesigner' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		);

		$query_params['order'] = array(
			'default'           => 'desc',
			'description'       => __( 'Order sort attribute ascending or descending.', 'mnumidesigner' ),
			'enum'              => array( 'asc', 'desc' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);

		$query_params['orderby'] = array(
			'default'           => 'updated_date',
			'description'       => __( 'Sort collection by object attribute.', 'mnumidesigner' ),
			'enum'              => array(
				'id',
				'created_date',
				'updated_date',
			),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);

		$query_params['type'] = array(
			'description'       => __( 'Filter collection by object type.', 'mnumidesigner' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'enum'              => array(
				'custom',
				'album-2pages',
				'calendar-12m',
				'business-card',
			),
		);

		$query_params['variation_id']   = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'mnumidesigner' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		);
		$query_params['variation_loop'] = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'mnumidesigner' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		);

		return $query_params;
	}

	/**
	 * Gets product
	 *
	 * @param int $parent Parent product id.
	 * @return WC_Product|WC_Product_Variation
	 */
	protected function get_parent( $parent ) {
		$product_object = wc_get_product( $parent );

		return $product_object;
	}

	/**
	 * Get single product tempalate
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$product = $this->get_parent( $request['parent'] );

		$product_or_variation_id = $product->get_id();
		if ( isset( $request['variation_id'] ) ) {
			$product_or_variation_id = $request['variation_id'];
		}

		$template_ids = get_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids' );

		$project = $this->get_project( $request['id'] );

		if ( ! $project ) {
			return new WP_Error( 'rest_cannot_get', __( 'Template does not exist.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		if ( ! in_array( $project->get_project_id(), $template_ids, true ) ) {
			return new WP_Error( 'rest_cannot_get', __( 'Template is not attached.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		$project  = $this->prepare_item_for_response( $project, $request );
		$response = rest_ensure_response( $project );

		return $response;
	}

	/**
	 * Get a collection of product templates.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$registered = $this->get_collection_params();

		$items              = array(); // do a query, call another class, etc.
		$data               = array();
		$prepared_args      = array();
		$parameter_mappings = array(
			'order'       => 'order',
			'per_page'    => 'limit',
			'template_id' => 'orderId',
			'type'        => 'type',
		);

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $prepared_args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$prepared_args[ $wp_param ] = $request[ $api_param ];
			}
		}

		if ( isset( $registered['offset'] ) && ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['limit'];
		}

		if ( isset( $registered['orderby'] ) ) {
			$orderby_possibles        = array(
				'id'           => 'orderId',
				'created_date' => 'created',
				'updated_date' => 'updated',
			);
			$prepared_args['orderby'] = $orderby_possibles[ $request['orderby'] ];
		}

		$offset   = $prepared_args['offset'];
		$per_page = (int) $prepared_args['limit'];
		$orderby  = $prepared_args['orderby'];
		$order    = $prepared_args['order'];

		unset( $prepared_args['offset'] );
		unset( $prepared_args['limit'] );
		unset( $prepared_args['orderby'] );
		unset( $prepared_args['order'] );

		$product = $this->get_parent( $request['parent'] );

		$product_or_variation_id = $product->get_id();
		if ( isset( $request['variation_id'] ) ) {
			$product_or_variation_id = $request['variation_id'];
		}

		$template_ids = get_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids' );

		$total_projects = 0;
		$data           = array();

		if ( ! empty( $template_ids ) ) {
			$prepared_args['ids']           = implode( ',', $template_ids );
			$prepared_args['sourceOrderId'] = 'NULL';

			$api            = MnumiDesigner_API::instance();
			$items          = $api->get_projects(
				$offset,
				$per_page,
				$orderby,
				$order,
				$prepared_args
			);
			$total_projects = $api->get_last_response_total_results();

			foreach ( $items as $item ) {
				$itemdata = $this->prepare_item_for_response( $item, $request );
				$data[]   = $this->prepare_response_for_collection( $itemdata );
			}
		}

		$response = rest_ensure_response( $data );

		$page = ceil( ( ( (int) $offset ) / $per_page ) + 1 );

		$response->header( 'X-WP-Total', (int) $total_projects );

		$max_pages = ceil( $total_projects / $per_page );

		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( urlencode_deep( $request->get_query_params() ), rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );

			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Attaches single template to product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$product = $this->get_parent( $request['parent'] );

		if ( ! $product ) {
			return new WP_Error( 'rest_cannot_attach', __( 'Product does not exist.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		$template_ids            = array();
		$product_or_variation_id = $product->get_id();

		if ( isset( $request['variation_id'] ) ) {
			$product_or_variation_id = $request['variation_id'];
		}

		$template     = $this->get_project( $request['id'] );
		$template_ids = get_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids' );

		if ( ! $template ) {
			return new WP_Error( 'rest_cannot_attach', __( 'Project does not exist.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		if ( $template->is_cloned() ) {
			return new WP_Error( 'rest_cannot_attach', __( 'Project is not a template.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		if ( in_array( $template->get_project_id(), $template_ids, true ) ) {
			return new WP_Error( 'rest_cannot_attach', __( 'Project is already attached.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		$template_ids[] = $template->get_project_id();

		// delete already existing projects to add new under separate meta entries under same meta key.
		delete_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids' );
		foreach ( $template_ids as $id ) {
			// Clean from projects:
			// case 1: non existing projects
			// case 2: changed api key ID (free -> paid)
			if ( $this->get_project( $id ) === false ) {
				continue;
			}
			add_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids', $id );
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'projects' => $template_ids,
			)
		);

		return $response;
	}

	/**
	 * Attaches single template to product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function attach_item( $request ) {
		$product = $this->get_parent( $request['parent'] );

		if ( ! $product ) {
			return new WP_Error( 'rest_cannot_attach', __( 'Product does not exist.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		$product_or_variation_id = $product->get_id();
		if ( isset( $request['variation_id'] ) ) {
			$product_or_variation_id = $request['variation_id'];
		}

		$template     = $this->get_project( $request['id'] );
		$template_ids = get_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids' );

		if ( ! $template ) {
			return new WP_Error( 'rest_cannot_attach', __( 'Project does not exist.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		if ( $template->is_cloned() ) {
			return new WP_Error( 'rest_cannot_attach', __( 'Project is not a template.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		if ( in_array( $template->get_project_id(), $template_ids, true ) ) {
			return new WP_Error( 'rest_cannot_attach', __( 'Project is already attached.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		$template_ids[] = $template->get_project_id();

		// delete already existing projects to add new under separate meta entries under same meta key.
		delete_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids' );
		foreach ( $template_ids as $id ) {
			// Clean from projects:
			// case 1: non existing projects
			// case 2: changed api key ID (free -> paid)
			if ( $this->get_project( $id ) === false ) {
				continue;
			}
			add_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids', $id );
		}

		$url = admin_url( sprintf( 'post.php?post=%d&action=edit', $product->get_id() ) );

		return new WP_REST_Response(
			$url,
			303,
			array(
				'Location' => $url,
			)
		);
	}

	/**
	 * Detaches single template from product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$product = $this->get_parent( $request['parent'] );

		if ( ! $product ) {
			return new WP_Error( 'rest_cannot_dettach', __( 'Product does not exist.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		$product_or_variation_id = $product->get_id();
		if ( isset( $request['variation_id'] ) ) {
			$product_or_variation_id = $request['variation_id'];
		}

		$template_ids = get_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids' );

		delete_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids' );
		foreach ( $template_ids as $id ) {
			if ( $id !== $request['id'] ) {
				add_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids', $id );
			}
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'removed' => true,
			)
		);

		return $response;
	}

	/**
	 * Gets project by id.
	 *
	 * @param string $slug Project Id.
	 * @return MnumiDesigner_Project|bool MnumiDesigner_Project instance or false if not found
	 */
	private function get_project( $slug ) {
		$api  = MnumiDesigner_API::instance();
		$item = $api->get_projects(
			0,
			1,
			'updated',
			'asc',
			array(
				'ids' => $slug,
			)
		);
		if ( ! $item ) {
			return false;
		}

		return $item[0];
	}

	/**
	 * Prepare a single product template output for response.
	 *
	 * @param  MnumiDesigner_Project $project Project.
	 * @param  WP_REST_Request       $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $project, $request ) {
		$data           = array();
		$data['parent'] = $request['parent'];

		$fields = $this->get_fields_for_response( $request );
		if ( in_array( 'id', $fields, true ) ) {
			$data['id'] = $project->get_project_id();
		}

		if ( in_array( 'type', $fields, true ) ) {
			$data['type'] = $project->get_type();
		}

		if ( in_array( 'type_label', $fields, true ) ) {
			$data['type_label'] = $project->get_type_label();
		}

		if ( in_array( 'project_label', $fields, true ) ) {
			$data['project_label'] = $project->get_project_label();
		}

		if ( in_array( 'project_label', $fields, true ) ) {
			$data['project_label'] = $project->get_project_label();
		}

		if ( in_array( 'created_date', $fields, true ) ) {
			$data['created_date'] = $project->get_created_at()->format( 'c' );
		}

		if ( in_array( 'updated_date', $fields, true ) ) {
			$data['updated_date'] = $project->get_updated_at()->format( 'c' );
		}

		if ( in_array( 'number_of_pages', $fields, true ) ) {
			$data['number_of_pages'] = $project->get_pages_count();
		}

		if ( in_array( 'is_derived', $fields, true ) ) {
			$data['is_derived'] = $project->is_cloned();
		}

		if ( in_array( 'template_id', $fields, true ) ) {
			$data['template_id'] = $project->get_source_project_id();
		}

		if ( in_array( 'is_global', $fields, true ) ) {
			$data['is_global'] = $project->is_readonly();
		}

		if ( in_array( 'is_pending_removal', $fields, true ) ) {
			$data['is_pending_removal'] = $project->is_pending_removal();
		}

		$data['preview'] = $project->get_preview();

		$context = ! empty( $request['context'] ) ? $request['context'] : 'embed';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$product = $this->get_parent( $request['parent'] );
		$response->add_links( $this->prepare_links( $product, $project ) );

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
			'title'      => 'product-project',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'description' => __( 'An alphanumeric identifier for the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'type'               => array(
					'description' => __( 'Machine type of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'type_label'         => array(
					'description' => __( 'Localized type of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'project_label'      => array(
					'description' => __( 'Label of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'created_date'       => array(
					'description' => __( 'Creation date of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'updated_date'       => array(
					'description' => __( 'Last modification date of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'number_of_pages'    => array(
					'description' => __( 'Number of pages in the project.', 'mnumidesigner' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'is_derived'         => array(
					'description' => __( 'Indicates if project is derived from template.', 'mnumidesigner' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'template_id'        => array(
					'description' => __( 'Template ID the derived project is based on.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'is_global'          => array(
					'description' => __( 'Indicates if project is global.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'is_pending_removal' => array(
					'description' => __( 'Indicates if project will be removed', 'mnumidesigner' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Product|WC_Product_Variation $product WooCommerce parent product.
	 * @param MnumiDesigner_Project           $project Request object.
	 * @return array Links for the given product.
	 */
	protected function prepare_links( $product, $project ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '%s/%s/%s', $this->namespace, 'projects', $project->get_project_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s/%d/projects', $this->namespace, $this->rest_base, $product->get_ID() ) ),
			),
		);

		$links['preview'] = array(
			'href' => $project->get_preview(),
		);

		if ( $project->can_edit() ) {
			$links['edit'] = array(
				'href' => rest_url( sprintf( '%s/%s/%s/edit', $this->namespace, 'projects', $project->get_project_id() ) ),
			);
		}
		if ( $project->can_duplicate() ) {
			$links['duplicate'] = array(
				'href' => rest_url( sprintf( '%s/%s/%s/duplicate', $this->namespace, $this->rest_base, $project->get_project_id() ) ),
			);
		}

		$links['attach'] = array(
			'href' => rest_url( sprintf( '%s/%s/%d/projects/%s/attach', $this->namespace, $this->parent_base, $product->get_ID(), $project->get_project_id() ) ),
		);

		$links['delete'] = array(
			'href' => rest_url( sprintf( '%s/%s/%d/projects/%s', $this->namespace, $this->parent_base, $product->get_ID(), $project->get_project_id() ) ),
		);

		$links['parent'] = array(
			'href' => rest_url( sprintf( '%s/%s/%d', 'wc/v1', $this->parent_base, $product->get_ID() ) ),
		);

		return $links;
	}
}


