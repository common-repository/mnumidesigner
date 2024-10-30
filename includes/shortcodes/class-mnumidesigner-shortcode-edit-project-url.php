<?php
/**
 * Used for generating publicly accessible urls allowing editing projects
 *
 * @package MnumiDesigner/Shortcodes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Edit Project Url Shortcode
 *
 * Generates url to MnumiDesigner which allows editing project by its ID.
 */
class MnumiDesigner_Shortcode_Edit_Project_Url
	extends MnumiDesigner_Shortcode_Project {
	const SHORTCODE = 'mnumidesigner_edit_project_url';

	/**
	 * Gets supported shortcode attributes and their defaults
	 *
	 * @return array<string,string>
	 */
	public function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'project'   => '',
				'templates' => '',
				'display'   => '',
			)
		);
	}

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
				'action' => 'editE',
			),
			parent::map_attrs( $atts ),
			$this->map_attr_project( $atts ),
			$this->map_attr_templates( $atts ),
			$this->map_attr_display( $atts )
		);
	}

	/**
	 * Maps shortcode `project` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_project( $atts ) {
		return array(
			'orderId' => $atts['project'],
		);
	}

	/**
	 * Maps shortcode `templates` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_templates( $atts ) {
		return array(
			'wizards' => $atts['templates'],
		);
	}

	/**
	 * Maps shortcode `display` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_display( $atts ) {
		if ( empty( $atts['display'] ) ) {
			return array();
		}

		return array(
			'display' => $atts['display'],
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
