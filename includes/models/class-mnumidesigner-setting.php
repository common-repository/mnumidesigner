<?php
/**
 * Model class representing MnumiDesigner API settings.
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MnumiDesigner_Setting' ) ) :

	/**
	 * MnumiDesigner model class for remote settings.
	 *
	 * @class   MnumiDesigner_Setting
	 * @package MnumiDesigner
	 */
	class MnumiDesigner_Setting {
		/**
		 * Api Key ID
		 *
		 * @var string
		 */
		private $id;

		/**
		 * Api Key
		 *
		 * @var string
		 */
		private $key;

		/**
		 * Domain
		 *
		 * @var string
		 */
		private $domain;

		/**
		 * Available project types for current Api key
		 *
		 * @var array
		 */
		private $available_project_types = array();

		/**
		 * Class constructor.
		 *
		 * @param array $args Initialization arguments.
		 */
		public function __construct( array $args ) {
			$this->id  = $args['id'];
			$this->key = $args['name'];
			if ( isset( $args['domain'] ) ) {
				$this->domain = $args['domain'];
			}
			if ( isset( $args['availableProjectTypes'] ) ) {
				$this->available_project_types = $args['availableProjectTypes'];
			}
		}

		/**
		 * Get Api key Id
		 *
		 * @return string
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Get Api key
		 *
		 * @return string
		 */
		public function get_key() {
			return $this->key;
		}

		/**
		 * Get Domain
		 *
		 * @return string
		 */
		public function get_domain() {
			return $this->domain;
		}

		/**
		 * Get available project types
		 *
		 * @return array
		 */
		public function get_available_project_types() {
			return $this->available_project_types;
		}
	}

endif;
