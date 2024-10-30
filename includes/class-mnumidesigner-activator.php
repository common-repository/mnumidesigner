<?php
/**
 * Fired during plugin activation
 *
 * @package    MnumiDesigner
 * @subpackage MnumiDesigner/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * @package    MnumiDesigner
 * @subpackage MnumiDesigner/includes
 */
class MnumiDesigner_Activator {
	/**
	 * Invoke all activation handlers.
	 */
	public static function activate() {
		MnumiDesigner_Install::install();

		MnumiDesigner_BackUrl_Handler::activation();
	}
}
