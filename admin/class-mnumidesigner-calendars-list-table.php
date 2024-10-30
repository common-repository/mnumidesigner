<?php
/**
 * MnumiDesigner Admin Calendars class
 *
 * @package MnumiDesigner/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MnumiDesigner_Calendar_List_Table class.
 */
class MnumiDesigner_Calendars_List_Table extends MnumiDesigner_Backbone_List_Table {
	const MENU = 'mnumidesigner-calendars';

	/**
	 * Class constructor.
	 *
	 * @param array $args Table parameters.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'singular' => 'calendar event',
				'plural'   => 'calendar events',
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

		$this->localize_table(
			array(
				'api' => array(
					'add_new' => array(
						'title' => __( 'Create new calendar', 'mnumidesigner' ),
					),
					'edit'    => array(
						'title' => __( 'Edit calendar', 'mnumidesigner' ),
					),
				),
			)
		);
		parent::admin_enqueue_scripts( $hook_suffix );

		wp_register_script(
			'mnumidesigner-calendars',
			MnumiDesigner::plugin_dir_url() . 'assets/js/admin/calendars.js',
			array( 'jquery', 'jquery-ui-dialog', 'wp-api', 'wp-backbone', 'underscore', 'mnumidesigner-table' ),
			MnumiDesigner::version(),
			true
		);
	}

	/**
	 * Register MnumiDesigner calendars menu.
	 */
	public static function register_menu() {
		// normally we would use:
		// 'screen' => get_plugin_page_hookname( self::MENU, MnumiDesigner::MENU' ).
		$view = new self(
			array(
				'screen' => 'mnumidesigner_page_mnumidesigner-calendars',
			)
		);

		$page = add_submenu_page(
			MnumiDesigner::MENU,
			'MnumiDesigner',
			'Calendars',
			'view_mnumidesigner_calendar',
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
			'label'   => __( 'Calendars', 'mnumidesigner' ),
			'default' => 10,
			'option'  => 'mnumidesigner_calendars_per_page',
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
	 * @return string
	 */
	public static function set_screen_option( $keep, $option, $value ) {
		if ( 'mnumidesigner_calendars_per_page' === $option ) {
			return $value;
		}

		return $keep;
	}

	/**
	 * Output page content.
	 */
	public function page_callback() {
		if ( ! current_user_can( 'view_mnumidesigner_calendar' ) ) {
			return;
		}
		wp_enqueue_script( 'mnumidesigner-calendars' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		?>
		<div class="wrap" id="calendars-list">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Calendars', 'mnumidesigner' ); ?></h1>
			<?php if ( current_user_can( 'create_mnumidesigner_calendar' ) ) : ?>
			<a href="" class="page-title-action open-new-calendar-dialog"><?php esc_html_e( 'Add New', 'mnumidesigner' ); ?></a>
			<?php endif; ?>
			<hr class="wp-header-end">
			<form method="post">
				<?php $this->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Output Backbone templates.
	 */
	public function display_js_templates() {
		parent::display_js_templates();
		include dirname( __FILE__ ) . '/../includes/admin/backbone/html-calendars.php';
	}

	/**
	 * Get available columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'name'     => __( 'Name', 'mnumidesigner' ),
			'type'     => __( 'Type', 'mnumidesigner' ),
			'locale'   => __( 'Locale', 'mnumidesigner' ),
			'modified' => __( 'Last modified', 'mnumidesigner' ),
		);
		return $columns;
	}

	/**
	 * Get name of default sorted column.
	 *
	 * @return string
	 */
	protected function get_default_sorted_column_name() {
		return 'modified';
	}

	/**
	 * Get name column content.
	 *
	 * @return string
	 */
	public function column_name() {
		$actions = array();

		if ( current_user_can( 'edit_mnumidesigner_calendar' ) ) {
			$actions['edit'] = sprintf(
				'<a href class="edit">%s</a>',
				__( 'Edit', 'mnumidesigner' )
			);
		}
		if ( current_user_can( 'delete_mnumidesigner_calendar' ) ) {
			$actions['delete'] = sprintf(
				'<a href class="delete">%s</a>',
				__( 'Delete', 'mnumidesigner' )
			);
		}
		return sprintf(
			'<div>%1$s</div>%2$s',
			/*%1$s*/ '{{ data.name }}',
			/*$2%s*/ $this->row_actions( $actions )
		);
	}
}
