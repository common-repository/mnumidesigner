<?php
/**
 * WooCommerce Product admin view integration for MnumiDesigner
 *
 * @package MnumiDesigner/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * MnumiDesigner_WC_Product class.
 */
class MnumiDesigner_WC_Product {
	const TAB = 'mnumidesigner_product_tab_content';

	/**
	 * MnumiDesigner_WC_Product class constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_head', array( $this, 'admin_head' ) );

		/**
		 * Add Mnumidesigner product data tab
		 */
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'tab' ), 10, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'product_data_panels' ) );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'product_after_variable_attributes' ), 10, 3 );

		add_action( 'woocommerce_process_product_meta_simple', array( $this, 'process_product_meta_simple' ) );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_product_variation' ), 10, 2 );
	}

	/**
	 * Gets templates attached to product/variation.
	 *
	 * @param WC_Product|WC_Product_Variation $product_or_variation WooCommerce product.
	 * @return array|null
	 */
	public static function get_templates( $product_or_variation ) {
		return get_post_meta( $product_or_variation->get_id(), 'mnumidesigner_project_ids' );
	}

	/**
	 * Gets price per MnumiDesigner project page set on product/variation.
	 *
	 * @param WC_Product|WC_Product_Variation $product_or_variation WooCommerce product.
	 * @return string|null
	 */
	public static function get_price_per_page( $product_or_variation ) {
		return get_post_meta( $product_or_variation->get_id(), 'mnumidesigner_price_per_page', true );
	}

	/**
	 * Gets MnumiDesigner translation set on product/variation.
	 *
	 * @param WC_Product|WC_Product_Variation $product_or_variation WooCommerce product.
	 * @return string|null
	 */
	public static function get_translation( $product_or_variation ) {
		return get_post_meta( $product_or_variation->get_id(), 'mnumidesigner_translation', true );
	}

	/**
	 * Gets MnumiDesigner event calendars set on product/variation.
	 *
	 * @param WC_Product|WC_Product_Variation $product_or_variation WooCommerce product.
	 * @return string|null
	 */
	public static function get_event_calendars( $product_or_variation ) {
		return get_post_meta( $product_or_variation->get_id(), 'mnumidesigner_calendars', true );
	}

	/**
	 * Checks if given product/product variation has attached MnumiDesigner templates.
	 *
	 * @param WC_Product|WC_Product_Variation $product_or_variation WooCommerce product.
	 * @return bool
	 */
	public static function has_templates( $product_or_variation ) {
		$templates = self::get_templates( $product_or_variation );
		return is_array( $templates ) && count( $templates ) > 0;
	}

	/**
	 * Checks if given product/product variation has set price per MnumiDesigner project page.
	 *
	 * @param WC_Product|WC_Product_Variation $product_or_variation WooCommerce product.
	 * @return bool
	 */
	public static function has_price_per_page( $product_or_variation ) {
		$price_per_page = self::get_price_per_page( $product_or_variation );
		return is_numeric( $price_per_page );
	}

	/**
	 * Checks if given product/product variation has MnumiDesigner settings.
	 *
	 * @param WC_Product|WC_Product_Variation $product_or_variation WooCommerce product.
	 * @return bool
	 */
	public static function is_mnumidesigner_product( $product_or_variation ) {
		return self::has_templates( $product_or_variation ) &&
			in_array(
				$product_or_variation->get_type(),
				array(
					'simple',
					'variable',
					'product_variation',
					'variation',
				),
				true
			);
	}

	/**
	 * Output MnumiDesigner scripts on admin page.
	 */
	public function admin_head() {
		global $post;

		if ( ! $post ) {
			return;
		}

		if ( 'product' === $post->post_type ) {
			$this->script_templates();
		}
	}

	/**
	 * Enqueue backend scripts.
	 *
	 * @param string $hook Current screen Id.
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			global $post;

			if ( ! $post ) {
				return;
			}

			if ( 'product' === $post->post_type ) {
				$product = wc_get_product( $post->ID );
				if ( ! $product ) {
					return;
				}
				wp_register_script(
					'mnumidesigner-table',
					MnumiDesigner::plugin_dir_url() . 'assets/js/admin/table.js',
					array( 'jquery', 'jquery-ui-dialog', 'wp-api', 'wp-backbone', 'underscore' ),
					MnumiDesigner::version(),
					true
				);
				wp_register_script(
					'mnumidesigner-wc-product',
					MnumiDesigner::plugin_dir_url() . 'assets/js/admin/woocommerce/product.js',
					array( 'jquery', 'wp-api', 'wp-backbone', 'jquery-ui-dialog' ),
					MnumiDesigner::version(),
					true
				);

				wp_localize_script(
					'mnumidesigner-wc-product',
					'MnumiDesigner',
					array(
						'namespace'   => 'mnumidesigner/v1/',
						'apiRoot'     => get_rest_url(),
						'product_id'  => $product->get_ID(),
						'emulateHTTP' => MnumiDesigner_Settings::instance()->get_emulate_http(),
						'api'         => array(
							'delete'       => array(
								'url' => esc_url_raw( rest_url( sprintf( 'mnumidesigner/v1/wc/products/%d/templates', $product->get_ID() ) ) ),
							),
							'add_new'      => array(
								'title'    => __( 'Create new template', 'mnumidesigner' ),
								'back_url' => esc_url_raw(
									rest_url(
										sprintf(
											'mnumidesigner/v1/products/%d/projects/%%projectId%%/attach',
											$product->get_ID()
										)
									)
								),
							),
							'add_existing' => array(
								'title'      => __( 'Attach existing template', 'mnumidesigner' ),
								'attach_url' => esc_url_raw(
									rest_url(
										sprintf(
											'mnumidesigner/v1/products/%d/projects',
											$product->get_ID()
										)
									)
								),
								'filters'    => array(
									'ownership_types' => MnumiDesigner::instance()->get_ownership_types(),
									'types'           => MnumiDesigner::instance()->get_types(),
								),
							),
						),
					)
				);

				wp_enqueue_script( 'mnumidesigner-wc-product' );
			}
		}
	}

	/**
	 * Register MnumiDesigner tab for WooCommerce Simple Product.
	 *
	 * @param array $tabs Available tabs in Woocommerce Simple Product.
	 * @return array
	 */
	public function tab( $tabs ) {
		$tabs['mnumidesigner-tab'] = array(
			'label'    => __( 'MnumiDesigner', 'mnumidesigner' ),
			'priority' => 50,
			'target'   => self::TAB,
			'class'    => array( 'show_if_simple' ),
		);

		return $tabs;
	}

	/**
	 * Output generic MnumiDesigner fields, used in both simple and variable products.
	 *
	 * @param bool                            $is_variation Indicates if given product is of variationt.
	 * @param WC_Product|WC_Product_Variation $product_or_variation WooCommerce product to render fields to.
	 * @param int                             $variation_loop Current product variation loop index.
	 * @param array                           $variation_data Current product variation data.
	 */
	private function generic_fields( $is_variation, $product_or_variation = null, $variation_loop = null, $variation_data = null ) {
		$id = ( $is_variation ? $product_or_variation->ID : $product_or_variation->get_ID() );
		woocommerce_wp_text_input(
			array(
				'id'                => 'mnumidesigner_project_pages' . ( $is_variation ? '_' . $variation_loop : '' ),
				'name'              => 'mnumidesigner_project_pages' . ( $is_variation ? '[' . $variation_loop . ']' : '' ),
				'label'             => __( 'Number of pages in customer project', 'mnumidesigner' ),
				'placeholder'       => __( 'Number of pages', 'mnumidesigner' ),
				'desc_tip'          => 'true',
				'description'       => __( 'Enter number of pages customer projects should have.', 'mnumidesigner' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'min' => 1,
				),
				'value'             => get_post_meta( $id, 'mnumidesigner_project_pages', true ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'    => 'mnumidesigner_price_per_page' . ( $is_variation ? '_' . $variation_loop : '' ),
				'name'  => 'mnumidesigner_price_per_page' . ( $is_variation ? '[' . $variation_loop . ']' : '' ),
				'class' => 'wc_input_price short',
				'label' => __( 'Additional price per page', 'mnumidesigner' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'value' => get_post_meta( $id, 'mnumidesigner_price_per_page', true ),
			)
		);

		woocommerce_wp_select(
			array(
				'id'      => 'mnumidesigner_count_change' . ( $is_variation ? '_' . $variation_loop : '' ),
				'name'    => 'mnumidesigner_count_change' . ( $is_variation ? '[' . $variation_loop . ']' : '' ),
				'label'   => __( 'Can customer change numer of pages?', 'mnumidesigner' ),
				'options' => array(
					0 => __( 'Customer can not add/remove pages', 'mnumidesigner' ),
					1 => __( 'Customer can add/remove pages', 'mnumidesigner' ),
				),
				'value'   => get_post_meta( $id, 'mnumidesigner_count_change', true ),
			)
		);

		$options[''] = '';
		$request     = new WP_REST_Request(
			'GET',
			'/mnumidesigner/v1/translations'
		);

		$response = rest_do_request( $request );

		$server = rest_get_server();
		$data   = $server->response_to_data( $response, false );
		foreach ( $data as $entry ) {
			$options[ $entry['id'] ] = sprintf(
				'%s (%s)',
				$entry['name'],
				$entry['locale']
			);
		}

		woocommerce_wp_select(
			array(
				'id'      => 'mnumidesigner_translation' . ( $is_variation ? '_' . $variation_loop : '' ),
				'name'    => 'mnumidesigner_translation' . ( $is_variation ? '[' . $variation_loop . ']' : '' ),
				'label'   => __( 'Custom translation', 'mnumidesigner' ),
				'options' => $options,
				'value'   => get_post_meta( $id, 'mnumidesigner_translation', true ),
			)
		);

		$options = array();
		$request = new WP_REST_Request(
			'GET',
			'/mnumidesigner/v1/calendars'
		);

		$response = rest_do_request( $request );

		$server = rest_get_server();
		$data   = $server->response_to_data( $response, false );
		foreach ( $data as $entry ) {
			$options[ $entry['id'] ] = sprintf(
				'%s (%s / %s)',
				$entry['name'],
				$entry['type'],
				$entry['locale']
			);
		}

		woocommerce_wp_select(
			array(
				'id'                => 'mnumidesigner_calendars' . ( $is_variation ? '_' . $variation_loop : '' ) . '[]',
				'name'              => 'mnumidesigner_calendars' . ( $is_variation ? '[' . $variation_loop . ']' : '' ) . '[]',
				'label'             => __( 'Custom calendars', 'mnumidesigner' ),
				'options'           => $options,
				'value'             => get_post_meta( $id, 'mnumidesigner_calendars', true ),
				'custom_attributes' => array(
					'multiple' => 'multiple',
				),
			)
		);
	}

	/**
	 * Output generic MnumiDesigner buttons, used in both simple and variable products.
	 */
	private function generic_buttons() {
		?>
		<div style="padding: 1em;">
			<?php if ( current_user_can( 'create_mnumidesigner_template' ) ) : ?>
			<button type="button" class="button open-new-designer-project-dialog"><?php esc_html_e( 'Create new template', 'mnumidesigner' ); ?></button>
			<?php endif; ?>
			<?php if ( current_user_can( 'attach_mnumidesigner_template' ) ) : ?>
			<button type="button" class="button open-add-existing-designer-project-dialog add_existing"><?php esc_html_e( 'Attach existing template', 'mnumidesigner' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Output container for MnumiDesigner projects loaded by backbone.
	 */
	private function templates_list_container() {
		?>
		<div class="wc-metaboxes" id="templates-list"></div>
		<?php
	}

	/**
	 * Output MnumiDesigner fields, used in simple products.
	 */
	public function product_data_panels() {
		global $post;
		$product = wc_get_product( $post->ID );
		// 'id' attribute needs to match the 'target' parameter set above
		?>
		<div
			id="<?php echo esc_attr( self::TAB ); ?>"
			class="panel woocommerce_options_panel wc-metaboxes-wrapper hidden mnumidesigner-simple-product-fields" >
			<div class="options_group" >
				<?php $this->generic_fields( false, $product ); ?>
				<?php $this->generic_buttons(); ?>
			</div>
			<?php $this->templates_list_container(); ?>
		</div>
		<?php
	}

	/**
	 * Output MnumiDesigner fields, used in variable products.
	 *
	 * @param int                  $loop Current variation loop index.
	 * @param array                $variation_data Current variation data.
	 * @param WC_Product_Variation $variation Current WooCommerce Variation Product.
	 */
	public function product_after_variable_attributes( $loop, $variation_data, $variation ) {
		?>
		<div class="wc-metabox mnumidesigner-variadic-metabox">
			<h3>
				MnumiDesigner
				<div class="handlediv" aria-label="<?php echo esc_attr( __( 'Click to toggle', 'mnumidesigner' ) ); ?>"></div>
			</h3>
			<div
				class="wc-metabox-content mnumidesigner-variadic-product-fields"
				data-variation-loop="<?php echo esc_attr( $loop ); ?>"
				data-variation-id="<?php echo esc_attr( $variation->ID ); ?>"
				style="display: none;"
				>
				<div class="data" >
					<?php $this->generic_fields( true, $variation, $loop, $variation_data ); ?>
					<?php $this->generic_buttons(); ?>
				</div>
				<?php $this->templates_list_container(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Saves MnumiDesigner fields in simple WooCommerce product.
	 *
	 * @param WC_Product $post_id Id of WooCommerce Product.
	 */
	public function process_product_meta_simple( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! ( isset( $_POST['woocommerce_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) ) {
			return;
		}

		foreach ( array(
			'mnumidesigner_project_pages',
			'mnumidesigner_price_per_page',
			'mnumidesigner_count_change',
		) as $key ) {
			if ( ! empty( $_POST[ $key ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				if ( is_numeric( $value ) ) {
					update_post_meta( $post_id, $key, $value );
				}
			} else {
				delete_post_meta( $post_id, $key );
			}
		}

		$key = 'mnumidesigner_translation';
		if ( ! empty( $_POST[ $key ] ) && strlen( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) > 0 ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			update_post_meta( $post_id, $key, $value );
		} else {
			delete_post_meta( $post_id, $key );
		}

		$key = 'mnumidesigner_calendars';
		if ( ! empty( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) {
			$to_save = array();
			foreach ( $_POST[ $key ] as $calendar ) {
				if ( strlen( sanitize_text_field( wp_unslash( $calendar ) ) ) > 0 ) {
					$to_save[] = sanitize_text_field( wp_unslash( $calendar ) );
				}
			}

			update_post_meta( $post_id, $key, $to_save );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}

	/**
	 * Saves MnumiDesigner fields in variable WooCommerce product.
	 *
	 * @param WC_Product $variation_id Id of WooCommerce Product Variation.
	 * @param WC_Product $i Loop index of WooCommerce Product Variation.
	 */
	public function save_product_variation( $variation_id, $i ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		check_ajax_referer( 'save-variations', 'security' );

		foreach ( array(
			'mnumidesigner_project_pages',
			'mnumidesigner_price_per_page',
			'mnumidesigner_count_change',
		) as $key ) {
			if ( isset( $_POST[ $key ] ) && isset( $_POST[ $key ][ $i ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $key ][ $i ] ) );
				if ( is_numeric( $value ) ) {
					update_post_meta( $variation_id, $key, $value );
				}
			} else {
				delete_post_meta( $variation_id, $key );
			}
		}

		$key = 'mnumidesigner_translation';
		if ( isset( $_POST[ $key ] ) && isset( $_POST[ $key ][ $i ] ) && strlen( sanitize_text_field( wp_unslash( $_POST[ $key ][ $i ] ) ) ) > 0 ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $key ][ $i ] ) );
			update_post_meta( $variation_id, $key, $value );
		} else {
			delete_post_meta( $variation_id, $key );
		}

		$key = 'mnumidesigner_calendars';
		if ( ! empty( $_POST[ $key ] ) && isset( $_POST[ $key ][ $i ] ) && is_array( $_POST[ $key ][ $i ] ) ) {
			$to_save = array();
			foreach ( $_POST[ $key ][ $i ] as $calendar ) {
				if ( strlen( sanitize_text_field( wp_unslash( $calendar ) ) ) > 0 ) {
					$to_save[] = sanitize_text_field( wp_unslash( $calendar ) );
				}
			}

			update_post_meta( $post_id, $key, $to_save );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}

	/**
	 * Output script templates.
	 */
	private function script_templates() {
		$request = new WP_REST_Request(
			'GET',
			'/mnumidesigner/v1/settings'
		);

		$response = rest_do_request( $request );
		$server   = rest_get_server();
		$data     = $server->response_to_data( $response, false );
		?>

		<?php if ( isset( $data['available_project_types'] ) ) : ?>
			<?php $available_project_types = $data['available_project_types']; ?>
			<?php include dirname( __FILE__ ) . '/../../includes/admin/backbone/html-new-project-template-form.php'; ?>
		<?php else : ?>
			<script type="text/html" id="tmpl-mnumidesigner-new-template">
				<?php echo esc_html( $data['message'] ); ?>
			</script>
		<?php endif; ?>
		<?php
		include dirname( __FILE__ ) . '/../../includes/admin/backbone/html-wc-product.php';
	}
}
new MnumiDesigner_WC_Product();

