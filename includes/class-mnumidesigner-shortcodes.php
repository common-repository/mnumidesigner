<?php
/**
 * Shortcodes
 *
 * Used for initializing supported shortcodes
 *
 * @package MnumiDesigner/Shortcodes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes class.
 */
class MnumiDesigner_Shortcodes {
	/**
	 * Include required shortcode files used.
	 */
	private static function includes() {
		require_once 'shortcodes/class-mnumidesigner-shortcode-project.php';
		require_once 'shortcodes/class-mnumidesigner-shortcode-project-pdf.php';
		require_once 'shortcodes/class-mnumidesigner-shortcode-edit-project-url.php';
		require_once 'shortcodes/class-mnumidesigner-shortcode-edit-project-link.php';
		require_once 'shortcodes/class-mnumidesigner-shortcode-project-pdf-check-url.php';
		require_once 'shortcodes/class-mnumidesigner-shortcode-project-pdf-status-url.php';

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			require_once 'woocommerce/shortcodes/class-mnumidesigner-wc-shortcode-project.php';
			require_once 'woocommerce/shortcodes/class-mnumidesigner-wc-shortcode-new-project-url.php';
			require_once 'woocommerce/shortcodes/class-mnumidesigner-wc-shortcode-new-project-link.php';
		}
	}

	/**
	 * Initializes shortcodes.
	 */
	public static function init() {
		self::includes();

		$shortcodes = array(
			MnumiDesigner_Shortcode_Edit_Project_Url::class => 'edit_project_url',
			MnumiDesigner_Shortcode_Edit_Project_Link::class => 'edit_project_link',
			MnumiDesigner_Shortcode_Project_Pdf_Check_Url::class => 'pdf_project_check_url',
			MnumiDesigner_Shortcode_Project_Pdf_Status_Url::class => 'pdf_project_status_url',
		);

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$shortcodes = array_merge(
				$shortcodes,
				array(
					MnumiDesigner_WC_Shortcode_New_Project_Url::class => 'wc_new_project_url',
					MnumiDesigner_WC_Shortcode_New_Project_Link::class => 'wc_new_project_link',
				)
			);
		}

		foreach ( $shortcodes as $shortcode_class => $function ) {
			add_shortcode(
				constant( $shortcode_class . '::SHORTCODE' ),
				__CLASS__ . '::' . $function
			);
		}
	}

	/**
	 * Wrapper for defined shortcodes.
	 *
	 * @param callable $function Shortcode callable to invoke.
	 * @param array    $atts Shortcode aarguments.
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array()
	) {
		ob_start();
		echo call_user_func( $function, $atts );
		return ob_get_clean();
	}

	/**
	 * Outputs Edit project url shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function edit_project_url( $atts ) {
		return self::shortcode_wrapper( array( 'MnumiDesigner_Shortcode_Edit_Project_Url', 'output' ), $atts );
	}

	/**
	 * Outputs Edit project link shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function edit_project_link( $atts ) {
		return self::shortcode_wrapper( array( 'MnumiDesigner_Shortcode_Edit_Project_Link', 'output' ), $atts );
	}

	/**
	 * Outputs PDF check project url shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function pdf_project_check_url( $atts ) {
		return self::shortcode_wrapper( array( 'MnumiDesigner_Shortcode_Project_Pdf_Check_Url', 'output' ), $atts );
	}

	/**
	 * Outputs PDF status project url shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function pdf_project_status_url( $atts ) {
		return self::shortcode_wrapper( array( 'MnumiDesigner_Shortcode_Project_Pdf_Status_Url', 'output' ), $atts );
	}

	/**
	 * Outputs WooCommerce new project url shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function wc_new_project_url( $atts ) {
		return self::shortcode_wrapper( array( 'MnumiDesigner_WC_Shortcode_New_Project_Url', 'output' ), $atts );
	}

	/**
	 * Outputs WooCommerce new project link shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function wc_new_project_link( $atts ) {
		return self::shortcode_wrapper( array( 'MnumiDesigner_WC_Shortcode_New_Project_Link', 'output' ), $atts );
	}
}

