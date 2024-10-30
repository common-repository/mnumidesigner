<?php
/**
 * MnumiDesigner Admin Settings class
 *
 * @package MnumiDesigner/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * MnumiDesigner_Settings class.
 */
final class MnumiDesigner_Settings {
	const DEFAULT_API_DOMAIN = 'wizard.mnumi.com';

	const API_KEY_ID = 'mnumidesigner_api_key_id';
	const API_KEY    = 'mnumidesigner_api_key';
	const API_HOST   = 'mnumidesigner_api_host';

	const EMULATE_HTTP = 'mnumidesigner_emulate_http';

	const ADD_TO_CART_LABEL = 'mnumidesigner_add_to_cart_label';

	const MENU = 'mnumidesigner-settings';

	const SETTINGS_PAGE       = 'mnumidesigner';
	const SECTION_DEFAULT     = 'general';
	const SECTION_WOOCOMMERCE = 'woocommerce';
	const SECTION_ADDITIONAL  = 'additional';

	/**
	 * Singleton instance of MnumiDesigner_Settings.
	 *
	 * @var MnumiDesigner_Settings
	 */
	protected static $instance = null;

	/**
	 * Get or create MnumiDesigner_Settings instance.
	 *
	 * @return MnumiDesigner_Settings
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init and hook in the integration.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Includes required files.
	 */
	public function includes() {
	}

