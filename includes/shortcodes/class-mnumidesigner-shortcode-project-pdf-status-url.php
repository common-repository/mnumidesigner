<?php
/**
 * Check Project PDF Url Shortcode
 *
 * Generates url to MnumiDesigner which allows attaching project to customer's order.
 *
 * @package MnumiDesigner/Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * MnumiDesigner_Shortcode_Project_Pdf_Status_Url class.
 */
class MnumiDesigner_Shortcode_Project_Pdf_Status_Url
	extends MnumiDesigner_Shortcode_Project_Pdf {

	const SHORTCODE = 'mnumidesigner_project_pdf_status_url';

	/**
	 * Maps shortcode attributes to the appropriate API ones
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	public function map_attrs( $atts ) {
		return array_merge(
			array(
				'action' => 'pdfstatus',
			),
			parent::map_attrs( $atts )
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

		return MnumiDesigner_API_Client::get_url(
			$instance->map_attrs(
				shortcode_atts( $instance->get_defaults(), $atts )
			)
		);
	}
}
