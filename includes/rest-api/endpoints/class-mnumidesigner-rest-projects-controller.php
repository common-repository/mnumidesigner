<?php
/**
 * Used for managing MnumiDesigner projects
 *
 * @category API
 * @package MnumiDesigner/API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MnumiDesigner_REST_Projects_Controller class.
 *
 * @package MnumiDesigner/API
 * @extends WP_REST_Controller
 */
class MnumiDesigner_REST_Projects_Controller extends WP_REST_Controller {
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
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
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
						'type'     => array(
							'required'          => true,
							'description'       => __( 'Type of newly created project.', 'mnumidesigner' ),
							'enum'              => array(
								'custom',
								'album-2pages',
								'calendar-12m',
								'business-card',
							),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'width'    => array(
							'required'          => true,
							'description'       => __( 'Width (in mm) of page in project.', 'mnumidesigner' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param ) && $param > 0;
							},
						),
						'height'   => array(
							'required'          => true,
							'description'       => __( 'Height (in mm) of page in project.', 'mnumidesigner' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param ) && $param > 0;
							},
						),
						'back_url' => array(
							'description'       => __( 'URL to redirect back to after creating project.', 'mnumidesigner' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/clone',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clone_item' ),
					'permission_callback' => array( $this, 'clone_item_permissions_check' ), // +
					'args'                => array(
						'product_id' => array(
							'required'          => true,
							'description'       => __( 'Woocommerce product ID.', 'mnumidesigner' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param ) && $param > 0;
							},
						),
						'count'      => array(
							'description'       => __( 'Number of pages in created project.', 'mnumidesigner' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param ) && $param > 0;
							},
						),
						'back_url'   => array(
							'description'       => __( 'URL to redirect back to after creating project.', 'mnumidesigner' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'templates'  => array(
							'description'       => __( 'Available templates provided as base for project.', 'mnumidesigner' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'The ID of the project.', 'mnumidesigner' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(),
				),
				// to unlock creating using backbone.
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
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
			'/' . $this->rest_base . '/(?P<id>[\w]+)/edit',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'The ID of the project to redirect to.', 'mnumidesigner' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_edit_encoded_redirect' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ), // +
					'args'                => array(
						'back_url'  => array(
							'description'       => __( 'URL to redirect back to after saving project.', 'mnumidesigner' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'required'          => true,
						),
						'templates' => array(
							'description'       => __( 'Available templates provided as base for project.', 'mnumidesigner' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w]+)/duplicate',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'The ID of the project to duplicate.', 'mnumidesigner' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'duplicate_item' ),
					'permission_callback' => array( $this, 'duplicate_item_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w]+)/restore',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'The ID of the project to restore.', 'mnumidesigner' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'restore_item' ),
					'permission_callback' => array( $this, 'restore_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to get projects.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'view_mnumidesigner_project' );
	}

	/**
	 * Check if a given request has access to get single project.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		$project_id = wc_clean( $request['id'] );

		return current_user_can( 'view_mnumidesigner_project' ) ||
			mnumidesigner_user_has_project_in_cart( $project_id );
	}

	/**
	 * Check if a given request has access to create project template.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'create_mnumidesigner_template' );
	}

	/**
	 * Check if a given request has access to update project.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function update_item_permissions_check( $request ) {
		return current_user_can( 'edit_mnumidesigner_project' );
	}

	/**
	 * Check if a given request has access to delete project.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function delete_item_permissions_check( $request ) {
		return current_user_can( 'delete_mnumidesigner_project' );
	}

	/**
	 * Check if a given request has access to undelete project.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function restore_item_permissions_check( $request ) {
		return current_user_can( 'delete_mnumidesigner_project' );
	}

	/**
	 * Check if a given request has access to create project based on template.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function clone_item_permissions_check( $request ) {
		return current_user_can( 'create_mnumidesigner_project' ) || true;
	}

	/**
	 * Check if a given request has access to duplicate project.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function duplicate_item_permissions_check( $request ) {
		return current_user_can( 'duplicate_mnumidesigner_project' );
	}

	/**
	 * Check if a given request has access to manage projects.
	 *
	 * @return bool
	 */
	public function current_user_can_manage_projects() {
		return current_user_can( 'manage_mnumidesigner' );
	}

	/**
	 * Not used currently.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 */
	public function update_item( $request ) {
	}

	/**
	 * Get the query params for collections of projects.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

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

		$query_params['ids'] = array(
			'description'       => __( 'Filter collection by object ids.', 'mnumidesigner' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);

		$query_params['template_id'] = array(
			'description'       => __( 'Filter collection by template id.', 'mnumidesigner' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);

		$query_params['is_derived'] = array(
			'description' => __( 'Fiter collection by object attribute.', 'mnumidesigner' ),
			'type'        => 'boolean',
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

		$query_params['changeablePageCount'] = array(
			'description' => __( 'Filter collection to objects changeable page count.', 'mnumidesigner' ),
			'type'        => 'boolean',
		);

		$query_params['defaultPageCount'] = array(
			'description'       => __( 'Filter collection to objects with provided page count.', 'mnumidesigner' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		);

		$query_params['is_pending_removal'] = array(
			'description' => __( 'Filter collection to objects with removal date set.', 'mnumidesigner' ),
			'type'        => 'boolean',
		);

		$query_params['ownershipType'] = array(
			'description'       => __( 'Filter collection by object ownership.', 'mnumidesigner' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'enum'              => array(
				'global',
				'own',
			),
		);

		return $query_params;
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$registered = $this->get_collection_params();

		$items              = array();
		$data               = array();
		$prepared_args      = array();
		$parameter_mappings = array(
			'order'               => 'order',
			'per_page'            => 'limit',
			'ids'                 => 'ids',
			'template_id'         => 'sourceId',
			'type'                => 'type',
			'changeablePageCount' => 'changeablePageCount',
			'defaultPageCount'    => 'defaultPageCount',
			'is_pending_removal'  => 'toRemove',
			'is_derived'          => 'isCloned',
			'ownershipType'       => 'ownershipType',
			'search'              => 'search',
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

		$api            = MnumiDesigner_API::instance();
		$items          = $api->get_projects(
			$offset,
			$per_page,
			$orderby,
			$order,
			$prepared_args
		);
		$total_projects = $api->get_last_response_total_results();

		$data = array();

		foreach ( $items as $item ) {
			$itemdata = $this->prepare_item_for_response( $item, $request );

			if ( is_wp_error( $itemdata ) ) {
				continue;
			}
			$data[] = $this->prepare_response_for_collection( $itemdata );
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
	 * Requests creation of MnumiDesigner teplate
	 *
	 * Returns location where user should be redirected to create new template.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$url = MnumiDesigner_API_Client::get_new_link(
			$request['type'],
			$request['width'],
			$request['height'],
			$request['back_url'],
			$this->current_user_can_manage_projects() ?
				'manage' :
				'enduser'
		);

		if ( $request->get_header( 'X-Requested-With' ) === 'XMLHttpRequest' ) {
			$response = new WP_REST_Response();
			$response->set_status( 202 );
			$response->set_data(
				array(
					'redirect' => $url,
				)
			);
			return $response;
		}

		return new WP_REST_Response(
			$url,
			303,
			array(
				'Location' => $url,
			)
		);
	}

	/**
	 * Requests creation of MnumiDesigner project.
	 *
	 * Returns location where user should be redirected to create new project
	 * based on given templates.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function clone_item( $request ) {
		$product_object = wc_get_product( $request['product_id'] );

		$data = array(
			'action'      => 'initOrder',
			'productName' => $product_object->get_slug(),
			'countChange' => false,
		);
		if ( ! isset( $request['count'] ) ) {
			$data['count'] = get_post_meta( $product_object->get_id(), 'mnumidesigner_project_pages', true );
		} else {
			$data = $request['count'];
		}

		if ( ! isset( $request['back_url'] ) || empty( $request['back_url'] ) ) {
			$data['backUrl'] = $product_object->get_permalink();
		} else {
			$data['backUrl'] = $request['back_url'];
		}

		if ( ! isset( $request['templates'] ) ) {
			$data['wizards'] = implode( ',', get_post_meta( $product_object->get_id(), 'mnumidesigner_project_ids' ) );
		} else {
			$data['wizards'] = $request['templates'];
		}

		if ( $request->get_header( 'X-Requested-With' ) === 'XMLHttpRequest' ) {
			$response = new WP_REST_Response();
			$response->set_status( 202 );
			$response->set_data(
				array(
					'redirect' => $url,
				)
			);
			return $response;
		}

		return new WP_REST_Response(
			$url,
			303,
			array(
				'Location' => $url,
			)
		);
	}

	/**
	 * Get single project from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		if ( ! MnumiDesigner::is_configured() ) {
			return new WP_Error( 'rest_not_configured', __( 'MnumiDesigner configuration is not set.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		$project = $this->get_project( $request['id'] );
		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		$project = $this->prepare_item_for_response( $project, $request );
		if ( is_wp_error( $project ) ) {
			return $project;
		}

		$response = rest_ensure_response( $project );

		return $response;
	}

	/**
	 * Requests encoded link for editing MnumiDesigner project.
	 *
	 * Returns location where user should be redirected to edit given project.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item_edit_encoded_redirect( $request ) {
		$id        = $request['id'];
		$back_url  = $request['back_url'];
		$templates = $request['templates'];

		$url = do_shortcode(
			sprintf(
				'[%s project="%s" back_url="%s" templates="%s" display="%s"]',
				MnumiDesigner_Shortcode_Edit_Project_Url::SHORTCODE,
				$id,
				$back_url,
				$templates,
				$this->current_user_can_manage_projects() ?
				'manage' :
				'enduser'
			)
		);

		if ( $request->get_header( 'X-Requested-With' ) === 'XMLHttpRequest' ) {
			$response = new WP_REST_Response();
			$response->set_data(
				array(
					'redirect' => $url,
				)
			);
			return $response;
		}

		return new WP_REST_Response(
			$url,
			303,
			array(
				'Location' => $url,
			)
		);
	}

	/**
	 * Duplicate single project.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function duplicate_item( $request ) {
		$project = $this->get_project( $request['id'] );
		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		$api    = MnumiDesigner_API::instance();
		$result = null;
		if ( ! $project->is_cloned() ) {
			if ( ! $api->duplicate_template( $request['id'] ) ) {
				return new WP_Error( 'rest_cannot_duplicate', __( 'Problem when duplicating project.', 'mnumidesigner' ), array( 'status' => 500 ) );
			}

			return $api->get_last_response();
		} else {
			return new WP_Error( 'rest_cannot_duplicate', __( 'The project cannot be duplicated.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		$project = $this->prepare_item_for_response( $project, $request );

		$response = rest_ensure_response( $project );

		return $response;
	}

	/**
	 * Delete single project.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$project = $this->get_project( $request['id'] );
		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		$request->set_param( 'context', 'edit' );

		$previous = $this->prepare_item_for_response( $project, $request );
		$response = rest_ensure_response( $project );

		$api    = MnumiDesigner_API::instance();
		$result = null;

		$result = $api->delete_project(
			$request['id'],
			array(
				'isSourceProject' => ! $project->is_cloned(),
			)
		);

		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		if ( ! $result ) {
			return new WP_Error( 'rest_cannot_delete', __( 'The project cannot be deleted.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		do_action( 'mnumidesigner_rest_delete_project', $project, $response, $request );

		return $response;
	}

	/**
	 * Undelete single project.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function restore_item( $request ) {
		$project = $this->get_project( $request['id'] );
		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		$request->set_param( 'context', 'edit' );

		$api      = MnumiDesigner_API::instance();
		$result   = null;
		$result   = $api->restore_project( $request['id'] );
		$previous = $this->prepare_item_for_response( $project, $request );

		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		if ( ! $result ) {
			return new WP_Error( 'rest_cannot_restore', __( 'The project cannot be restored.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		$project = $this->prepare_item_for_response( $project, $request );

		$response = rest_ensure_response( $project );

		return $response;
	}

	/**
	 * Gets single project by Id.
	 *
	 * @param string $slug Project Id.
	 * @return WP_Error|MnumiDesigner_Project
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

		if ( is_wp_error( MnumiDesigner_API::instance()->get_last_error() ) ) {
			return MnumiDesigner_API::instance()->get_last_error();
		}

		if ( empty( $item ) ) {
			return new WP_Error( 'rest_project_not_found', __( 'The project cannot be found.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		return $item[0];
	}

	/**
	 * Prepare a single project output for response.
	 *
	 * @param MnumiDesigner_Project $project Project.
	 * @param WP_REST_Request       $request Full data about the request.
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $project, $request ) {
		if ( is_wp_error( $project ) ) {
			return $project;
		}

		$data   = array();
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

		if ( in_array( 'created_date', $fields, true ) ) {
			$data['created_date'] = date_i18n(
				sprintf(
					'%s %s',
					get_option( 'date_format' ),
					get_option( 'time_format' )
				),
				$project->get_created_at()->getTimestamp()
			);
		}

		if ( in_array( 'updated_date', $fields, true ) ) {
			$data['updated_date'] = date_i18n(
				sprintf(
					'%s %s',
					get_option( 'date_format' ),
					get_option( 'time_format' )
				),
				$project->get_updated_at()->getTimestamp()
			);
		}

		if ( in_array( 'number_of_pages', $fields, true ) ) {
			$data['number_of_pages'] = $project->get_pages_count();
		}

		if ( in_array( 'number_of_pages_for_price', $fields, true ) ) {
			$data['number_of_pages_for_price'] = $project->get_pages_count_for_price();
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

		if ( in_array( 'remove_at', $fields, true ) ) {
			$data['remove_at'] = $project->is_pending_removal() ?
				date_i18n(
					sprintf(
						'%s %s',
						get_option( 'date_format' ),
						get_option( 'time_format' )
					),
					$project->get_remove_at()->getTimestamp()
				) :
				null;
		}

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			if ( in_array( 'linked_products', $fields, true ) ) {
				$args     = array(
					'mnumidesigner_project_ids' => array( $project->get_project_id() ),
				);
				$products = wc_get_products( $args );

				foreach ( $products as $product ) {
					$data['linked_products'][] = array(
						'id'   => $product->get_id(),
						'name' => $product->get_name(),
						'link' => get_edit_post_link( $product->get_id() ),
					);
				}
			}

			if ( in_array( 'linked_items', $fields, true ) ) {
				$order_item_id = $this->get_order_item_id_by_project_id( $project->get_project_id() );

				if ( $order_item_id > 0 ) {
					$store    = new WC_Order_Item_Data_Store();
					$order_id = $store->get_order_id_by_order_item_id( $order_item_id );

					$order = wc_get_order( $order_id );

					$data['linked_items'][] = array(
						'id'   => $order_item_id,
						'name' => $order_id,
						'link' => get_edit_post_link( $order_id ),
					);
				}
			}
		}

		$data['preview'] = $project->get_preview();

		$context = ! empty( $request['context'] ) ? $request['context'] : 'embed';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $project ) );

		return $response;
	}


	private function get_order_item_id_by_project_id( $project_id ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key LIKE %s AND meta_value LIKE %s",
				"'mnumidesigner_project_id'",
				"'" . $project_id . "'"
			)
		);
	}

	/**
	 * Retrieves the projects's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'project',
			'type'       => 'object',
			'properties' => array(
				'id'                        => array(
					'description' => __( 'An alphanumeric identifier for the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'type'                      => array(
					'description' => __( 'Machine type of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'type_label'                => array(
					'description' => __( 'Localized type of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'project_label'             => array(
					'description' => __( 'Label of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'created_date'              => array(
					'description' => __( 'Creation date of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'updated_date'              => array(
					'description' => __( 'Last modification date of the project.', 'mnumidesigner' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'number_of_pages'           => array(
					'description' => __( 'Number of pages in the project.', 'mnumidesigner' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'number_of_pages_for_price' => array(
					'description' => __( 'Number of priceable pages in the project', 'mnumidesigner' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'is_derived'                => array(
					'description' => __( 'Indicates if project is derived from template.', 'mnumidesigner' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'template_id'               => array(
					'description' => __( 'Template ID the derived project is based on.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'is_global'                 => array(
					'description' => __( 'Indicates if project is global.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'is_pending_removal'        => array(
					'description' => __( 'Indicates if project will be removed', 'mnumidesigner' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'remove_at'                 => array(
					'description' => __( 'Date when project will be removed', 'mnumidesigner' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'linked_products'           => array(
					'description' => __( 'Products to which this project is attached to', 'mnumidesigner' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'type' => 'number',
							),
							'name' => array(
								'type' => 'string',
							),
							'link' => array(
								'type' => 'string',
							),
						),
					),
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),
				'linked_items'              => array(
					'description' => __( 'WooCommerce Order Items to which this project is attached to', 'mnumidesigner' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'type' => 'number',
							),
							'name' => array(
								'type' => 'string',
							),
							'link' => array(
								'type' => 'string',
							),
						),
					),
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
	 * @param MnumiDesigner_Project $project Request object.
	 * @return array Links for the given project.
	 */
	protected function prepare_links( $project ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '%s/%s/%s', $this->namespace, $this->rest_base, $project->get_project_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		$links['preview'] = array(
			'href' => $project->get_preview(),
		);
		if ( ! $project->is_pending_removal() ) {
			if ( $project->can_edit() ) {
				$links['edit'] = array(
					'href' => rest_url( sprintf( '%s/%s/%s/edit', $this->namespace, $this->rest_base, $project->get_project_id() ) ),
				);
			}
			if ( ! $project->is_cloned() && $project->can_duplicate() ) {
				$links['duplicate'] = array(
					'href' => rest_url( sprintf( '%s/%s/%s/duplicate', $this->namespace, $this->rest_base, $project->get_project_id() ) ),
				);
			}

			if ( $project->can_delete() ) {
				$links['delete'] = array(
					'href' => rest_url( sprintf( '%s/%s/%s', $this->namespace, $this->rest_base, $project->get_project_id() ) ),
				);
			}
		}

		return $links;
	}
}


