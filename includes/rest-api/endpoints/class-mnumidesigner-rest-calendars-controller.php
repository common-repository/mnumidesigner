<?php
/**
 * Used for managing MnumiDesigner calendar events
 *
 * @category API
 * @package MnumiDesigner/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * MnumiDesigner_REST_Calendars_Controller class.
 *
 * @package MnumiDesigner/API
 * @extends WP_REST_Controller
 */
class MnumiDesigner_REST_Calendars_Controller extends WP_REST_Controller {
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
	protected $rest_base = 'calendars';

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
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w.-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier of the calendar.', 'mnumidesigner' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get the query params for collections of calendars.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['name'] = array(
			'description'       => __( 'Filter collection by object name.', 'mnumidesigner' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);
		$query_params['type'] = array(
			'description'       => __( 'Filter collection by object type.', 'mnumidesigner' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);
		return $query_params;
	}

	/**
	 * Check if a given request has access to get calendars.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'view_mnumidesigner_calendar' );
	}

	/**
	 * Check if a given request has access to get single calendar.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'view_mnumidesigner_calendar' );
	}

	/**
	 * Check if a given request has access to create calendar.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'create_mnumidesigner_calendar' );
	}

	/**
	 * Check if a given request has access to update calendar.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function update_item_permissions_check( $request ) {
		return current_user_can( 'edit_mnumidesigner_calendar' );
	}

	/**
	 * Check if a given request has access to delete calendars.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function delete_item_permissions_check( $request ) {
		return current_user_can( 'delete_mnumidesigner_calendar' );
	}

	/**
	 * Get collection of available calendars.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$registered = $this->get_collection_params();

		$data               = array();
		$prepared_args      = array();
		$parameter_mappings = array(
			'page'     => 'page',
			'per_page' => 'per_page',
			'name'     => 'name',
			'type'     => 'type',
		);

		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$prepared_args[ $wp_param ] = $request[ $api_param ];
			}
		}

		$page     = $prepared_args['page'];
		$per_page = $prepared_args['per_page'];
		$offset   = $page * $per_page - $per_page;

		$dir = MnumiDesigner::plugin_calendars_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$it    = new CallbackFilterIterator(
			new FilesystemIterator( $dir ),
			function ( $file, $key, $iterator ) use ( $prepared_args ) {
				if ( ! mnumidesigner_is_calendar_filename_valid( $file ) ) {
					return false;
				}

				$meta = mnumidesigner_get_calendar_file_meta( $file );

				foreach ( array( 'name', 'type' ) as $filter ) {
					if ( isset( $prepared_args[ $filter ] ) && ( $meta[ $filter ] !== $prepared_args[ $filter ] ) ) {
						return false;
					}
				}

				return true;
			}
		);
		$total = iterator_count( $it );
		$it->rewind();
		$it = new LimitIterator( $it, $offset, $per_page );

		foreach ( $it as $key => $file ) {
			$meta     = mnumidesigner_get_calendar_file_meta( $file );
			$itemdata = $this->prepare_item_for_response( $file, $request );
			$data[]   = $this->prepare_response_for_collection( $itemdata );
		}

		$response = rest_ensure_response( $data );

		$response->header( 'X-WP-Total', $total );

		$max_pages = ceil( $total / $per_page );

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
	 * Create single calendar.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$request['id'] = sprintf(
			'%s.%s.%s',
			sanitize_title( $request['name'] ),
			$request['type'],
			$request['locale']
		);

		$dir = MnumiDesigner::plugin_calendars_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$file = $this->get_file( $request['id'] );

		if ( $file->isFile() ) {
			return new WP_Error( 'rest_cannot_create', __( 'Calendar already exists.', 'mnumidesigner' ), array( 'status' => 400 ) );
		}

		$api = MnumiDesigner_API::instance();

		$data = array();
		if ( isset( $request['events'] ) && is_array( $request['events'] ) ) {
			foreach ( $request['events'] as $event_entry ) {
				$data[ $event_entry['date'] ][] = $event_entry['name'];
			}
		}

		$fileobj = $file->openFile( 'w' );
		$fileobj->fwrite( wp_json_encode( $data ) );
		$fileobj = null;

		$data = $this->prepare_item_for_response( $file, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Gets calendar file by id.
	 *
	 * @param string $id Calendar file id.
	 * @return SplFileInfo
	 */
	public function get_file( $id ) {
		return mnumidesigner_get_calendar_file( $id );
	}

