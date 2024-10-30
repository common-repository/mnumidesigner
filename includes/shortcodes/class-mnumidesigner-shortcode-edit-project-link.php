<?php
/**
 * Used for generating publicly accessible urls allowing editing projects
 *
 * Used on the product page, the project shortcode displays "Edit project"
 * link allowing to edit project by its ID.
 *
 * @package MnumiDesigner/Shortcodes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Edit Project Link Shortcode
 *
 * Generates HTML link to MnumiDesigner which allows editing project by its ID.
 *
 * Wrapper over MnumiDesigner_Shortcode_Edit_Project_Url
 */
class MnumiDesigner_Shortcode_Edit_Project_Link
	extends MnumiDesigner_Shortcode_Edit_Project_Url {
	const SHORTCODE = 'mnumidesigner_edit_project_link';

	/**
	 * Gets supported shortcode attributes and their defaults
	 *
	 * @return array<string,string>
	 */
	public function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'text'         => __( 'Edit project', 'mnumidesigner' ),
				'class'        => '',
				'variation_id' => '',
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
