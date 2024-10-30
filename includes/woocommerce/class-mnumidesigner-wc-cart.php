<?php
/**
 * MnumiDesigner Admin Templates class
 *
 * @package MnumiDesigner/WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * MnumiDesigner_WC_Cart class.
 */
class MnumiDesigner_WC_Cart {
	const PROJECT_ID_FIELD_NAME    = 'mnumidesigner_project_id';
	const PROJECT_PAGES_FIELD_NAME = 'mnumidesigner_pages_count';

	/**
	 * Singleton instance of MnumiDesigner_WC_Cart.
	 *
	 * @var MnumiDesigner_WC_Cart
	 */
	private static $instance;

	/**
	 * Initialize MnumiDesigner_WC_Cart instance.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
	}

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'query_vars', array( $this, 'add_project_id_query_var' ) );

		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'add_to_cart_text' ), 20, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_text' ), 20, 2 );

		add_action( 'woocommerce_add_to_cart_validation', array( $this, 'add_to_cart_validation' ), 10, 5 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );

		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 11, 3 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'checkout_create_order_line_item' ), 10, 4 );

		add_action( 'woocommerce_remove_cart_item', array( $this, 'remove_cart_item' ), 1, 1 );
		add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'before_cart_item_quantity_zero' ), 1, 1 );

		add_action( 'woocommerce_restore_cart_item', array( $this, 'restore_cart_item' ), 10, 2 );

		add_filter( 'woocommerce_cart_id', array( $this, 'force_mnumidesigner_products_uniqueness' ), 10, 5 );

		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'force_mnumidesigner_products_redirect' ) );
		add_filter( 'woocommerce_cart_item_thumbnail', array( $this, 'show_mnumidesigner_project_thumbnail' ), 10, 3 );

		add_action( 'woocommerce_before_calculate_totals', array( $this, 'update_price' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'variable_product_add_to_text_handling' ), 22 );

		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'add_to_cart_fragments' ), 10, 1 );
	}

	/**
	 * Add Project Id to Query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function add_project_id_query_var( $vars ) {
		$vars[] = self::PROJECT_ID_FIELD_NAME;
		return $vars;
	}

	/**
	 * Handler for Add to Cart button for Variable Products
	 */
	public function variable_product_add_to_text_handling() {
		$product                  = wc_get_product();
		$mnumidesigner_variations = array();
		if ( $product ) {
			if ( $product->is_type( 'variable' ) ) {
				$all_variations = $product->get_available_variations();
				foreach ( $all_variations as $all_variations ) {
					$variation = wc_get_product( $all_variations['variation_id'] );

					if ( MnumiDesigner_WC_Product::is_mnumidesigner_product( $variation ) ) {
						$mnumidesigner_variations[ $variation->get_id() ] = get_option( 'mnumidesigner_add_to_cart_label', 'Personalize' );
					}
				}
			}

			wp_register_script(
				'mnumidesigner_frontend',
				MnumiDesigner::plugin_dir_url() . 'assets/js/frontend.js',
				array( 'jquery' ),
				MnumiDesigner::version(),
				true
			);
			wp_localize_script(
				'mnumidesigner_frontend',
				'mnumidesigner_frontend',
				array(
					'variations'         => $mnumidesigner_variations,
					'default_cart_label' => __( 'Add to cart', 'mnumidesigner' ),
				)
			);
			wp_enqueue_script( 'mnumidesigner_frontend' );
		}
	}

	/**
	 * Change "Add to Cart" Text for Simple Products containing MnumiDesigner templates.
	 *
	 * @param string     $text Add to cart button text.
	 * @param WC_Product $product Product for which method is invoked.
	 *
	 * @return string
	 */
	public function add_to_cart_text( $text, $product ) {
		if ( MnumiDesigner_WC_Product::is_mnumidesigner_product( $product ) ) {
			$text = get_option( 'mnumidesigner_add_to_cart_label', 'Personalize' );
		}
		return $text;
	}

