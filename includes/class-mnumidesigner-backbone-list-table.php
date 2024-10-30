<?php
/**
 * Administration API: MnumiDesigner_List_Table class
 *
 * Based on WordPress WP_List_Table but modified to use Backbone instead.
 *
 * @package MnumiDesigner
 */

/**
 * MnumiDesigner_Backbone_List_Table class.
 *
 * @access private
 */
abstract class MnumiDesigner_Backbone_List_Table {

	/**
	 * Various information about the current table.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Various information needed for displaying the pagination.
	 *
	 * @var array
	 */
	protected $pagination_args = array();

	/**
	 * The current screen.
	 *
	 * @var object
	 */
	protected $screen;

	/**
	 * Cached bulk actions.
	 *
	 * @var array
	 */
	private $actions;

	/**
	 * Cached pagination output.
	 *
	 * @var string
	 */
	private $pagination;

	/**
	 * Stores the value returned by ->get_column_info().
	 *
	 * @var array
	 */
	protected $column_headers;

	/**
	 * JS Localized options.
	 *
	 * @var array
	 */
	protected $localized = array();

	/**
	 * Constructor.
	 *
	 * The child class should call this constructor from its own constructor to override
	 * the default $args.
	 *
	 * @param array|string $args {
	 *     Array or string of arguments.
	 *
	 *     @type string $plural   Plural value used for labels and the objects being listed.
	 *                            This affects things such as CSS class-names and nonces used
	 *                            in the list table, e.g. 'posts'. Default empty.
	 *     @type string $singular Singular label for an object being listed, e.g. 'post'.
	 *                            Default empty
	 *     @type bool   $ajax     Whether the list table supports Ajax. This includes loading
	 *                            and sorting data, for example. If true, the class will call
	 *                            the _js_vars() method in the footer to provide variables
	 *                            to any scripts handling Ajax events. Default false.
	 *     @type string $screen   String containing the hook name used to determine the current
	 *                            screen. If left null, the current screen will be automatically set.
	 *                            Default null.
	 * }
	 */
	protected function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'plural'   => '',
				'singular' => '',
				'ajax'     => false,
				'screen'   => null,
			)
		);

		$this->screen = convert_to_screen( $args['screen'] );

		add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );

		if ( ! $args['plural'] ) {
			$args['plural'] = $this->screen->base;
		}

		$args['plural']   = sanitize_key( $args['plural'] );
		$args['singular'] = sanitize_key( $args['singular'] );

		$this->args = $args;

		$this->column_headers = $this->get_column_info();
	}

	/**
	 * Adds screen options.
	 */
	public function screen_options() {
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @param array $options JS Localized options.
	 */
	public function localize_table( array $options ) {
		$this->localized = array_merge(
			array(
				'namespace'   => 'mnumidesigner/v1/',
				'apiRoot'     => get_rest_url(),
				'emulateHTTP' => MnumiDesigner_Settings::instance()->get_emulate_http(),
				'table'       => array(
					'per_page' => $this->get_items_per_page(),
				),
			),
			$options
		);
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
		wp_register_script(
			'mnumidesigner-table',
			MnumiDesigner::plugin_dir_url() . 'assets/js/admin/table.js',
			array( 'jquery', 'jquery-ui-dialog', 'wp-api', 'wp-backbone', 'underscore' ),
			MnumiDesigner::version(),
			true
		);

		wp_localize_script(
			'mnumidesigner-table',
			'MnumiDesigner',
			$this->localized
		);
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {
		esc_html_e( 'No items found.', 'mnumidesigner' );
	}

	/**
	 * Displays the search box.
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 */
	public function search_box( $text, $input_id ) {
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array();
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *                      This is designated as optional for backward compatibility.
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->actions ) ) {
			$this->actions = $this->get_bulk_actions();
			$two           = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->actions ) ) {
			return;
		}

		?>
		<label for="bulk-action-selector-<?php echo esc_attr( $which ); ?>" class="screen-reader-text">
		<?php esc_html_e( 'Select bulk action', 'mnumidesigner' ); ?>
		</label>
		<select name="action<?php echo esc_attr( $two ); ?>" id="bulk-action-selector-<?php echo esc_attr( $which ); ?>">
			<option value="-1">
			<?php esc_html_e( 'Bulk Actions', 'mnumidesigner' ); ?>
			</option>

		<?php foreach ( $this->actions as $name => $title ) : ?>
			<?php $class = 'edit' === $name ? ' class="hide-if-no-js"' : ''; ?>
			<option value="<?php echo esc_attr( $name ); ?>" <?php echo esc_attr( $class ); ?>>
				<?php echo esc_html( $title ); ?>
			</option>
		<?php endforeach; ?>
		</select>

		<?php
		submit_button( __( 'Apply', 'mnumidesigner' ), 'action', '', false, array( 'id' => "doaction$two" ) );
	}

	/**
	 * Generate row actions div
	 *
	 * @param string[] $actions        An array of action links.
	 * @param bool     $always_visible Whether the actions should be always visible.
	 * @return string
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i            = 0;

		if ( ! $action_count ) {
			return '';
		}

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			$sep = ' | ';
			if ( $i === $action_count ) {
				$sep = '';
			}
			$out .= "<span class='" . sanitize_html_class( $action ) . "'>$link$sep</span>";
		}
		$out .= '</div>';

		// $out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html_e( 'Show more details', 'mnumidesigner' ) . '</span></button>';

		return $out;
	}

	/**
	 * Get number of items to display on a single page
	 *
	 * @return int
	 */
	protected function get_items_per_page() {
		$user   = get_current_user_id();
		$screen = get_current_screen();

		if ( ! is_object( $screen ) || $screen->id !== $this->screen->id ) {
			return;
		}

		$option = $screen->get_option( 'per_page', 'option' );

		$per_page = get_user_meta( $user, $option, true );

		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		return (int) $per_page;
	}

	/**
	 * Display the pagination.
	 *
	 * @param string $which top or bottom.
	 */
	protected function pagination( $which ) {
		if ( empty( $this->pagination_args ) ) {
			return;
		}

		$infinite_scroll = false;
		if ( isset( $this->pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->pagination_args['infinite_scroll'];
		}

		// <% if ( data.total_pages > 1 ) { %>
		if ( 'top' === $which ) :
			?>
			<?php $this->screen->render_screen_reader_content( 'heading_pagination' ); ?>
		<?php endif; ?>
		<?php // <# } #> ?>
		<?php
		include dirname( __FILE__ ) . '/admin/backbone/html-pagination.php';
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @abstract
	 *
	 * @return array
	 */
	abstract public function get_columns();

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 * Get name of default sortable column
	 *
	 * @return string
	 */
	protected function get_default_sortable_column_name() {
		return '';
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @return string Name of the default primary column, in this case, an empty string.
	 */
	protected function get_default_primary_column_name() {
		$columns = $this->get_columns();
		$column  = '';

		if ( empty( $columns ) ) {
			return $column;
		}

		// We need a primary defined so responsive views show something,
		// so let's fall back to the first non-checkbox column.
		foreach ( $columns as $col => $column_name ) {
			if ( 'cb' === $col ) {
				continue;
			}

			$column = $col;
			break;
		}

		return $column;
	}

	/**
	 * Public wrapper for WP_List_Table::get_default_primary_column_name().
	 *
	 * @return string Name of the default primary column.
	 */
	public function get_primary_column() {
		return $this->get_primary_column_name();
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @return string The name of the primary column.
	 */
	protected function get_primary_column_name() {
		$columns = get_column_headers( $this->screen );
		$default = $this->get_default_primary_column_name();

		// If the primary column doesn't exist fall back to the
		// first non-checkbox column.
		if ( ! isset( $columns[ $default ] ) ) {
			// We need a primary defined so responsive views show something,
			// so let's fall back to the first non-checkbox column.
			foreach ( $columns as $col => $column_name ) {
				if ( 'cb' === $col ) {
					continue;
				}

				$default = $col;
				break;
			}
		}

		/**
		 * Filters the name of the primary column for the current list table.
		 *
		 * @param string $default Column name default for the specific list table, e.g. 'name'.
		 * @param string $context Screen ID for specific list table, e.g. 'plugins'.
		 */
		$column = apply_filters( 'list_table_primary_column', $default, $this->screen->id );

		if ( empty( $column ) || ! isset( $columns[ $column ] ) ) {
			$column = $default;
		}

		return $column;
	}

	/**
	 * Get a list of all, hidden and sortable columns, with filter applied
	 *
	 * @return array
	 */
	protected function get_column_info() {
		// $_column_headers is already set / cached
		if ( isset( $this->column_headers ) && is_array( $this->column_headers ) ) {
			// Back-compat for list tables that have been manually setting $_column_headers for horse reasons.
			// In 4.3, we added a fourth argument for primary column.
			$column_headers = array( array(), array(), array(), $this->get_primary_column_name() );
			foreach ( $this->column_headers as $key => $value ) {
				$column_headers[ $key ] = $value;
			}

			return $column_headers;
		}

		$columns = get_column_headers( $this->screen );
		$hidden  = get_hidden_columns( $this->screen );

		$sortable_columns = $this->get_sortable_columns();
		/**
		 * Filters the list table sortable columns for a specific screen.
		 *
		 * The dynamic portion of the hook name, `$this->screen->id`, refers
		 * to the ID of the current screen, usually a string.
		 *
		 * @param array $sortable_columns An array of sortable columns.
		 */
		$_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) ) {
				continue;
			}

			$data = (array) $data;
			if ( ! isset( $data[1] ) ) {
				$data[1] = false;
			}

			$sortable[ $id ] = $data;
		}

		$primary              = $this->get_primary_column_name();
		$this->column_headers = array( $columns, $hidden, $sortable, $primary );

		return $this->column_headers;
	}

	/**
	 * Return number of visible columns
	 *
	 * @return int
	 */
	public function get_column_count() {
		list ( $columns, $hidden ) = $this->get_column_info();
		$hidden                    = array_intersect( array_keys( $columns ), array_filter( $hidden ) );
		return count( $columns ) - count( $hidden );
	}

	/**
	 * Print column headers, accounting for hidden and sortable columns.
	 *
	 * @staticvar int $cb_counter
	 *
	 * @param bool $with_id Whether to set the id attribute or not.
	 */
	public function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		$default_orderby = $this->get_default_sorted_column_name();

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb']     = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All', 'mnumidesigner' ) . '</label>'
			. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			if ( in_array( $column_key, $hidden, true ) ) {
				$class[] = 'hidden';
			}

			if ( 'cb' === $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ), true ) ) {
				$class[] = 'num';
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			$defult_order = '';
			if ( isset( $sortable[ $column_key ] ) ) {
				list( $orderby, $desc_first ) = $sortable[ $column_key ];

				$order   = $desc_first ? 'desc' : 'asc';
				$class[] = '<# if ((!data.orderby && "' . $column_key . '" == "' . $default_orderby . '") || (data.orderby == "' . $column_key . '")) { #>sorted<# } else { #>sortable<# }#>';

				$class[] = '<# if ((!data.order && "' . $column_key . '" == "' . $default_orderby . '") || ((data.orderby == "' . $column_key . '") && ( data.orderby == "' . $default_orderby . '"))) { #>' . $order . '<# } else { #>{{{ data.order }}}<# }#>';

				$column_display_name = '<a href><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
			$id    = $with_id ? "id='$column_key'" : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
			}

			echo "<$tag $scope $id $class>$column_display_name</$tag>";
		}
	}

	/**
	 * Display the table
	 */
	public function display() {
		$this->display_js_templates();
		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );
		$this->display_table_template();
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Output Backbone table template.
	 */
	protected function display_table_template() {
		$singular = $this->args['singular'];
		?>
		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
			<thead id="mnumidesigner-list-head"></thead>

			<tbody id="mnumidesigner-list"
			<?php if ( $singular ) : ?>
				data-wp-lists='list:<?php echo esc_attr( $singular ); ?>'
			<?php endif ?>
			>
			</tbody>

			<tfoot id="mnumidesigner-list-foot"></tfoot>

		</table>
			<?php
	}

	/**
	 * Output Backbone templates.
	 */
	public function display_js_templates() {
		?>
		<script type="text/html" id="tmpl-mnumidesigner-table-header-view">
		<?php $this->print_column_headers(); ?>
		</script>
		<script type="text/html" id="tmpl-mnumidesigner-table-footer-view">
		<?php $this->print_column_headers( false ); ?>
		</script>
		<script type="text/html" id="tmpl-mnumidesigner-table-row-view">
		<?php $this->single_row_columns( null ); ?>
		</script>
		<script type="text/html" id="tmpl-mnumidesigner-table-empty-view">
		<?php $this->empty_row(); ?>
		</script>
		<script type="text/html" id="tmpl-mnumidesigner-table-loading-view">
		<?php $this->loading_rows(); ?>
		</script>
		<script type="text/html" id="tmpl-mnumidesigner-table-error-view">
		<?php $this->error_row(); ?>
		</script>
		<script type="text/html" id="tmpl-mnumidesigner-table-pagination-top-view">
		<?php $this->pagination( 'top' ); ?>
		</script>
		<script type="text/html" id="tmpl-mnumidesigner-table-pagination-bottom-view">
		<?php $this->pagination( 'bottom' ); ?>
		</script>
		<?php
	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag.
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', $this->args['plural'] );
	}

	/**
	 * Generate the table navigation above or below the table.
	 *
	 * @param string $which top or bottom.
	 */
	protected function display_tablenav( $which ) {
		// if ( 'top' === $which ) {
		// wp_nonce_field( 'bulk-' . $this->args['plural'] );
		// }.
		?>
	<div id="mnumidesigner-table-nav-<?php echo esc_attr( $which ); ?>" class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php // if ( $this->has_items() ) :. ?>
		<div class="alignleft actions bulkactions">
		<?php $this->bulk_actions( $which ); ?>
		</div>
		<?php
		// endif;.
		$this->extra_tablenav( $which );
		?>
			<div class="pagination-container"></div>

		<br class="clear" />
	</div>
		<?php
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param string $which top or bottom.
	 */
	protected function extra_tablenav( $which ) {}

	/**
	 * Default handler for columns.
	 *
	 * @param string $column_name Name of column for processing.
	 */
	protected function column_default( $column_name ) {
		return sprintf( '{{ data.%s }}', $column_name );
	}

	/**
	 * Handler for cb column.
	 *
	 * Like all column_* methods should return string.
	 */
	protected function column_cb() {}

	/**
	 * Generates the columns for a single row of the table
	 */
	protected function single_row_columns() {
		$item = null;
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$classes   = array();
			$classes[] = $column_name;
			$classes[] = "column-$column_name";
			if ( $primary === $column_name ) {
				$classes[] = 'has-row-actions';
				$classes[] = 'column-primary';
			}

			if ( in_array( $column_name, $hidden, true ) ) {
				$classes[] = 'hidden';
			}

			// Comments column uses HTML in the display name with screen reader text.
			// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.

			$classes = implode( ' ', array_map( 'sanitize_html_class', $classes ) );
			?>
			<?php if ( 'cb' === $column_name ) : ?>
				<th scope="row" class="check-column">
					<?php echo $this->column_cb( $item ); ?>
				</th>
			<?php else : ?>
				<td
					class="<?php echo $classes; ?>"
					data-colname="<?php echo wp_strip_all_tags( $column_display_name ); ?>"
					>
					<?php
					if ( method_exists( $this, 'column_' . $column_name ) ) {
						echo call_user_func( array( $this, 'column_' . $column_name ) );
					} else {
						echo $this->column_default( $column_name );
					}
					?>
				</td>
			<?php endif; ?>
				<?php
		}
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string The row actions HTML, or an empty string if the current column is the primary column.
	 */
	protected function handle_row_actions( $column_name, $primary ) {
		return $column_name === $primary ? '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'mnumidesigner' ) . '</span></button>' : '';
	}

	/**
	 * Output empty row indicator.
	 */
	protected function empty_row() {
		?>
		<tr class="no-items">
			<td class="colspanchange" colspan="<?php echo esc_attr( $this->get_column_count() ); ?>">
			<?php $this->no_items(); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output error row indicator.
	 */
	protected function error_row() {
		?>
		<tr class="error">
			<td class="colspanchange" colspan="<?php echo esc_attr( $this->get_column_count() ); ?>">
				<?php esc_html_e( 'Error', 'mnumidesigner' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output loading rows indicator.
	 */
	protected function loading_rows() {
		?>
		<tr class="loading">
			<td class="colspanchange" colspan="<?php echo esc_attr( $this->get_column_count() ); ?>">
				<?php esc_html_e( 'Loading...', 'mnumidesigner' ); ?>
			</td>
		</tr>
		<?php
	}
}
