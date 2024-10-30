<?php
/**
 * Fired during plugin deactivation
 *
 * @package    MnumiDesigner
 * @subpackage MnumiDesigner/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation.
 *
 * @package    MnumiDesigner
 * @subpackage MnumiDesigner/includes
 */
class MnumiDesigner_Deactivator {
	/**
	 * Invoke all deactivation handlers.
	 */
	public static function deactivate() {
		MnumiDesigner_BackUrl_Handler::deactivation();
	}
}
