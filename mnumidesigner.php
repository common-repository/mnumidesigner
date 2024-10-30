<?php
/**
 * Plugin Name: MnumiDesigner
 * Plugin URI: https://mnumidesigner.com
 * Description: MnumiDesigner is an online application for photo products. You can design photo calendars, photo albums or any other photo products sold in WooCommerce.
 * Author: Mnumi
 * Version: 0.8
 * WC requires at least: 3.0.0
 * Text Domain: mnumidesigner
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'MnumiDesigner' ) ) {
	include_once dirname( __FILE__ ) . '/class-mnumidesigner.php';
}

if ( ! defined( 'MNUMIDESIGNER_PLUGIN_FILE' ) ) {
	define( 'MNUMIDESIGNER_PLUGIN_FILE', __FILE__ );
}

MnumiDesigner::instance();

