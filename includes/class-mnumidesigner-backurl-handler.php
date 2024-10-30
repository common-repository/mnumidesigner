<?php
/**
 * Used for handling returning from MnumiDesigner.
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'MnumiDesigner_BackUrl_Handler' ) ) :
	/**
	 * MnumiDesigner_BackUrl_Handler class.
	 */
	class MnumiDesigner_BackUrl_Handler {
		const URI_ATTACH_ACTION = 'attach';

		/**
		 * Class constructor.
		 */
		public function __construct() {
			add_action( 'init', array( 'MnumiDesigner_BackUrl_Handler', 'permastructs' ) );
			add_action( 'template_redirect', array( $this, 'permastruct_handler' ) );
		}

		/**
		 * Gets permalink
		 *
		 * @param array $query_args Additional query arguments.
		 * @return string
		 */
		public static function get_permalink( $query_args ) {
			$permalink = get_home_url();

			return add_query_arg( $query_args, $permalink );
		}

		/**
		 * Creates permalink entries on activation
		 */
		public static function activation() {
			self::permastructs();
			flush_rewrite_rules();
		}

		/**
		 * Cleans permalink structure on deactivation
		 */
		public static function deactivation() {
			flush_rewrite_rules();
		}

		/**
		 * Creates permalink entries on activation
		 */
		public static function permastructs() {
			add_rewrite_tag( '%mnumidesigner_uri_action%', '([^/]+)' );
			add_rewrite_tag( '%mnumidesigner_uri_cart_item_id%', '([^/]+)' );
			add_rewrite_tag( '%mnumidesigner_uri_project_id%', '([^/]+)' );
			add_rewrite_tag( '%mnumidesigner_uri_pages_count%', '([^/]+)' );
			add_permastruct( 'mnumidesigner_uri_back_url_handler', '/mnumidesigner/%mnumidesigner_uri_action%/%mnumidesigner_uri_project_id%/%mnumidesigner_uri_pages_count%/%mnumidesigner_uri_cart_item_id%' );
		}

		/**
		 * Handler for created permalink entries
		 */
		public function permastruct_handler() {
			// mnumidesigner_uri_cart_item_id is needed to uniquely identify cart item to attach project to.
			$action       = get_query_var( 'mnumidesigner_uri_action' );
			$project_id   = get_query_var( 'mnumidesigner_uri_project_id' );
			$pages_count  = get_query_var( 'mnumidesigner_uri_pages_count' );
			$cart_item_id = get_query_var( 'mnumidesigner_uri_cart_item_id' );

			if ( ! $action ) {
				return;
			}
			if ( ! $project_id ) {
				return;
			}
			if ( ! $pages_count ) {
				return;
			}
			if ( ! $cart_item_id ) {
				return;
			}

			$project_id = wc_clean( $project_id );

			switch ( $action ) {
				case self::URI_ATTACH_ACTION:
					// e.g.: attach project & redirect to cart.
					mnumidesigner_cart_item_set( $cart_item_id, MnumiDesigner_WC_Cart::PROJECT_ID_FIELD_NAME, $project_id );

					wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
					break;
				default:
					return;
			}
		}
	}

endif;

new MnumiDesigner_BackUrl_Handler();