	/**
	 * Initializes hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initializes MnumiDesigner settings page.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . MnumiDesigner::plugin_basename(), array( $this, 'add_plugin_page_settings_link' ), 10, 4 );

		add_action(
			'admin_enqueue_scripts',
			function() {
				wp_register_script(
					'mnumidesigner-settings',
					MnumiDesigner::plugin_dir_url() . 'assets/js/admin/settings.js',
					array( 'jquery', 'wp-api' ),
					MnumiDesigner::version(),
					true
				);
				wp_localize_script(
					'mnumidesigner-settings',
					'MnumiDesigner',
					array(
						'namespace'   => 'mnumidesigner/v1/',
						'apiRoot'     => get_rest_url(),
						'emulateHTTP' => MnumiDesigner_Settings::instance()->get_emulate_http(),
						'api'         => array(
							'settings' => array(
								'url'   => esc_url_raw( rest_url( 'mnumidesigner/v1/settings' ) ),
								'nonce' => wp_create_nonce( 'wp_rest' ),
							),
						),
					)
				);
			}
		);
	}

	/**
	 * Registers MnumiDesigner settings.
	 */
	public function register_settings() {
		add_settings_section(
			self::SECTION_DEFAULT,
			__( 'API Access', 'mnumidesigner' ),
			'',
			self::SETTINGS_PAGE
		);
		add_settings_section(
			self::SECTION_WOOCOMMERCE,
			__( 'Woocommerce', 'mnumidesigner' ),
			'',
			self::SETTINGS_PAGE
		);
		add_settings_section(
			self::SECTION_ADDITIONAL,
			__( 'Additional', 'mnumidesigner' ),
			'',
			self::SETTINGS_PAGE
		);
		add_settings_field(
			self::API_KEY_ID,
			'API Key ID',
			array( $this, 'api_key_id_input' ),
			self::SETTINGS_PAGE,
			self::SECTION_DEFAULT,
			array(
				'label_for' => self::API_KEY_ID,
				'class'     => '',
			)
		);
		add_settings_field(
			self::API_KEY,
			__( 'API Key', 'mnumidesigner' ),
			array( $this, 'api_key_input' ),
			self::SETTINGS_PAGE,
			self::SECTION_DEFAULT,
			array(
				'label_for' => self::API_KEY,
				'class'     => '',
			)
		);
		add_settings_field(
			self::ADD_TO_CART_LABEL,
			__( '"Add to Cart" button label', 'mnumidesigner' ),
			array( $this, 'add_to_cart_label_input' ),
			self::SETTINGS_PAGE,
			self::SECTION_WOOCOMMERCE,
			array(
				'label_for' => self::ADD_TO_CART_LABEL,
				'class'     => '',
			)
		);
		add_settings_field(
			self::EMULATE_HTTP,
			__( 'Emulate HTTP requests', 'mnumidesigner' ),
			array( $this, 'emulate_http_input' ),
			self::SETTINGS_PAGE,
			self::SECTION_ADDITIONAL,
			array(
				'label_for' => self::EMULATE_HTTP,
				'class'     => '',
			)
		);
		add_settings_field(
			self::API_HOST,
			__( 'API Host', 'mnumidesigner' ),
			array( $this, 'api_host_input' ),
			self::SETTINGS_PAGE,
			self::SECTION_ADDITIONAL,
			array(
				'label_for' => self::API_HOST,
				'class'     => '',
			)
		);
		register_setting(
			self::SETTINGS_PAGE,
			self::ADD_TO_CART_LABEL,
			array(
				'type'              => 'string',
				'description'       => __( 'Products with attached MnumiDesigner template will have this text displayed in Add to Cart button', 'mnumidesigner' ),
				'sanitize_callback' => array( $this, 'sanitize_add_to_cart_label' ),
				'show_in_rest'      => false,
				'default'           => 'Personalize',
			)
		);
		register_setting(
			self::SETTINGS_PAGE,
			self::EMULATE_HTTP,
			array(
				'type'              => 'boolean',
				'description'       => sprintf(
					'%s. <a href="https://developer.wordpress.org/rest-api/using-the-rest-api/global-parameters/#_method-or-x-http-method-override-header">%s</a>',
					__( 'When Wordpress server does not understand some HTTP Methods, e.g.: DELETE, this option should be turned on', 'mnumidesigner' ),
					__( 'More info', 'mnumidesigner' )
				),
				'sanitize_callback' => array( $this, 'sanitize_emulate_http' ),
				'show_in_rest'      => false,
				'default'           => '',
			)
		);
		register_setting(
			self::SETTINGS_PAGE,
			self::API_HOST,
			array(
				'type'              => 'string',
				'description'       => '',
				'sanitize_callback' => array( $this, 'sanitize_api_host' ),
				'show_in_rest'      => false,
				'default'           => 'https://wizard.mnumi.com/',
			)
		);
		register_setting(
			self::SETTINGS_PAGE,
			self::API_KEY,
			array(
				'type'              => 'string',
				'description'       => __( 'Enter with your API Key.', 'mnumidesigner' ),
				'sanitize_callback' => array( $this, 'sanitize_api_key' ),
				'show_in_rest'      => false,
				'default'           => null,
			)
		);
		register_setting(
			self::SETTINGS_PAGE,
			self::API_KEY_ID,
			array(
				'type'              => 'integer',
				'description'       => __( 'Enter with your API Key ID', 'mnumidesigner' ),
				'sanitize_callback' => array( $this, 'sanitize_api_key_id' ),
				'show_in_rest'      => false,
				'default'           => null,
			)
		);
	}

	/**
	 * Register MnumiDesigner settings menu.
	 */
	public static function register_menu() {
		add_submenu_page(
			MnumiDesigner::MENU,
			'Settings',
			'Settings',
			'manage_mnumidesigner',
			self::MENU,
			array( __CLASS__, 'page_callback' )
		);
	}

	/**
	 * Adds plugin settings link in installed Plugins view.
	 *
	 * @param string[] $actions An array of plugin action links.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data.
	 * @param string   $context The plugin context.
	 * @return array
	 */
	public function add_plugin_page_settings_link( $actions, $plugin_file, $plugin_data, $context ) {
		$links = array(
			'settings' => sprintf(
				'<a href="%s">%s</a>',
				menu_page_url( self::MENU, false ),
				__( 'Settings', 'mnumidesigner' )
			),
		);
		return array_merge( $links, $actions );
	}

