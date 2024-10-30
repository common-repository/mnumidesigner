<?php
/**
 * WooCommerce Order data admin view integration for MnumiDesigner
 *
 * @package MnumiDesigner/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * MnumiDesigner_WC_Order_Data class.
 */
class MnumiDesigner_WC_Order_Data {
	const PROJECT_ID_FIELD_NAME    = 'mnumidesigner_project_id';
	const PROJECT_PAGES_FIELD_NAME = 'mnumidesigner_pages_count';

	/**
	 * Singleton instance of MnumiDesigner_WC_Order_Data.
	 *
	 * @var MnumiDesigner_WC_Order_Data
	 */
	protected static $instance = null;

	/**
	 * Registers MnumiDesigner_WC_Order_Data instance.
	 */
	public static function register() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
	}

	/**
	 * Init
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
			add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'admin_order_item_display_meta_key' ), 20, 3 );
			add_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'admin_order_item_display_meta_value' ), 20, 3 );
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

			if ( 'shop_order' === $post->post_type ) {
				wp_register_script(
					'mnumidesigner-wc-download',
					MnumiDesigner::plugin_dir_url() . '/assets/js/admin/download.js',
					array( 'jquery', 'wp-api', 'wp-backbone', 'jquery-ui-dialog' ),
					MnumiDesigner::version(),
					true
				);
				wp_localize_script(
					'mnumidesigner-wc-download',
					'MnumiDesigner',
					array(
						'namespace'   => 'mnumidesigner/v1/',
						'apiRoot'     => get_rest_url(),
						'emulateHTTP' => MnumiDesigner_Settings::instance()->get_emulate_http(),
						'api'         => array(
							'pdf' => array(
								'nonce' => wp_create_nonce( 'wp_rest' ),
							),
						),
					)
				);
				wp_enqueue_script( 'mnumidesigner-wc-download' );
			}
		}
	}

	/**
	 * Admin view display project actions for attached project
	 *
	 * @param string        $key Meta key.
	 * @param object        $meta Meta object.
	 * @param WC_Order_Item $item Current Order Item.
	 *
	 * @return string
	 */
	public function admin_order_item_display_meta_key( $key, $meta, $item ) {
		if ( self::PROJECT_ID_FIELD_NAME === $meta->key ) {
			$key = __( 'Project', 'mnumidesigner' );
		}

		return $key;
	}

	/**
	 * Admin view display project actions for attached project
	 *
	 * @param string        $value Meta value.
	 * @param object        $meta Meta object.
	 * @param WC_Order_Item $item Current Order Item.
	 */
	public function admin_order_item_display_meta_value( $value, $meta, $item ) {
		if ( self::PROJECT_ID_FIELD_NAME === $meta->key ) {
			$api     = MnumiDesigner_API::instance();
			$results = $api->get_projects(
				0,
				1,
				'updated',
				'desc',
				array(
					'ids' => $value,
				)
			);

			if ( is_wp_error( $api->get_last_error() ) ) {
				return $api->get_last_error()->get_error_message();
			} else {
				if ( $api->get_last_response_total_results() < 1 ) {
					return __( 'Not found', 'mnumidesigner' ) . ': ' . $value;
				}

				$current_url   = mnumidesigner_get_current_admin_url( 'post.php' );
				$order_item_id = $item->get_order_id();
				$extra         = '';

				$cals = MnumiDesigner_WC_Product::get_event_calendars( $item->get_product() );
				if ( is_array( $cals ) && count( $cals ) > 0 ) {
					$extra .= sprintf( ' calendar_ids="%s"', implode( ',', $cals ) );
				}

				// results always is an array with one entry...
				foreach ( $results as $project ) {
					$project_id = $project->get_project_id();
					$edit_btn   = do_shortcode(
						sprintf(
							'[%s project="%s" back_url="%s" class="button mnumidesigner-download" text="%s" %s]',
							MnumiDesigner_Shortcode_Edit_Project_Link::SHORTCODE,
							$project_id,
							$current_url,
							__( 'Edit', 'mnumidesigner' ),
							$extra
						)
					);

					$download_pdf_btn          = '';
					$download_normal_pdf_title = __( 'Download PDF', 'mnumidesigner' );
					if ( $project->get_type() === 'album-2pages' ) {
						$download_normal_pdf_title = __( 'Download PDF Album', 'mnumidesigner' );
					}
					$download_pdf_btn = sprintf(
						'<button type="button" title="%s" data-project-id="%s" data-pdf-check-url="%s" data-pdf-status-url="%s" class="button mnumidesigner-download mnumidesigner-download-project-pdf">%s</button>',
						$download_normal_pdf_title,
						$project_id,
						do_shortcode(
							sprintf(
								'[%s project="%s" barcode="%s" %s]',
								MnumiDesigner_Shortcode_Project_Pdf_Check_Url::SHORTCODE,
								$project_id,
								$order_item_id,
								$extra
							)
						),
						do_shortcode(
							sprintf(
								'[%s project="%s" barcode="%s" %s]',
								MnumiDesigner_Shortcode_Project_Pdf_Status_Url::SHORTCODE,
								$project_id,
								$order_item_id,
								$extra
							)
						),
						$download_normal_pdf_title
					);

					if ( $project->get_type() === 'album-2pages' ) {
						$download_pdf_btn .= sprintf(
							'<button type="button" title="%s" data-project-id="%s" data-pdf-check-url="%s" data-pdf-status-url="%s" class="button mnumidesigner-download mnumidesigner-download-project-pdf">%s</button>',
							__( 'Download PDF Cover', 'mnumidesigner' ),
							$project_id,
							do_shortcode(
								sprintf(
									'[%s project="%s" barcode="%s" range="1" id="%s-cover"]',
									MnumiDesigner_Shortcode_Project_Pdf_Check_Url::SHORTCODE,
									$project_id,
									$order_item_id,
									$order_item_id
								)
							),
							do_shortcode(
								sprintf(
									'[%s project="%s" barcode="%s" range="1" id="%s-cover"]',
									MnumiDesigner_Shortcode_Project_Pdf_Status_Url::SHORTCODE,
									$project_id,
									$order_item_id,
									$order_item_id
								)
							),
							__( 'Download PDF Cover', 'mnumidesigner' )
						);
						$download_pdf_btn .= sprintf(
							'<button type="button" title="%s" data-project-id="%s" data-pdf-check-url="%s" data-pdf-status-url="%s" class="button mnumidesigner-download mnumidesigner-download-project-pdf">%s</button>',
							__( 'Download PDF Book', 'mnumidesigner' ),
							$project_id,
							do_shortcode(
								sprintf(
									'[%s project="%s" barcode="%s" range="2-end" split="vertical" id="%s-book"]',
									MnumiDesigner_Shortcode_Project_Pdf_Check_Url::SHORTCODE,
									$project_id,
									$order_item_id,
									$order_item_id
								)
							),
							do_shortcode(
								sprintf(
									'[%s project="%s" barcode="%s" range="2-end" split="vertical" id="%s-book"]',
									MnumiDesigner_Shortcode_Project_Pdf_Status_Url::SHORTCODE,
									$project_id,
									$order_item_id,
									$order_item_id
								)
							),
							__( 'Download PDF Book', 'mnumidesigner' )
						);
					}
					$value  = '';
					$value .= $edit_btn;
					$value .= $download_pdf_btn;
					break;
				}
			}
		}

		return $value;
	}
}

MnumiDesigner_WC_Order_Data::register();

