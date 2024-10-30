<?php
/**
 * Used for generating publicly accessible urls allowing creating new projects
 *
 * @package MnumiDesigner/Shortcodes
 */

defined( 'ABSPATH' ) || exit;

/**
 * New Project Url Shortcode
 *
 * Generates url to MnumiDesigner which allows creating project by used
 * product & variation.
 */
class MnumiDesigner_WC_Shortcode_New_Project_Url
	extends MnumiDesigner_WC_Shortcode_Project {

	const SHORTCODE = 'mnumidesigner_wc_new_project_url';

	/**
	 * Gets supported shortcode attributes and their defaults
	 *
	 * @return array<string,string>
	 */
	public function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'wizards'      => '',
				'count'        => 1,
				'count_change' => true,
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
				'action' => 'initOrder',
			),
			parent::map_attrs( $atts ),
			$this->map_attr_project_ids( $atts ),
			$this->map_attr_count( $atts ),
			$this->map_attr_count_change( $atts )
		);
	}

	/**
	 * Maps shortcode `count` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_count( $atts ) {
		$result   = array();
		$owner_id = $this->product->get_id();
		if ( $this->variation ) {
			$owner_id = $this->variation;
		}

		$count = get_post_meta( $owner_id, 'mnumidesigner_project_pages', true );

		if ( $count ) {
			$result['count'] = $count;
		}
		return $result;
	}

	/**
	 * Maps shortcode `count_change` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_count_change( $atts ) {
		$result   = array();
		$owner_id = $this->product->get_id();
		if ( $this->variation ) {
			$owner_id = $this->variation;
		}

		$result['countChange'] = $atts['count_change'];

		$count_change = get_post_meta( $owner_id, 'mnumidesigner_count_change', true );
		if ( $count_change !== false ) {
			$result['countChange'] = intval( $count_change );
		}
		return $result;
	}

	/**
	 * Maps shortcode `project_ids` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_project_ids( $atts ) {
		$result   = array();
		$owner_id = $this->product->get_id();
		if ( $this->variation ) {
			$owner_id = $this->variation;
		}

		$result['wizards'] = implode(
			',',
			get_post_meta( $owner_id, 'mnumidesigner_project_ids' )
		);

		return $result;
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
