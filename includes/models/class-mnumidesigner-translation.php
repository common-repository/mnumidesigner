<?php
/**
 * MnumiDesigner Translation model class.
 *
 * @package MnumiDesigner/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MnumiDesigner_Translation' ) ) :
	/**
	 * MnumiDesigner model class for remote translation data.
	 *
	 * @class   MnumiDesigner_Translation
	 * @package MnumiDesigner/Models
	 */
	class MnumiDesigner_Translation {
		/**
		 * MnumiDesigner locale for translation.
		 *
		 * @var string
		 */
		private $locale;

		/**
		 * MnumiDesigner locale domain for translation.
		 *
		 * @var string
		 */
		private $domain = 'editor';

		/**
		 * MnumiDesigner fallback locale for translation.
		 *
		 * @var string
		 */
		private $fallback;

		/**
		 * MnumiDesigner translations for current locale.
		 *
		 * @var array
		 */
		private $translations;

		/**
		 * Class constructor.
		 *
		 * @param array $args Translation parameters.
		 */
		public function __construct( array $args ) {
			$this->locale       = $args['locale'];
			$this->fallback     = $args['fallback'];
			$this->translations = $args['translations'];
		}

		/**
		 * Gets MnumiDesigner translation domain.
		 *
		 * @return string
		 */
		public function get_domain() {
			return $this->domain;
		}

		/**
		 * Gets MnumiDesigner translation locale.
		 *
		 * @return string
		 */
		public function get_locale() {
			return $this->locale;
		}

		/**
		 * Gets MnumiDesigner translation fallback locale.
		 *
		 * @return string
		 */
		public function get_fallback() {
			return $this->fallback;
		}

		/**
		 * Gets MnumiDesigner translations.
		 *
		 * @return array
		 */
		public function get_translations() {
			return $this->translations;
		}

		/**
		 * Gets MnumiDesigner translation entries.
		 *
		 * @return array
		 */
		public function get_translation_entries() {
			$locale       = $this->get_locale();
			$domain       = $this->get_domain();
			$translations = $this->get_translations();
			$entries      = $translations[ $locale ][ $domain ];

			return $entries;
		}
	}

endif;