	/**
	 * Get available tabs in MnumiDesigner settings view.
	 *
	 * @return array
	 */
	public function get_tabs() {
		global $wp_settings_sections;

		$tabs = array();

		if ( isset( $wp_settings_sections[ self::SETTINGS_PAGE ] ) ) {
			foreach ( (array) $wp_settings_sections[ self::SETTINGS_PAGE ] as $section ) {
				$tabs[ $section['id'] ] = $section['title'];
			}
		}

		return $tabs;
	}

	/**
	 * Get page url to the given settings tab.
	 *
	 * @param string $tab Tab to check.
	 * @return string
	 */
	private static function get_tab_url( $tab ) {
		return add_query_arg( 'tab', $tab, menu_page_url( self::MENU, false ) );
	}

	/**
	 * Get settings tab for current page.
	 *
	 * @return string
	 */
	private static function get_current_tab() {
		$current_tab = self::SECTION_DEFAULT;
		if ( ! empty( $_REQUEST['tab'] ) ) {
			$current_tab = sanitize_title( wp_unslash( $_REQUEST['tab'] ) );
		}

		return $current_tab;
	}

	/**
	 * Checks if given tab is active.
	 *
	 * @param string $tab Tab to check.
	 * @return bool
	 */
	private static function is_tab_active( $tab ) {
		return self::get_current_tab() === $tab;
	}

