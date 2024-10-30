<?php
/**
 * Used for managing MnumiDesigner translations
 *
 * @category API
 * @package MnumiDesigner/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * MnumiDesigner_REST_Translations_Controller class.
 *
 * @package MnumiDesigner/API
 * @extends WP_REST_Controller
 */
class MnumiDesigner_REST_Translations_Controller extends WP_REST_Controller {
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
	protected $rest_base = 'translations';

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
						'description' => __( 'Unique identifier of the translation.', 'mnumidesigner' ),
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
	 * Get the query params for collections of translations.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['name']   = array(
			'description'       => __( 'Filter collection by object name.', 'mnumidesigner' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);
		$query_params['locale'] = array(
			'description'       => __( 'Filter collection by object locale.', 'mnumidesigner' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		);
		return $query_params;
	}

	/**
	 * Check if a given request has access to get translations.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'view_mnumidesigner_translation' );
	}

	/**
	 * Check if a given request has access to get single translation.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'view_mnumidesigner_translation' );
	}

	/**
	 * Check if a given request has access to create translation.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'create_mnumidesigner_translation' );
	}

	/**
	 * Check if a given request has access to update translation.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function update_item_permissions_check( $request ) {
		return current_user_can( 'edit_mnumidesigner_translation' );
	}

	/**
	 * Check if a given request has access to delete translations.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function delete_item_permissions_check( $request ) {
		return current_user_can( 'delete_mnumidesigner_translation' );
	}

	/**
	 * Get collection of available translations.
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
			'locale'   => 'locale',
		);

		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$prepared_args[ $wp_param ] = $request[ $api_param ];
			}
		}

		$page     = $prepared_args['page'];
		$per_page = $prepared_args['per_page'];
		$offset   = $page * $per_page - $per_page;

		$locale = get_locale();

		$dir = MnumiDesigner::plugin_translations_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$it    = new CallbackFilterIterator(
			new FilesystemIterator( $dir ),
			function ( $file, $key, $iterator ) use ( $prepared_args ) {
				if ( ! mnumidesigner_is_translation_filename_valid( $file ) ) {
					return false;
				}

				$meta = mnumidesigner_get_translation_file_meta( $file );

				foreach ( array( 'name', 'locale' ) as $filter ) {
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
			$meta     = mnumidesigner_get_translation_file_meta( $file );
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
	 * Create single translation.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$request['id'] = sprintf(
			'%s.%s.%s',
			sanitize_title( $request['name'] ),
			'editor',
			$request['locale']
		);

		$dir = MnumiDesigner::plugin_translations_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$file = $this->get_file( $request['id'] );

		if ( $file->isFile() ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Translation already exists.', 'mnumidesigner' ),
				array( 'status' => 400 )
			);
		}

		$api         = MnumiDesigner_API::instance();
		$translation = $api->get_translations( $request['locale'] );

		if ( ! $translation instanceof MnumiDesigner_Translation ) {
			return $api->get_last_error();
		}

		$data = $translation->get_translation_entries();
		if ( isset( $request['translations'] ) && is_array( $request['translations'] ) ) {
			foreach ( $request['translations'] as $translation_entry ) {
				$data[ $translation_entry['id'] ] = $translation_entry['translation'];
			}
		}

		$fileobj = $file->openFile( 'w' );
		$fileobj->fwrite( wp_json_encode( $data ) );
		$fileobj = null;

		$data = $this->prepare_item_for_response( $file, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Gets translation file by id.
	 *
	 * @param string $id Translation file id.
	 * @return SplFileInfo
	 */
	public function get_file( $id ) {
		return mnumidesigner_get_translation_file( $id );
	}

	/**
	 * Update single translation.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$file     = $this->get_file( $request['id'] );
		$fs       = new WP_Filesystem_Direct( '' );
		$contents = $fs->get_contents( $file->getRealPath() );

		$data = json_decode( $contents, true );
		if ( isset( $request['translations'] ) && is_array( $request['translations'] ) ) {
			foreach ( $request['translations'] as $translation_entry ) {
				$data[ $translation_entry['id'] ] = $translation_entry['translation'];
			}
		}

		$fs->put_contents( $file->getRealPath(), wp_json_encode( $data ), 0644 );

		$data = $this->prepare_item_for_response( $file, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Get single translation.
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
	 * Delete single translation.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$file = $this->get_file( $request['id'] );

		if ( ! $file->isFile() ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Translation file is not a file.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		if ( ! $file->isWritable() ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Insufficient file permissions for removing translation.', 'mnumidesigner' ), array( 'status' => 500 ) );
		}

		$result = unlink( $file->getRealPath() );

		if ( ! $result ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Translation cannot be deleted.', 'mnumidesigner' ), array( 'status' => 500 ) );
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
	 * Prepare a single translation output for response.
	 *
	 * @param SplFileInfo     $file Translation file object.
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $file, $request ) {
		$meta = mnumidesigner_get_translation_file_meta( $file );

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
				$meta['domain'],
				$meta['locale']
			);
		}

		if ( in_array( 'link', $fields, true ) ) {
			$data['link'] = mnumidesigner_get_translation_file_url( $file );
		}

		if ( in_array( 'translations', $fields, true ) ) {
			$api          = MnumiDesigner_API::instance();
			$translations = $api->get_translations( $meta['locale'] );

			$data['translations'] = array();

			if ( ! $api->get_last_error() ) {
				$remote = $translations->get_translation_entries();

				$fs    = new WP_Filesystem_Direct( '' );
				$trans = json_decode( $fs->get_contents( $file->getRealPath() ), true );
				foreach ( $trans as $id => $translation ) {
					$data['translations'][] = array(
						'id'          => $id,
						'original'    => $remote[ $id ],
						'translation' => $translation,
					);
					unset( $remote[ $id ] );
				}
				foreach ( $remote as $id => $translation ) {
					$data['translations'][] = array(
						'id'          => $id,
						'original'    => $translation,
						'translation' => $translation,
					);
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
	 * Retrieves the translation's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'translation',
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'description' => __( 'Unique id of translation', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'name'         => array(
					'description' => __( 'Name for translation.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'domain'       => array(
					'description' => __( 'Translation domain', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'editor' ),
					'readonly'    => true,
				),
				'locale'       => array(
					'description' => __( 'Locale of translation.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'fallback'     => array(
					'description' => __( 'Translation fallback locale', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified'     => array(
					'description' => __( 'The date the object was last modified, in the site\'s timezone.', 'mnumidesigner' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'link'         => array(
					'description' => __( 'URL to the object', 'mnumidesigner' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'format'      => 'uri',
					'readonly'    => true,
				),
				'translations' => array(
					'description' => __( 'Translations', 'mnumidesigner' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'required'    => false,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'          => array(
								'description' => __( 'ID of translation entry', 'mnumidesigner' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'required'    => true,
							),
							'translation' => array(
								'description' => __( 'Translation value', 'mnumidesigner' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'required'    => true,
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
	 * @return array Links for the given translation.
	 */
	protected function prepare_links( SplFileInfo $file ) {
		$meta = mnumidesigner_get_translation_file_meta( $file );

		$id = sprintf(
			'%s.%s.%s',
			$meta['name'],
			$meta['domain'],
			$meta['locale']
		);

		$links = array(
			'self'         => array(
				'href' => rest_url(
					sprintf(
						'/%s/%s/%s',
						$this->namespace,
						$this->rest_base,
						$id
					)
				),
			),
			'collection'   => array(
				'href' => rest_url(
					sprintf(
						'/%s/%s',
						$this->namespace,
						$this->rest_base
					)
				),
			),
			'translations' => array(
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


