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
 * MnumiDesigner_Shortcode_Project_Pdf_Check_Url class.
 */
abstract class MnumiDesigner_Shortcode_Project_Pdf
	extends MnumiDesigner_Shortcode_Project {

	/**
	 * Gets supported shortcode attributes and their defaults
	 *
	 * @return array<string,string>
	 */
	public function get_defaults() {
		return array(
			'project'        => '',
			'barcode'        => 123456789,
			'translation_id' => '',
			'calendar_ids'   => '',
			'id'             => '', // e.g.: Customer Order ID
			'range'          => '', // only valid for albums
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
			$this->map_attr_project( $atts ),
			$this->map_attr_barcode( $atts ),
			$this->map_attr_calendar_ids( $atts ),
			$this->map_attr_translation_id( $atts ),
			$this->map_attr_id( $atts ),
			$this->map_attr_range( $atts )
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
	 * Maps shortcode `barcode` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_barcode( $atts ) {
		return array(
			'barcode' => $atts['barcode'],
		);
	}

	/**
	 * Maps shortcode `calendar_ids` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_calendar_ids( $atts ) {
		$result    = array();
		$calendars = array();
		if ( $atts['calendar_ids'] ) {
			$calendars = explode( ',', $atts['calendar_ids'] );
		}

		foreach ( $calendars as $calendar_id ) {
			$file = mnumidesigner_get_calendar_file( $calendar_id );
			if ( $file && mnumidesigner_is_translation_filename_valid( $file ) ) {
				$result['calendarUrls'][] = mnumidesigner_get_calendar_file_url( $file );
			}
		}

		return $result;
	}

	/**
	 * Maps shortcode `translation_id` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_translation_id( $atts ) {
		$result = array();
		$trans  = mnumidesigner_get_translation_file( $atts['translation_id'] );

		if ( $trans && mnumidesigner_is_translation_filename_valid( $trans ) ) {
			$result['translationUrl'] = mnumidesigner_get_translation_file_url( $trans );
		}

		return $result;
	}

	/**
	 * Maps shortcode `id` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_id( $atts ) {
		if ( empty( $atts['id'] ) ) {
			return array();
		}

		return array(
			'id' => $atts['id'],
		);
	}

	/**
	 * Maps shortcode `range` attribute to the appropriate API one
	 *
	 * It is only valid for album projects.
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_range( $atts ) {
		if ( empty( $atts['range'] ) ) {
			return array();
		}

		return array(
			'range' => $atts['range'],
		);
	}

	/**
	 * Maps shortcode `split` attribute to the appropriate API one
	 *
	 * It is only valid for album projects.
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_split( $atts ) {
		if ( empty( $atts['split'] ) ) {
			return array();
		}

		return array(
			'split' => $atts['split'],
		);
	}
}
