<?php
/**
 * Integrates with WooCommerce
 *
 * @package MnumiDesigner/WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * MnumiDesigner_WooCommerce class.
 */
class MnumiDesigner_WooCommerce {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( $this, 'handle_product_project_ids_query_var' ), 10, 2 );

		// Handle removal of project outside woocommerce view, detach form product.
		add_action(
			'mnumidesigner_rest_delete_project',
			function( $project, $response, $request ) {
				$post_type  = array( 'product', 'product_variation' );
				$meta_key   = 'mnumidesigner_project_ids';
				$meta_value = $project->get_project_id();

				$posts = get_posts(
					array(
						'post_type'      => $post_type,
						'meta_key'       => $meta_key,
						'posts_per_page' => -1,
					)
				);

				foreach ( $posts as $post ) {
					delete_post_meta( $post->ID, $meta_key, $meta_value );
				}
			},
			10,
			3
		);
	}

	/**
	 * Handle a custom 'mnumidesigner_project_ids' query var to get products with the 'mnumidesigner_project_ids' meta.
	 *
	 * @param array $query - Args for WP_Query.
	 * @param array $query_vars - Query vars from WC_Product_Query.
	 * @return array modified $query
	 */
	public function handle_product_project_ids_query_var( $query, $query_vars ) {
		if ( ! empty( $query_vars['mnumidesigner_project_ids'] ) ) {
			$query['meta_query'][] = array(
				'key'     => 'mnumidesigner_project_ids',
				'value'   => $query_vars['mnumidesigner_project_ids'],
				'compare' => 'IN',
			);
		}

		return $query;
	}
}

new MnumiDesigner_WooCommerce();