	/**
	 * Update single calendar.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$file = $this->get_file( $request['id'] );
		$meta = mnumidesigner_get_calendar_file_meta( $file );
		$fs   = new WP_Filesystem_Direct( '' );

		$data = array();
		if ( isset( $request['events'] ) && is_array( $request['events'] ) ) {
			foreach ( $request['events'] as $event_entry ) {
				if ( $event_entry['cyclic'] ) {
					$event_entry['date'] = substr( $event_entry['date'], 5 );
				}
				if ( null === $event_entry['type'] ) {
					$event_entry['type'] = '';
				}
				if ( 'national-day' === $meta['type'] ) {
					$data[ $event_entry['date'] ][] = array(
						'name' => $event_entry['name'],
						'type' => $event_entry['type'],
					);
				} else {
					$data[ $event_entry['date'] ][] = $event_entry['name'];
				}
			}
		}

		$fs->put_contents( $file->getRealPath(), wp_json_encode( $data ), 0644 );

		$data = $this->prepare_item_for_response( $file, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Get single calendar.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$file = $this->get_file( $request['id'] );

		if ( ! $file->isFile() ) {
			return rest_ensure_response( array() );
		}

		$data = $this->prepare_item_for_response( $file, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Delete single calendar.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$file = $this->get_file( $request['id'] );

		if ( ! $file->isFile() ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Calendar file is not a file.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		if ( ! $file->isWritable() ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Insufficient file permissions for removing calendar.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		$result = unlink( $file->getRealPath() );

		if ( ! $result ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Calendar cannot be deleted.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted' => true,
			)
		);

		return $response;
	}

	/**
	 * Prepare a single calendar output for response.
	 *
	 * @param SplFileInfo     $file Calendar file object.
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $file, $request ) {
		$meta = mnumidesigner_get_calendar_file_meta( $file );

		$data   = array();
		$fields = $this->get_fields_for_response( $request );

		foreach ( $fields as $field_name ) {
			if ( array_key_exists( $field_name, $meta ) ) {
				$data[ $field_name ] = $meta[ $field_name ];
			}
		}

		if ( in_array( 'id', $fields, true ) ) {
			$data['id'] = sprintf(
				'%s.%s.%s',
				$meta['name'],
				$meta['type'],
				$meta['locale']
			);
		}

		if ( in_array( 'link', $fields, true ) ) {
			$data['link'] = mnumidesigner_get_calendar_file_url( $file );
		}

		if ( in_array( 'events', $fields, true ) ) {
			$data['events'] = array();

			$fs     = new WP_Filesystem_Direct( '' );
			$events = json_decode( $fs->get_contents( $file->getRealPath() ), true );

			foreach ( $events as $date => $day ) {
				foreach ( $day as $event_entry ) {
					$has_year = strlen( $date ) === 10;
					$cyclic   = ! $has_year;
					if ( $cyclic ) {
						$date = gmdate( 'Y' ) . '-' . $date;
					}

					if ( 'national-day' === $meta['type'] ) {
						$data['events'][] = array(
							'date'   => $date,
							'cyclic' => $cyclic,
							'name'   => $event_entry['name'],
							'type'   => $event_entry['type'],
						);
					} else {
						$data['events'][] = array(
							'date'   => $date,
							'cyclic' => $cyclic,
							'name'   => $event_entry,
							'type'   => '',
						);
					}
				}
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );

		$data = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $file, $request ) );

		return $response;
	}

	/**
	 * Retrieves the calendar's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'calendar',
			'type'       => 'object',
			'properties' => array(
				'id'       => array(
					'description' => __( 'Unique id of calendar', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'name'     => array(
					'description' => __( 'Name for calendar.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'type'     => array(
					'description' => __( 'Calendar type', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'name-day', 'national-day' ),
					'readonly'    => true,
				),
				'locale'   => array(
					'description' => __( 'Locale of calendar.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'modified' => array(
					'description' => __( 'The date the object was last modified, in the site\'s timezone.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'link'     => array(
					'description' => __( 'URL to the object', 'mnumidesigner' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'format'      => 'uri',
					'readonly'    => true,
				),
				'events'   => array(
					'description' => __( 'Calendar events', 'mnumidesigner' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'required'    => false,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'date'   => array(
								'description' => __( 'Event date', 'mnumidesigner' ),
								'type'        => 'date',
								'context'     => array( 'view', 'edit' ),
								'required'    => true,
							),
							'name'   => array(
								'description' => __( 'Event name', 'mnumidesigner' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'required'    => true,
							),
							'cyclic' => array(
								'description' => __( 'Is event cyclic?', 'mnumidesigner' ),
								'type'        => 'bool',
								'context'     => array( 'view', 'edit' ),
							),
							'type'   => array(
								'description' => __( 'Event type', 'mnumidesigner' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'required'    => false,
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param SplFileInfo $file Request object.
	 * @return array Links for the given calendar.
	 */
	protected function prepare_links( SplFileInfo $file ) {
		$meta = mnumidesigner_get_calendar_file_meta( $file );

		$id = sprintf(
			'%s.%s.%s',
			$meta['name'],
			$meta['type'],
			$meta['locale']
		);

		$links = array(
			'self'       => array(
				'href' => rest_url(
					sprintf(
						'/%s/%s/%s',
						$this->namespace,
						$this->rest_base,
						$id
					)
				),
			),
			'collection' => array(
				'href' => rest_url(
					sprintf(
						'/%s/%s',
						$this->namespace,
						$this->rest_base
					)
				),
			),
			'events'     => array(
				'href'       => rest_url(
					sprintf(
						'/%s/%s/%s/entries',
						$this->namespace,
						$this->rest_base,
						$id
					)
				),
				'embeddable' => true,
			),
		);

		return $links;
	}
}


