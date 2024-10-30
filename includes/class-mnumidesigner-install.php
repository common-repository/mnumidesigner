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
 * MnumiDesigner_Install Class
 *
 * @package    MnumiDesigner
 */
class MnumiDesigner_Install {
	/**
	 * Supported default roles
	 *
	 * @var array
	 */
	private static $roles = array(
		'administrator',
		'shop_manager',
	);

	/**
	 * Install MnumiDesigner data
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		self::create_caps();
	}

	/**
	 * Create MnumiDesigner roles/capabilities.
	 */
	public static function create_caps() {
		$wp_roles = wp_roles();

		$capabilities = self::get_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				foreach ( self::$roles as $role_name ) {
					$role = $wp_roles->get_role( $role_name );
					if ( ! $role ) {
						continue;
					}

					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Get capabilities for MnumiDesigner.
	 *
	 * @return array
	 */
	private static function get_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_mnumidesigner',
		);

		$capability_types = array( 'project', 'template', 'translation', 'calendar' );

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				"create_mnumidesigner_{$capability_type}",
				"edit_mnumidesigner_{$capability_type}",
				"delete_mnumidesigner_{$capability_type}",
				"duplicate_mnumidesigner_{$capability_type}",
				"attach_mnumidesigner_{$capability_type}",

				"view_mnumidesigner_{$capability_type}",
			);
		}

		return $capabilities;
	}

	/**
	 * Remove MnumiDesigner roles/capabilities.
	 */
	public static function remove_caps() {
		$wp_roles = wp_roles();

		$capabilities = self::get_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				foreach ( self::$roles as $role_name ) {
					$role = $wp_roles->get_role( $role_name );
					if ( ! $role ) {
						continue;
					}

					$role->remove_cap( $cap );
				}
			}
		}
	}
}
