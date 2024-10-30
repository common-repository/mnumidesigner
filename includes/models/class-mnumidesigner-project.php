<?php
/**
 * Model class representing MnumiDesigner project.
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MnumiDesigner_Project' ) ) :
	/**
	 * Designer Project model class.
	 *
	 * @package MnumiDesigner/Models
	 */
	class MnumiDesigner_Project {
		/**
		 * Project Id
		 *
		 * @var string
		 */
		private $project_id;

		/**
		 * Project type
		 *
		 * @var string
		 */
		private $type;

		/**
		 * Project type label
		 *
		 * @var string
		 */
		private $type_label;

		/**
		 * Project label
		 *
		 * @var string
		 */
		private $project_label;

		/**
		 * Date project was created
		 *
		 * @var \DateTime
		 */
		private $created_at;

		/**
		 * Date project was last updated
		 *
		 * @var \DateTime
		 */
		private $updated_at;

		/**
		 * Number of pages in project
		 *
		 * @var int
		 */
		private $number_of_pages;

		/**
		 * Project template Id
		 *
		 * @var string
		 */
		private $source_project_id;

		/**
		 * Is project cloned from template?
		 *
		 * @var bool
		 */
		private $cloned;

		/**
		 * Is project read only?
		 *
		 * @var bool
		 */
		private $readonly;

		/**
		 * Is project requested to delete?
		 *
		 * @var bool
		 */
		private $pending_removal;

		/**
		 * Can project be removed?
		 *
		 * @var bool
		 */
		private $can_remove;

		/**
		 * Class constructor.
		 *
		 * @param array $args Initialization arguments.
		 */
		public function __construct( array $args ) {
			$this->project_id    = $args['order_id'];
			$this->type          = $args['type'];
			$this->type_label    = $args['typeLabel'];
			$this->project_label = isset( $args['label'] ) ? $args['label'] : null;
			$this->created_at    = new \DateTime( $args['created'] );
			$this->updated_at    = new \DateTime( $args['updated'] );

			$this->number_of_pages = (int) $args['number_of_pages'];

			$this->cloned = (bool) $args['is_cloned'];
			if ( $this->cloned ) {
				$this->source_project_id = $args['source_order_name'];
			}
			$readonly = false;
			if ( isset( $args['readonly'] ) ) {
				$readonly = (bool) $args['readonly'];
			}
			$this->readonly        = $readonly;
			$this->pending_removal = (bool) $args['removeAt'];
			$this->remove_at       = null;
			if ( $this->pending_removal ) {
				$this->remove_at = new \DateTime( $args['removeAt'] );
			}
			$this->can_remove = (bool) $args['can_remove'];
		}

		/**
		 * Get project Id
		 *
		 * @return string
		 */
		public function get_project_id() {
			return $this->project_id;
		}

		/**
		 * Get project type
		 *
		 * @return string
		 */
		public function get_type() {
			return $this->type;
		}

		/**
		 * Get project type label
		 *
		 * @return string
		 */
		public function get_type_label() {
			return $this->type_label;
		}

		/**
		 * Get project label
		 *
		 * @return string
		 */
		public function get_project_label() {
			return $this->project_label;
		}

		/**
		 * Get project creation date
		 *
		 * @return \DateTime
		 */
		public function get_created_at() {
			return $this->created_at;
		}

		/**
		 * Get project last modified date
		 *
		 * @return \DateTime
		 */
		public function get_updated_at() {
			return $this->updated_at;
		}

		/**
		 * Get number of pages in project
		 *
		 * @return int
		 */
		public function get_pages_count() {
			return $this->number_of_pages;
		}

		/**
		 * Get number of pages calculated for price
		 *
		 * @return int
		 */
		public function get_pages_count_for_price() {
			$count = $this->get_pages_count();
			if ( 'album-2pages' === $this->get_type() ) {
				// do not calculate cover for this type.
				// double amount for inside pages.
				$count = ( $count - 3 ) * 2 + 2;
			}
			return $count;
		}

		/**
		 * Get project template Id
		 *
		 * @return string
		 */
		public function get_source_project_id() {
			return $this->source_project_id;
		}

		/**
		 * Get project preview Url
		 *
		 * @return string
		 */
		public function get_preview() {
			return sprintf(
				'%svi/%s/default.jpg',
				MnumiDesigner_Settings::instance()->get_api_host(),
				$this->get_project_id()
			);
		}

		/**
		 * Is project cloned from template?
		 *
		 * @return bool
		 */
		public function is_cloned() {
			return $this->cloned;
		}

		/**
		 * Is project cloned read only?
		 *
		 * @return bool
		 */
		public function is_readonly() {
			return $this->readonly;
		}

		/**
		 * Is project requested to delete?
		 *
		 * @return bool
		 */
		public function is_pending_removal() {
			return $this->pending_removal;
		}

		/**
		 * Get date project will be removed
		 *
		 * @return \DateTime|null
		 */
		public function get_remove_at() {
			return $this->remove_at;
		}

		/**
		 * Can edit project?
		 *
		 * @return bool
		 */
		public function can_edit() {
			return ! ( $this->is_readonly() || $this->is_pending_removal() );
		}

		/**
		 * Can duplicate project?
		 *
		 * @return bool
		 */
		public function can_duplicate() {
			return ! $this->is_pending_removal();
		}

		/**
		 * Can delete project?
		 *
		 * @return bool
		 */
		public function can_delete() {
			return $this->can_remove && ! $this->is_pending_removal();
		}
	}

endif;