	/**
	 * Adds Project ID value to Cart item
	 *
	 * @param array $cart_item_data Cart item data.
	 * @return array
	 */
	private function add_to_cart_data( $cart_item_data ) {
		$field = self::PROJECT_ID_FIELD_NAME;
		if ( ! empty( $_REQUEST[ $field ] ) ) {
			$project_id               = sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) );
			$cart_item_data[ $field ] = $project_id;
		}

		return $cart_item_data;
	}

	/**
	 * Adds Project ID value to Cart item
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id Product Id.
	 * @param int   $variation_id Product Variation Id.
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		return $this->add_to_cart_data( $cart_item_data );
	}

	/**
	 * Adds Project ID value to Cart item
	 *
	 * @param array  $cart_item Cart item.
	 * @param array  $values Values.
	 * @param string $key Key.
	 * @return array
	 */
	public function get_cart_item_from_session( $cart_item, $values, $key ) {
		return $this->add_to_cart_data( $cart_item );
	}

	/**
	 * Validates Project ID value in Cart
	 *
	 * @return bool
	 */
	public function add_to_cart_validation() {
		$field = self::PROJECT_ID_FIELD_NAME;
		if ( ! empty( $_REQUEST[ $field ] ) ) {
			$project_id = sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) );
			if ( 10 !== strlen( $project_id ) ) {
				wc_add_notice( __( 'Invalid project ID', 'mnumidesigner' ), 'error' );
				return false;
			}
		}
		// currently ignore PROJECT_PAGES_FIELD_NAME.
		return true;
	}

	/**
	 * Validates Project ID value in Cart
	 *
	 * @param array $item_data Cart item data.
	 * @param array $cart_item Cart item.
	 * @return bool
	 */
	public function get_item_data( $item_data, $cart_item ) {
		if ( ! MnumiDesigner::is_configured() ) {
			return $item_data;
		}

		$product_or_variation = $cart_item['data'];
		if ( ! MnumiDesigner_WC_Product::is_mnumidesigner_product( $product_or_variation ) ) {
			return $item_data;
		}

		$project_id = mnumidesigner_cart_item_get( $cart_item['key'], self::PROJECT_ID_FIELD_NAME, false );

		if ( $project_id ) {
			$project_id = wc_clean( $project_id );

			$templates = implode( ',', MnumiDesigner_WC_Product::get_templates( $product_or_variation ) );

			$extra = '';
			$trans = MnumiDesigner_WC_Product::get_translation( $product_or_variation );
			if ( $trans ) {
				$extra .= sprintf( ' translation_id="%s"', $trans );
			}

			$cals = MnumiDesigner_WC_Product::get_event_calendars( $product_or_variation );
			if ( is_array( $cals ) && count( $cals ) > 0 ) {
				$extra .= sprintf( ' calendar_ids="%s"', implode( ',', $cals ) );
			}

			$item_data[] = array(
				'name'    => __( 'Project', 'mnumidesigner' ),
				'value'   => $project_id,
				'display' => do_shortcode(
					sprintf(
						'[%s project="%s" back_url="%s" templates="%s" %s]',
						MnumiDesigner_Shortcode_Edit_Project_Link::SHORTCODE,
						$project_id,
						wc_get_cart_url(),
						$templates,
						$extra
					)
				),
			);
		} else {
			$product_id              = $product_or_variation->get_id();
			$product_or_variation_id = $product_or_variation->get_id();
			$variation_att           = '';
			if ( $product_or_variation instanceof WC_Product_Variation ) {
				$product_id    = $product_or_variation->get_parent_id();
				$variation_att = sprintf( 'variation_id="%s"', $product_or_variation->get_id() );
			}

			$trans = MnumiDesigner_WC_Product::get_translation( $product_or_variation );
			if ( $trans ) {
				$variation_att .= sprintf( ' translation_id="%s"', $trans );
			}

			$cals = MnumiDesigner_WC_Product::get_event_calendars( $product_or_variation );
			if ( is_array( $cals ) && count( $cals ) > 0 ) {
				$variation_att .= sprintf( ' calendar_ids="%s"', implode( ',', $cals ) );
			}

			$templates   = implode( ',', get_post_meta( $product_or_variation_id, 'mnumidesigner_project_ids' ) );
			$item_data[] = array(
				'name'    => __( 'Project', 'mnumidesigner' ),
				'value'   => $project_id,
				'display' => do_shortcode(
					sprintf(
						'[%s product_id="%d" back_url="%s" %s]',
						MnumiDesigner_WC_Shortcode_New_Project_Link::SHORTCODE,
						$product_id,
						MnumiDesigner_BackUrl_Handler::get_permalink(
							array(
								'mnumidesigner_uri_action' => 'attach',
								'mnumidesigner_uri_cart_item_id' => $cart_item['key'],
								'mnumidesigner_uri_project_id' => '%s',
								'mnumidesigner_uri_pages_count' => '%s',
							)
						),
						$variation_att
					)
				),
			);
		}

		return $item_data;
	}

	/**
	 * Adds Checkout order line item related to MnumiDesigner project.
	 *
	 * @param WC_Order_Item_Product $item Cart item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values Values.
	 * @param WC_Order              $order Order instance.
	 */
	public function checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( isset( $values[ self::PROJECT_ID_FIELD_NAME ] ) ) {
			$item->add_meta_data( self::PROJECT_ID_FIELD_NAME, $values[ self::PROJECT_ID_FIELD_NAME ], true );
		}
	}

	/**
	 * Remove project for given cart item key.
	 *
	 * @param string $cart_item_key Cart item key.
	 */
	public function remove_cart_item( $cart_item_key/*, $cart*/ ) {
		$id = mnumidesigner_cart_item_get( $cart_item_key, self::PROJECT_ID_FIELD_NAME, false );
		if ( $id ) {
			$api = MnumiDesigner_API::instance();
			$api->delete_user_project( $id );
		}
	}

	/**
	 * Remove project for given cart item key when quantity = 0.
	 *
	 * @param string $cart_item_key Cart item key.
	 */
	public function before_cart_item_quantity_zero( $cart_item_key ) {
		$this->remove_cart_item( $cart_item_key );
	}

	/**
	 * Restore project for given cart item key.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param array  $cart Cart.
	 */
	public function restore_cart_item( $cart_item_key, $cart ) {
		$id = mnumidesigner_cart_item_get( $cart_item_key, self::PROJECT_ID_FIELD_NAME, false );
		if ( $id ) {
			$api = MnumiDesigner_API::instance();
			$api->restore_project( $id );
		}
	}

	/**
	 * Restore project for given cart item key.
	 *
	 * @param string $md5 Current hash.
	 * @param int    $product_id - id of the product the key is being generated for.
	 * @param int    $variation_id of the product the key is being generated for.
	 * @param array  $variation data for the cart item.
	 * @param array  $cart_item_data Cart item data.
	 * @return string cart item key
	 */
	public function force_mnumidesigner_products_uniqueness( $md5, $product_id, $variation_id, $variation, $cart_item_data ) {
		$product = wc_get_product( $product_id );
		if ( MnumiDesigner_WC_Product::is_mnumidesigner_product( $product ) ) {
			$templates = MnumiDesigner_WC_Product::get_templates( $product );
			$md5      .= md5( implode( ',', $templates ) ) . $variation_id . uniqid();
		}

		return $md5;
	}

	/**
	 * Returns latest cart item.
	 *
	 * @return mixed
	 */
	private function get_latest_cart_item() {
		$latest_cart_item = end( WC()->cart->cart_contents );

		return $latest_cart_item;
	}

	/**
	 * Sets MnumiDesigner as redirect destination after adding to cart product
	 * configured with MnumiDesigner.
	 *
	 * @param string $url Original destination url.
	 * @return string
	 */
	public function force_mnumidesigner_products_redirect( $url ) {
		if ( empty( $_REQUEST['add-to-cart'] ) ) {
			return $url;
		}
		if ( ! is_numeric( $_REQUEST['add-to-cart'] ) ) {
			return $url;
		}
		if ( ! MnumiDesigner::is_configured() ) {
			return $url;
		}

		$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );

		$product = wc_get_product( $product_id );

		$latest_cart_item = $this->get_latest_cart_item();
		$new_url          = $this->get_attach_url_for_cart_item( $latest_cart_item );
		if ( $new_url ) {
			return $new_url;
		}
		return $url;
	}

	/**
	 * Sets MnumiDesigner project thumbnail for Products in cart containing user
	 * projects.
	 *
	 * @param string $_product_img HTML img tag.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function show_mnumidesigner_project_thumbnail( $_product_img, $cart_item, $cart_item_key ) {
		if ( ! MnumiDesigner::is_configured() ) {
			return $_product_img;
		}

		$product = $cart_item['data'];
		if ( ! MnumiDesigner_WC_Product::is_mnumidesigner_product( $product ) ) {
			return $_product_img;
		}
		$project_id = mnumidesigner_cart_item_get( $cart_item['key'], self::PROJECT_ID_FIELD_NAME, false );

		if ( ! $project_id ) {
			return $_product_img;
		}

		$settings = MnumiDesigner_Settings::instance();
		return sprintf( '<img src="%svi/%s/default.jpg">', $settings->get_api_host(), $project_id );
	}

	/**
	 * Updates price in cart for MnumiDesigner related projects.
	 *
	 * @param WC_Cart $cart_object Cart.
	 */
	public function update_price( $cart_object ) {
		$cart_items = $cart_object->cart_contents;

		if ( ! empty( $cart_items ) ) {
			foreach ( $cart_items as $key => $cart_item ) {
				$product = $cart_item['data'];

				// Ignore calculating price for products without templates and price per page.
				if ( ! MnumiDesigner_WC_Product::is_mnumidesigner_product( $product ) ) {
					continue;
				}

				if ( ! MnumiDesigner_WC_Product::has_price_per_page( $product ) ) {
					continue;
				}

				// Ignore when no user project is set.
				if ( ! isset( $cart_item['mnumidesigner_project_id'] ) ) {
					continue;
				}

				$project_id = $cart_item['mnumidesigner_project_id'];

				$request = new WP_REST_Request(
					'GET',
					sprintf( '/mnumidesigner/v1/projects/%s', $project_id )
				);

				$response = rest_do_request( $request );

				$server = rest_get_server();
				$data   = $server->response_to_data( $response, false );

				if ( isset( $data['code'] ) &&
					( ( 'rest_not_configured' === $data['code'] ) ||
					( 'rest_project_not_found' === $data['code'] ) )
				) {
					continue;
				}

				$price_per_page = MnumiDesigner_WC_Product::get_price_per_page( $product );
				$pages_count    = $data['number_of_pages_for_price'];

				$price = $product->get_price();
				$product->set_price( $price + $pages_count * $price_per_page );
			}
		}
	}

	/**
	 *
	 * @param array $fragments
	 * @return array
	 */
	public function add_to_cart_fragments( $fragments ) {
		if ( ! MnumiDesigner::is_configured() ) {
			return $fragments;
		}

		if ( ! is_object( WC()->cart ) ) {
			return $fragments;
		}

		$latest_cart_item = $this->get_latest_cart_item();
		$product_id       = $latest_cart_item['data']->get_id();

		$product = wc_get_product( $product_id );
		if ( ! MnumiDesigner_WC_Product::is_mnumidesigner_product( $product ) ) {
			return $fragments;
		}

		return array_merge(
			$fragments,
			array(
				'mnumidesigner' => $this->get_attach_url_for_cart_item( $latest_cart_item ),
			)
		);
	}

	/**
	 * @param array $latest_cart_item
	 * @return string|false
	 */
	private function get_attach_url_for_cart_item( $latest_cart_item ) {
		$product_or_variation    = $latest_cart_item['data'];
		$product_or_variation_id = $product_or_variation->get_id();
		$product_id              = $product_or_variation->get_id();
		$extra                   = '';

		if ( $product_or_variation instanceof WC_Product_Variation ) {
			$product_id = $product_or_variation->get_parent_id();
			$extra     .= sprintf( 'variation_id="%s"', $product_or_variation->get_id() );
		}

		$trans = MnumiDesigner_WC_Product::get_translation( $product_or_variation );
		if ( $trans ) {
			$extra .= sprintf( ' translation_id="%s"', $trans );
		}

		$cals = MnumiDesigner_WC_Product::get_event_calendars( $product_or_variation );

		if ( is_array( $cals ) && count( $cals ) > 0 ) {
			$extra .= sprintf( ' calendar_ids="%s"', implode( ',', $cals ) );
		}

		if ( ! MnumiDesigner_WC_Product::is_mnumidesigner_product( $product_or_variation ) ) {
			return false;
		}

		return do_shortcode(
			sprintf(
				'[%s product_id="%d" back_url="%s" %s]',
				MnumiDesigner_WC_Shortcode_New_Project_Url::SHORTCODE,
				$product_id,
				MnumiDesigner_BackUrl_Handler::get_permalink(
					array(
						'mnumidesigner_uri_action'       => 'attach',
						'mnumidesigner_uri_cart_item_id' => $latest_cart_item['key'],
						'mnumidesigner_uri_project_id'   => '%s',
						'mnumidesigner_uri_pages_count'  => '%s',
					)
				),
				$extra
			)
		);
	}
}

MnumiDesigner_WC_Cart::register();

