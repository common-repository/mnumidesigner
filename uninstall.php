<?php
/**
 * MnumiDesigner Uninstall
 *
 * Uninstalling MnumiDesigner deletes user roles/capabilities
 *
 * @package MnumiDesigner\Uninstaller
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once dirname( __FILE__ ) . '/includes/class-mnumidesigner-install.php';

MnumiDesigner_Install::remove_roles();
