<?php
/**
 * Helper Functions for WordPress
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'mnumidesigner_get_current_admin_url' ) ) {
	/**
	 * Returns current administration url.
	 *
	 * @param string $path Path.
	 * @return url to current /wp-admin page
	 */
	function mnumidesigner_get_current_admin_url( $path ) {
		$query = '';
		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$query = sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
		}
		return add_query_arg( $query, '', admin_url( $path ) );
	}
}

/**
 * Checks if cart has item with given meta key.
 *
 * @param int    $item_id Cart item Id.
 * @param string $meta_key Meta key.
 * @return bool
 */
function mnumidesigner_cart_item_has( $item_id, $meta_key ) {
	return mnumidesigner_cart_item_get( $item_id, $meta_key, false ) !== false;
}

/**
 * Gets cart item with given meta key or default.
 *
 * @param int    $item_id Cart item Id.
 * @param string $meta_key Meta key.
 * @param string $default Default value if not found.
 * @return mixed
 */
function mnumidesigner_cart_item_get( $item_id, $meta_key, $default ) {
	$cart = WC()->cart->cart_contents;
	foreach ( $cart as $cart_item_id => $cart_item ) {
		if ( $cart_item_id === $item_id ) {
			if ( isset( $cart_item[ $meta_key ] ) ) {
				return $cart_item[ $meta_key ];
			}
			break;
		}
	}
	return $default;
}

/**
 * Sets value for cart item meta with given meta key.
 *
 * @param int    $item_id Cart item Id.
 * @param string $meta_key Meta key.
 * @param string $meta_value Meta value.
 * @return mixed
 */
function mnumidesigner_cart_item_set( $item_id, $meta_key, $meta_value ) {
	$cart = WC()->cart->cart_contents;

	foreach ( $cart as $cart_item_id => $cart_item ) {
		if ( $cart_item_id === $item_id ) {
			$cart_item[ $meta_key ]                    = $meta_value;
			WC()->cart->cart_contents[ $cart_item_id ] = $cart_item;
			WC()->cart->set_session();
			break;
		}
	}
}

/**
 * Deletes cart item meta with given meta key.
 * .
 *
 * @param int    $item_id Cart item Id.
 * @param string $meta_key Meta key.
 */
function mnumidesigner_cart_item_del( $item_id, $meta_key ) {
	if ( mnumidesigner_cart_item_has( $item_id, $meta_key ) ) {
		$cart = WC()->cart->cart_contents;

		foreach ( $cart as $cart_item_id => $cart_item ) {
			if ( $cart_item_id === $item_id ) {
				unset( $cart_item[ $meta_key ] );
				WC()->cart->cart_contents[ $cart_item_id ] = $cart_item;
				WC()->cart->set_session();
				break;
			}
		}
	}
}

/**
 * Checks if user has specified project in cart
 *
 * @param string $project_id Project ID.
 * @return bool
 */
function mnumidesigner_user_has_project_in_cart( $project_id ) {
	$cart = WC()->cart->cart_contents;

	$cart_field = MnumiDesigner_WC_Cart::PROJECT_ID_FIELD_NAME;

	foreach ( $cart as $cart_item_id => $cart_item ) {
		if ( mnumidesigner_cart_item_has( $item_id, $cart_field ) ) {
			$user_project_id = mnumidesigner_cart_item_get( $item_id, $cart_field, false );

			if ( $user_project_id === $project_id ) {
				return true;
			}
		}
	}

	return false;
}

if ( ! function_exists( 'mnumidesigner_get_translation_file_url' ) ) {
	/**
	 * Returns url to translation file.
	 *
	 * @param SplFileInfo $file Translation file.
	 * @return string|false
	 */
	function mnumidesigner_get_translation_file_url( SplFileInfo $file ) {
		if ( ! mnumidesigner_is_translation_filename_valid( $file ) ) {
			return false;
		}
		return content_url( 'uploads/mnumidesigner-translations/' . $file->getFilename() );
	}
}

