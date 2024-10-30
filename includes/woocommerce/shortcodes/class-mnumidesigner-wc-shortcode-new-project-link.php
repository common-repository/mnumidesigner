<?php
/**
 * Add Project Shortcode
 *
 * Used on the product page, the project shortcode displays "Add project"
 * link allowing to attach to order customer's project.
 *
 * @package MnumiDesigner/Shortcodes
 */

defined( 'ABSPATH' ) || exit;

/**
 * New Project Link Shortcode
 *
 * Generates HTML link to MnumiDesigner which allows creating project by used
 * product & variation.
 *
 * Wrapper over MnumiDesigner_Shortcode_New_Project_Url
 */
class MnumiDesigner_WC_Shortcode_New_Project_Link
	extends MnumiDesigner_WC_Shortcode_New_Project_Url {
	const SHORTCODE = 'mnumidesigner_wc_new_project_link';

	/**
	 * Gets supported shortcode attributes and their defaults
	 *
	 * @return array<string,string>
	 */
	public function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'text'  => __( 'Add project', 'mnumidesigner' ),
				'class' => '',
			)
		);
	}

	/**
	 * Output shortcode
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function output( $atts ) {
		$instance = new self();
		$atts     = shortcode_atts( $instance->get_defaults(), $atts );

		return sprintf(
			'<a href="%s" class="%s">%s</a>',
			parent::output( $atts ),
			$atts['class'],
			$atts['text']
		);
	}
}

