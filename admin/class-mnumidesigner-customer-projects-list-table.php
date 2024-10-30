<?php
/**
 * MnumiDesigner Admin Customer Projects class
 *
 * @package MnumiDesigner/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MnumiDesigner_Templates_List_Table class.
 */
class MnumiDesigner_Customer_Projects_List_Table extends MnumiDesigner_Backbone_List_Table {
	const MENU = 'mnumidesigner-projects';

	const PROJECT_TYPE_FIELD_NAME = 'project-type';

	/**
	 * Page hook to use screen.
	 *
	 * @var string
	 */
	private static $page;

	/**
	 * Class constructor.
	 *
	 * @param array $args Table parameters.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'singular' => 'project',
				'plural'   => 'projects',
				'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);
		$this->pagination_args['infinite_scroll'] = false;
	}

	/**
	 * Enqueue backend scripts.
	 *
	 * @param string $hook_suffix Current screen Id.
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		if ( $hook_suffix !== $this->screen->id ) {
			return;
		}

		$this->localize_table( array() );

		parent::admin_enqueue_scripts( $hook_suffix );

		wp_register_script(
			'mnumidesigner-admin-projects',
			MnumiDesigner::plugin_dir_url() . 'assets/js/admin/projects.js',
			array( 'jquery', 'jquery-ui-dialog', 'wp-api', 'wp-backbone', 'underscore', 'mnumidesigner-table' ),
			MnumiDesigner::version(),
			true
		);
	}

	/**
	 * Register MnumiDesigner templates menu.
	 */
	public static function register_menu() {
		// just to enforce getting proper plugin_page_hookname.
		// It will be already re-set in add_menu_page
		// global $admin_page_hooks;
		// $admin_page_hooks[ MnumiDesigner::MENU ] = sanitize_title( '' );.
		// normally we would use:
		// 'screen' => get_plugin_page_hookname( self::MENU, MnumiDesigner::MENU' ).
		$view = new self(
			array(
				'screen' => 'mnumidesigner_page_mnumidesigner-projects',
			)
		);

		$page = add_submenu_page(
			MnumiDesigner::MENU,
			'MnumiDesigner',
			'Projects',
			'view_mnumidesigner_project',
			self::MENU,
			array( $view, 'page_callback' )
		);
		add_action(
			'admin_enqueue_scripts',
			array(
				$view,
				'admin_enqueue_scripts',
			)
		);

		add_action( "load-$page", array( $view, 'screen_options' ) );
	}

	/**
	 * Adds screen options.
	 */
	public function screen_options() {
		$screen_options['per_page'] = array(
			'label'   => __( 'Projects', 'mnumidesigner' ),
			'default' => 10,
			'option'  => 'mnumidesigner_projects_per_page',
		);

		foreach ( $screen_options as $option => $args ) {
			add_screen_option( $option, $args );
		}
	}

	/**
	 * Sets screen options.
	 *
	 * @param bool   $keep Whether to save or skip saving the screen option value. Default false.
	 * @param string $option The option name.
	 * @param mixed  $value The number of rows to use.
	 * @return mixed
	 */
	public static function set_screen_option( $keep, $option, $value ) {
		if ( 'mnumidesigner_projects_per_page' === $option ) {
			return $value;
		}

		return $keep;
	}

	/**
	 * Output page content.
	 */
	public function page_callback() {
		if ( ! current_user_can( 'view_mnumidesigner_project' ) ) {
			return;
		}
		wp_enqueue_script( 'mnumidesigner-admin-projects' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		?>
		<div class="wrap" id="projects-list">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Customer projects', 'mnumidesigner' ); ?></h1>
			<hr class="wp-header-end">
			<form id="projects-filter" method="post">
				<?php $this->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get available columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'thumb'        => __( 'Thumb', 'mnumidesigner' ),
			'project_id'   => __( 'Project ID', 'mnumidesigner' ),
			'linked_to'    => __( 'Linked to', 'mnumidesigner' ),
			'created_date' => __( 'Created At', 'mnumidesigner' ),
			'updated_date' => __( 'Updated At', 'mnumidesigner' ),
			'remove_at'    => __( 'Remove At', 'mnumidesigner' ),
		);
		return $columns;
	}