	/**
	 * Output page content.
	 */
	public static function page_callback() {
		if ( ! current_user_can( 'manage_mnumidesigner' ) ) {
			return;
		}

		$tabs        = self::instance()->get_tabs();
		$current_tab = self::get_current_tab();

		$tab_exists = isset( $tabs[ $current_tab ] );

		if ( ! $tab_exists ) {
			wp_safe_redirect( menu_page_url( self::MENU, false ) );
			exit;
		}
		wp_enqueue_script( 'mnumidesigner-settings' );

		?>
		<div class='wrap'>
			<h1><?php esc_html_e( 'MnumiDesigner', 'mnumidesigner' ); ?></h1>
			<form method='post' action="options.php">
				<nav class="nav-tab-wrapper">
					<?php foreach ( $tabs as $tab => $title ) : ?>
						<a href="<?php echo esc_url( self::get_tab_url( $tab ) ); ?>" class="nav-tab <?php echo esc_attr( self::is_tab_active( $tab ) ? 'nav-tab-active' : '' ); ?>">
							<?php echo esc_html( $title ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php
				settings_fields( self::SETTINGS_PAGE );
				self::do_settings_sections( self::SETTINGS_PAGE, $current_tab );
			?>
			<p class="submit">
			<?php submit_button( null, 'primary', 'submit', false ); ?>
			<?php
			if ( self::SECTION_DEFAULT === $current_tab ) {
				$settings = self::instance();
				if ( ! $settings->get_api_key() || ! $settings->get_api_key_id() ) {
					submit_button( 'Get free access', 'primary', 'mnumidesigner-register-demo', false );
				} elseif ( $settings->get_api_key() && $settings->get_api_key_id() && (
					MnumiDesigner_API::instance()->is_free() ||
					MnumiDesigner_API::instance()->is_demo()
				) ) {
					?>
					<a href="https://mnumidesigner.com/pricing/" target="_blank" class="button button-primary"><?php esc_html_e( 'Buy Pro version', 'mnumidesigner' ); ?></a>
					<?php
				}
			}
			?>
			</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Gets saved MnumiDesigner API Key
	 *
	 * @return string
	 */
	public function get_api_key() {
		return get_option( self::API_KEY, null );
	}

	/**
	 * Gets saved MnumiDesigner API Key Id
	 *
	 * @return string
	 */
	public function get_api_key_id() {
		return get_option( self::API_KEY_ID, null );
	}

	/**
	 * Gets saved MnumiDesigner host
	 *
	 * @return string
	 */
	public function get_api_host() {
		return get_option(
			self::API_HOST,
			sprintf(
				'%s://%s/',
				( is_ssl() ? 'https' : 'http' ),
				self::DEFAULT_API_DOMAIN
			)
		);
	}

	/**
	 * Gets saved MnumiDesigner "Add to Cart" text for products containing MnumiDesigner templates.
	 *
	 * @return string
	 */
	public function get_add_to_cart_label() {
		return get_option( self::ADD_TO_CART_LABEL, __( 'Personalize', 'mnumidesigner' ) );
	}

	/**
	 * Gets saved MnumiDesigner "Emulate HTTP" option used in backbone.
	 *
	 * @return bool
	 */
	public function get_emulate_http() {
		return (bool) get_option( self::EMULATE_HTTP, true );
	}

	/**
	 * Output MnumiDesigner API Key input.
	 *
	 * @param array $args array Input args.
	 */
	public function api_key_input( $args ) {
		global $wp_registered_settings;
		$value           = $this->get_api_key();
		$hidden          = isset( $args['hidden'] ) && $args['hidden'];
		$type            = $hidden ? 'hidden' : 'text';
		$has_description = isset( $wp_registered_settings[ self::API_KEY ]['description'] );
		?>
		<input
			id="<?php echo esc_attr( self::API_KEY ); ?>"
			name="<?php echo esc_attr( self::API_KEY ); ?>"
			type="<?php echo esc_attr( $type ); ?>" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" />
		<?php if ( ! $hidden && $has_description ) : ?>
			<p class="description">
				<?php echo esc_html( $wp_registered_settings[ self::API_KEY ]['description'] ); ?>
			</p>
		<?php endif; ?>

		<?php
	}

	/**
	 * Output MnumiDesigner API Key Id input.
	 *
	 * @param array $args array Input args.
	 */
	public function api_key_id_input( $args ) {
		global $wp_registered_settings;
		$value           = $this->get_api_key_id();
		$hidden          = isset( $args['hidden'] ) && $args['hidden'];
		$type            = $hidden ? 'hidden' : 'text';
		$has_description = isset( $wp_registered_settings[ self::API_KEY_ID ]['description'] );
		?>
		<input id="<?php echo esc_attr( self::API_KEY_ID ); ?>" name="<?php echo esc_attr( self::API_KEY_ID ); ?>" type="<?php echo esc_attr( $type ); ?>" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" />
		<?php if ( ! $hidden && $has_description ) : ?>
			<p class="description">
				<?php echo esc_html( $wp_registered_settings[ self::API_KEY_ID ]['description'] ); ?>
			</p>
		<?php endif; ?>

		<?php
	}

	/**
	 * Output MnumiDesigner API host input.
	 *
	 * @param array $args array Input args.
	 */
	public function api_host_input( $args ) {
		global $wp_registered_settings;
		$value           = $this->get_api_host();
		$hidden          = isset( $args['hidden'] ) && $args['hidden'];
		$type            = $hidden ? 'hidden' : 'text';
		$has_description = isset( $wp_registered_settings[ self::API_HOST ]['description'] );
		?>
		<input id="<?php echo esc_attr( self::API_HOST ); ?>" name="<?php echo esc_attr( self::API_HOST ); ?>" type="<?php echo esc_attr( $type ); ?>" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" />
		<?php if ( ! $hidden && $has_description ) : ?>
			<p class="description">
				<?php echo esc_html( $wp_registered_settings[ self::API_HOST ]['description'] ); ?>
			</p>
		<?php endif; ?>

		<?php
	}

	/**
	 * Output MnumiDesigner "Add to Cart" input.
	 *
	 * @param array $args array Input args.
	 */
	public function add_to_cart_label_input( $args ) {
		global $wp_registered_settings;
		$value           = $this->get_add_to_cart_label();
		$hidden          = isset( $args['hidden'] ) && $args['hidden'];
		$type            = $hidden ? 'hidden' : 'text';
		$has_description = isset( $wp_registered_settings[ self::ADD_TO_CART_LABEL ]['description'] );
		?>
		<input id="<?php echo esc_attr( self::ADD_TO_CART_LABEL ); ?>" name="<?php echo esc_attr( self::ADD_TO_CART_LABEL ); ?>" type="<?php echo esc_attr( $type ); ?>" class="regular-text code" value="<?php echo esc_attr( $value ); ?>" />
		<?php if ( ! $hidden && $has_description ) : ?>
			<p class="description">
				<?php echo esc_html( $wp_registered_settings[ self::ADD_TO_CART_LABEL ]['description'] ); ?>
			</p>
		<?php endif; ?>

		<?php
	}

	/**
	 * Output MnumiDesigner "Emulate HTTP" input.
	 *
	 * @param array $args array Input args.
	 */
	public function emulate_http_input( $args ) {
		global $wp_registered_settings;
		$value           = $this->get_emulate_http();
		$hidden          = isset( $args['hidden'] ) && $args['hidden'];
		$type            = $hidden ? 'hidden' : 'checkbox';
		$has_description = isset( $wp_registered_settings[ self::EMULATE_HTTP ]['description'] );
		?>
		<input id="<?php echo esc_attr( self::EMULATE_HTTP ); ?>" name="<?php echo esc_attr( self::EMULATE_HTTP ); ?>" type="<?php echo esc_attr( $type ); ?>" class="checkbox" <?php checked( $value, 1 ); ?> value="1" />
		<?php if ( ! $hidden && $has_description ) : ?>
			<p class="description">
				<?php echo $wp_registered_settings[ self::EMULATE_HTTP ]['description']; ?>
			</p>
		<?php endif; ?>

		<?php
	}

	/**
	 * Sanitize MnumiDesigner API Key value.
	 *
	 * @param string $api_key Value to save.
	 * @return string
	 */
	public function sanitize_api_key( $api_key ) {
		return sanitize_text_field( $api_key );
	}

	/**
	 * Sanitize MnumiDesigner API Key Id value.
	 *
	 * @param string $api_key_id Value to save.
	 * @return string
	 */
	public function sanitize_api_key_id( $api_key_id ) {
		return sanitize_text_field( $api_key_id );
	}

	/**
	 * Sanitize MnumiDesigner API Host value.
	 *
	 * @param string $api_host Value to save.
	 * @return string
	 */
	public function sanitize_api_host( $api_host ) {
		$api_host = sanitize_text_field( $api_host );
		if ( $api_host ) {
			$url      = wp_parse_url( $api_host );
			$api_host = sprintf( '%s://%s/', $url['scheme'], $url['host'] );
		}
		return $api_host;
	}

	/**
	 * Sanitize MnumiDesigner "Add to Cart" value.
	 *
	 * @param string $add_to_cart_label Value to save.
	 * @return string
	 */
	public function sanitize_add_to_cart_label( $add_to_cart_label ) {
		return sanitize_text_field( $add_to_cart_label );
	}

	/**
	 * Sanitize MnumiDesigner "Emulate HTTP" value.
	 *
	 * @param bool $emulate_http Value to save.
	 * @return int
	 */
	public function sanitize_emulate_http( $emulate_http ) {
		return (int) $emulate_http;
	}

	/**
	 * Display fields from other tabs as hidden, to prevent loosing data.
	 *
	 * @param string $page Settings page.
	 * @param string $section Settings section.
	 */
	private static function hidden_fields( $page, $section ) {
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
			$field['args']['hidden'] = true;
			call_user_func( $field['callback'], $field['args'] );
		}
	}

	/**
	 * Display settings fields from given tab.
	 *
	 * @param string $page Settings page.
	 * @param string $tab Settings tab.
	 */
	private static function do_settings_sections( $page, $tab ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
			if ( $tab !== $section['id'] ) {
				self::hidden_fields( $page, $section['id'] );
				continue;
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
				continue;
			}
			echo '<table class="form-table">';
			do_settings_fields( $page, $section['id'] );
			echo '</table>';
		}
	}
}

