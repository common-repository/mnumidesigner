<?php
/**
 * Base class for MnumiDesigner Shortcodes
 *
 * @package MnumiDesigner/Shortcodes
 */

defined( 'ABSPATH' ) || exit;

/**
 * MnumiDesigner_Shortcode_Project class.
 */
abstract class MnumiDesigner_Shortcode_Project {
	/**
	 * Gets supported shortcode attributes and their defaults.
	 *
	 * @return array<string,string>
	 */
	public function get_defaults() {
		return array(
			'back_url'       => '',
			'ping_url'       => '',
			'translation_id' => '',
			'calendar_ids'   => '',
		);
	}

	/**
	 * Map shortcode attributes to the request data.
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	public function map_attrs( $atts ) {
		return array_merge(
			$this->map_attr_back_url( $atts ),
			$this->map_attr_ping_url( $atts ),
			$this->map_attr_calendar_ids( $atts ),
			$this->map_attr_translation_id( $atts )
		);
	}


	/**
	 * Maps shortcode `back_url` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_back_url( $atts ) {
		return array(
			'backUrl' => $atts['back_url'],
		);
	}

	/**
	 * Maps shortcode `ping_url` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_ping_url( $atts ) {
		$result = array();
		if ( $atts['ping_url'] ) {
			$result['pingUrl'] = $atts['ping_url'];
		}
		return $result;
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
	 * Output shortcode
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return string
	 */
	abstract public static function output( $atts );
}
