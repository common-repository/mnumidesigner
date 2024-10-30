<?php
/**
 * Base class for MnumiDesigner WooCommerce Shortcodes
 *
 * @package MnumiDesigner/Shortcodes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode class.
 *
 * Generates url to MnumiDesigner which allows creating project by used
 * product & variation.
 */
abstract class MnumiDesigner_WC_Shortcode_Project
	extends MnumiDesigner_Shortcode_Project {

	/**
	 * Loaded product
	 *
	 * @var WC_Product|null
	 */
	protected $product = null;

	/**
	 * Variation ID if related
	 *
	 * @var integer|null
	 */
	protected $variation = null;

	/**
	 * Gets supported shortcode attributes and their defaults
	 *
	 * @return array<string,string>
	 */
	public function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'product_id'   => null,
				'variation_id' => '',
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
			parent::map_attrs( $atts ),
			$this->map_attr_product_id( $atts ),
			$this->map_attr_variation_id( $atts )
		);
	}

	/**
	 * Maps shortcode `product_id` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_product_id( $atts ) {
		$result                = array();
		$this->product         = wc_get_product( $atts['product_id'] );
		$result['productName'] = $this->product->get_slug();

		if ( empty( $atts['back_url'] ) ) {
			$result['back_url'] = $this->product->get_permalink();
		}

		return $result;
	}

	/**
	 * Maps shortcode `variation_id` attribute to the appropriate API one
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 *
	 * @return array<string,string>
	 */
	protected function map_attr_variation_id( $atts ) {
		if ( ! empty( $atts['variation_id'] ) ) {
			$this->variation = $atts['variation_id'];
		}

		return array();
	}
}