if ( ! function_exists( 'mnumidesigner_get_translation_file' ) ) {
	/**
	 * Returns translation file for the given id.
	 *
	 * @param string $id Translation id.
	 *
	 * @return SplFileInfo|false
	 */
	function mnumidesigner_get_translation_file( $id ) {
		if ( ! $id ) {
			return false;
		}

		list ($name, $domain, $locale) = explode( '.', $id );
		$domain                        = 'editor';

		$dir = MnumiDesigner::plugin_translations_dir();

		return new SplFileInfo(
			sprintf(
				'%s%s.%s.%s.json',
				trailingslashit( $dir ),
				$name,
				$domain,
				$locale
			)
		);
	}
}

if ( ! function_exists( 'mnumidesigner_get_translation_file_meta' ) ) {
	/**
	 * Returns translation file meta data
	 *
	 * @param SplFileInfo $file Translation file.
	 * @return array
	 */
	function mnumidesigner_get_translation_file_meta( SplFileInfo $file ) {
		$basename = $file->getBasename( '.json' );

		$meta = array();

		preg_match( '/(?P<name>[\w-]+)\.(?P<domain>\w+)\.(?P<locale>\w+)/', $basename, $meta );

		$meta['modified'] = date_i18n(
			sprintf(
				'%s %s',
				get_option( 'date_format' ),
				get_option( 'time_format' )
			),
			$file->getMTime()
		);

		$locale           = explode( '_', $meta['locale'] );
		$meta['fallback'] = $locale[0];

		return $meta;
	}
}

if ( ! function_exists( 'mnumidesigner_is_translation_filename_valid' ) ) {
	/**
	 * Checks if translation file file name is valid.
	 *
	 * @param SplFileInfo $file Translation file.
	 * @return boolean
	 */
	function mnumidesigner_is_translation_filename_valid( SplFileInfo $file ) {
		return preg_match( '/(?P<name>[\w-]+)\.(?P<domain>\w+)\.(?P<locale>\w+)/', $file->getFilename() ) === 1;
	}
}

if ( ! function_exists( 'mnumidesigner_get_calendar_file_url' ) ) {
	/**
	 * Returns url to calendar file.
	 *
	 * @param SplFileInfo $file Calendar file.
	 * @return string|false
	 */
	function mnumidesigner_get_calendar_file_url( SplFileInfo $file ) {
		if ( ! mnumidesigner_is_calendar_filename_valid( $file ) ) {
			return false;
		}
		return content_url( 'uploads/mnumidesigner-calendars/' . $file->getFilename() );
	}
}

if ( ! function_exists( 'mnumidesigner_get_calendar_file' ) ) {
	/**
	 * Returns calendar file for the given id.
	 *
	 * @param string $id Calendar id.
	 *
	 * @return SplFileInfo|false
	 */
	function mnumidesigner_get_calendar_file( $id ) {
		if ( ! $id ) {
			return false;
		}

		list ($name, $type, $locale) = explode( '.', $id );

		$dir = MnumiDesigner::plugin_calendars_dir();

		return new SplFileInfo(
			sprintf(
				'%s%s.%s.%s.json',
				trailingslashit( $dir ),
				$name,
				$type,
				$locale
			)
		);
	}
}

if ( ! function_exists( 'mnumidesigner_get_calendar_file_meta' ) ) {
	/**
	 * Returns calendar file meta data
	 *
	 * @param SplFileInfo $file Calendar file.
	 * @return array
	 */
	function mnumidesigner_get_calendar_file_meta( SplFileInfo $file ) {
		$basename = $file->getBasename( '.json' );

		$meta = array();

		preg_match( '/(?P<name>[\w-]+)\.(?P<type>[\w-]+)\.(?P<locale>[\w_]+)/', $basename, $meta );

		$meta['modified'] = date_i18n(
			sprintf(
				'%s %s',
				get_option( 'date_format' ),
				get_option( 'time_format' )
			),
			$file->getMTime()
		);

		return $meta;
	}
}

if ( ! function_exists( 'mnumidesigner_is_calendar_filename_valid' ) ) {
	/**
	 * Checks if calendar file name is valid.
	 *
	 * @param SplFileInfo $file Calendar file.
	 * @return boolean
	 */
	function mnumidesigner_is_calendar_filename_valid( SplFileInfo $file ) {
		return preg_match( '/(?P<name>[\w-]+)\.(?P<type>[\w-]+)\.(?P<locale>[\w_]+)/', $file->getFilename() ) === 1;
	}
}