	/**
	 * Get available sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'project_id'   => array( 'id', false ),
			'created_date' => array( 'created_date', false ),
			'updated_date' => array( 'updated_date', true ),
		);
		return $sortable_columns;
	}

	/**
	 * Get name of default sorted column.
	 *
	 * @return string
	 */
	protected function get_default_sorted_column_name() {
		return 'updated_date';
	}

	/**
	 * Get available bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array();

		return $actions;
	}

	/**
	 * Get available project types.
	 *
	 * @return array
	 */
	public function get_types() {
		return MnumiDesigner::instance()->get_types();
	}

	/**
	 * Output extra table nav.
	 *
	 * @param string $which 'top' or 'bottom'.
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
		<?php
		if ( 'top' === $which ) {
			$this->render_filters();
		}
		?>
		</div>
		<?php
	}

	/**
	 * Render any custom filters and search inputs for the list table.
	 */
	protected function render_filters() {
		$this->render_project_type_filter();
	}

	/**
	 * Output Project type filter
	 */
	protected function render_project_type_filter() {
		$current_type = '';
		$types        = $this->get_types();

		if ( empty( $types ) || 1 === count( $types ) ) {
				return;
		}

		?>
		<select class="project-types" id="dropdown_project_type">
		<?php foreach ( $types as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php echo selected( $value, $current_type, false ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Handler for cb column.
	 *
	 * @return string
	 */
	protected function column_cb() {
		return sprintf(
			'<# if ( data.id ) { #><input type="checkbox" name="%1$s[]" value="%2$s" /><# } #>',
			/*$1%s*/ $this->args['plural'],
			/*$2%s*/ '{{ data.id }}'
		);
	}

	/**
	 * Get thumb column content.
	 *
	 * @return string
	 */
	public function column_thumb() {
		return sprintf( '<img src="%s" class="mnumidesigner-project-preview">', '{{ data.preview }}' );
	}

	/**
	 * Get project id column content.
	 *
	 * @return string
	 */
	public function column_project_id() {
		$actions = array();

		if ( current_user_can( 'edit_mnumidesigner_project' ) ) {
			$actions['edit'] = sprintf(
				'<# if (data._links.edit) { #><a href class="edit">%s</a><# } #>',
				__( 'Edit', 'mnumidesigner' )
			);
		}

		if ( current_user_can( 'duplicate_mnumidesigner_project' ) ) {
			$actions['duplicate'] = sprintf(
				'<# if (data._links.duplicate) { #><a href class="duplicate">%s</a><# } #>',
				__( 'Duplicate', 'mnumidesigner' )
			);
		}

		if ( current_user_can( 'delete_mnumidesigner_project' ) ) {
			$actions['delete']  = sprintf(
				'<# if (data._links.delete) { #><a href class="delete">%s</a><# } #>',
				__( 'Delete', 'mnumidesigner' )
			);
			$actions['restore'] = sprintf(
				'<# if (data._links.restore) { #><a href class="restore">%s</a><# } #>',
				__( 'Restore', 'mnumidesigner' )
			);
		}

		// Return the title contents.
		return sprintf(
			'<div>%4$s</div> <span style="color:silver">%1$s (%2$s)</span>%3$s',
			/*$1%s*/ '{{ data.id }}',
			/*$2%s*/ '{{ data.type_label }}',
			/*$3%s*/ $this->row_actions( $actions ),
			/*%4$s*/ '{{ data.project_label }}'
		);
	}

	/**
	 * Get remove at column content.
	 *
	 * @return string
	 */
	public function column_remove_at() {
		return '{{{ data.remove_at }}}';
		// return '<# if (data.collection.state.data.is_pending_removal) { #>{{{ data.remove_at }}}<# }; #>';.
	}

	/**
	 * Get linked order items column content.
	 *
	 * @return string
	 */
	public function column_linked_to() {
		return '<# _.each(data.linked_items, function(item, index) { #><a href="{{{ item.link }}}">{{{ item.name }}}</a><# if (index < data.linked_items.length - 1) { #>, <# } #><# }); #>';
	}

	/**
	 * Process bulk actions.
	 *
	 * @TODO Finish this for backbone version.
	 */
	public function process_bulk_action() {
		return;
	}
}
