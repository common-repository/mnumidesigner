<?php
/**
 * Main MnumiDesigner class.
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* Required for conditional check for related plugins, e.g.: WooCommerce */
require_once ABSPATH . 'wp-admin/includes/plugin.php';
/* Required for WP_Filesystem_Direct usage */
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

/**
 * MnumiDesigner class.
 */
final class MnumiDesigner {
	const MENU = 'mnumidesigner';

	/**
	 * Singleton instance of MnumiDesigner.
	 *
	 * @var MnumiDesigner
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
	 * Class constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();

		// register settings menu.
		MnumiDesigner_Settings::instance();
	}

	/**
	 * Get current version of plugin.
	 *
	 * @return string
	 */
	public static function version() {
		$data = get_plugin_data( MNUMIDESIGNER_PLUGIN_FILE );
		return $data['Version'];
	}

	/**
	 * Get directory path where translations are stored.
	 *
	 * @return string
	 */
	public static function plugin_translations_dir() {
		$upload_dir = wp_upload_dir( null, false );
		return $upload_dir['basedir'] . '/mnumidesigner-translations';
	}

	/**
	 * Get directory path where calendars are stored.
	 *
	 * @return string
	 */
	public static function plugin_calendars_dir() {
		$upload_dir = wp_upload_dir( null, false );
		return $upload_dir['basedir'] . '/mnumidesigner-calendars';
	}

	/**
	 * Get plugin directory url.
	 *
	 * @return string
	 */
	public static function plugin_dir_url() {
		return plugin_dir_url( MNUMIDESIGNER_PLUGIN_FILE );
	}

	/**
	 * Get plugin basename.
	 *
	 * @return string
	 */
	public static function plugin_basename() {
		return plugin_basename( MNUMIDESIGNER_PLUGIN_FILE );
	}

	/**
	 * Initializes hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'user_has_cap', array( $this, 'customer_has_cap' ), 10, 4 );
		add_filter( 'user_has_cap', array( $this, 'manager_has_cap' ), 10, 4 );
		add_action( 'init', array( 'MnumiDesigner_Shortcodes', 'init' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_demo_notice' ) );

		add_action(
			'admin_enqueue_scripts',
			function() {
				wp_enqueue_style(
					'mnumidesigner-admin',
					MnumiDesigner::plugin_dir_url() . '/assets/css/admin.css',
					array(),
					MnumiDesigner::version()
				);
			}
		);

		register_activation_hook( self::plugin_basename(), array( 'MnumiDesigner_Activator', 'activate' ) );
		register_deactivation_hook( self::plugin_basename(), array( 'MnumiDesigner_Deactivator', 'deactivate' ) );

		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_mnumidesigner_redirect' ), 10 );
		add_filter( 'allowed_http_origins', array( $this, 'allow_mnumidesigner_for_cors' ), 10 );

		add_action(
			'rest_api_init',
			function () {
				$controller = new MnumiDesigner_REST_Settings_Controller();
				$controller->register_routes();
				$controller = new MnumiDesigner_REST_Translations_Controller();
				$controller->register_routes();
				$controller = new MnumiDesigner_REST_Calendars_Controller();
				$controller->register_routes();
				$controller = new MnumiDesigner_REST_Projects_Controller();
				$controller->register_routes();

				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					$controller = new MnumiDesigner_REST_Products_Controller();
					$controller->register_routes();
				}
			}
		);
	}

	/**
	 * Allow redirecting user outside WordPress to MnumiDesigner.
	 * Required as editing project is under separate server.
	 *
	 * @param array $origins Allowed origins.
	 * @return array
	 */
	public function allow_mnumidesigner_redirect( $origins ) {
		if ( self::is_configured() ) {
			$host      = MnumiDesigner_Settings::instance()->get_api_host();
			$origins[] = wp_parse_url( $host, PHP_URL_HOST );
		}
		return $origins;
	}

	/**
	 * Allow MnumiDesigner server for using CORS.
	 *
	 * @param array $origins Allowed origins.
	 * @return array
	 */
	public function allow_mnumidesigner_for_cors( $origins ) {
		if ( self::is_configured() ) {
			$host      = MnumiDesigner_Settings::instance()->get_api_host();
			$parsed    = wp_parse_url( $host, PHP_URL_HOST );
			$origins[] = 'http://' . $parsed;
			$origins[] = 'https://' . $parsed;
		}
		return $origins;
	}

	/**
	 * Init menu and plugin translation domain.
	 */
	public function init() {
		load_plugin_textdomain( 'mnumidesigner', false, self::plugin_dir_url() . '/languages' );

		add_action(
			'admin_menu',
			array(
				'MnumiDesigner_Templates_List_Table',
				'register_menu',
			)
		);
		add_action(
			'admin_menu',
			array(
				'MnumiDesigner_Customer_Projects_List_Table',
				'register_menu',
			)
		);
		add_action(
			'admin_menu',
			array(
				'MnumiDesigner_Translations_List_Table',
				'register_menu',
			)
		);
		add_action(
			'admin_menu',
			array(
				'MnumiDesigner_Calendars_List_Table',
				'register_menu',
			)
		);
		add_filter(
			'set-screen-option',
			array(
				'MnumiDesigner_Translations_List_Table',
				'set_screen_option',
			),
			10,
			3
		);

		add_action(
			'admin_menu',
			array(
				'MnumiDesigner_Settings',
				'register_menu',
			)
		);
	}

	/**
	 * Base64 encoded logo for using in Menu.
	 *
	 * @return string
	 */
	public static function get_logo() {
		$fs = new WP_Filesystem_Direct( '' );
		return sprintf(
			'data:image/svg+xml;base64,%s',
			base64_encode( $fs->get_contents( dirname( __FILE__ ) . '/assets/logo.svg' ) )
		);
	}
	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param mixed $links Plugin Row Meta.
	 * @param mixed $file  Plugin Base file.
	 *
	 * @return array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( self::plugin_basename() === $file ) {
			$row_meta = array(
				'docs'    => sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					esc_url( 'https://mnumidesigner.com/documentation/' ),
					esc_attr__( 'View MnumiDesigner documentation', 'mnumidesigner' ),
					esc_html__( 'Docs', 'mnumidesigner' )
				),
				'support' => sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( 'https://mnumidesigner.com/contact/' ),
					esc_html__( 'Contact', 'mnumidesigner' )
				),
			);
			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	/**
	 * Checks if a user has a certain capability.
	 *
	 * @TODO
	 * @param array   $allcaps All capabilities.
	 * @param array   $caps    Capabilities.
	 * @param array   $args    Arguments.
	 * @param WP_User $user    The user object.
	 * @return bool
	 */
	public function customer_has_cap( $allcaps, $caps, $args, $user ) {
		if ( isset( $caps[0] ) ) {
			switch ( $caps[0] ) {
				case 'view_mnumidesigner_project':
				case 'view_mnumidesigner_template':
				case 'view_mnumidesigner_translation':
					break;
			}
		}
		return $allcaps;
	}

	/**
	 * Checks if a user has a certain capability.
	 *
	 * @TODO
	 * @param array   $allcaps All capabilities.
	 * @param array   $caps    Capabilities.
	 * @param array   $args    Arguments.
	 * @param WP_User $user    The user object.
	 * @return bool
	 */
	public function manager_has_cap( $allcaps, $caps, $args, $user ) {
		if ( isset( $caps[0] ) ) {
			switch ( $caps[0] ) {
				case 'view_mnumidesigner_project':
				case 'view_mnumidesigner_template':
				case 'view_mnumidesigner_translation':
					break;
			}
		}
		return $allcaps;
	}

	/**
	 * Show demo notice if user uses demo version.
	 */
	public function maybe_show_demo_notice() {
		global $wp;
		$settings     = MnumiDesigner_Settings::instance();
		$api          = MnumiDesigner_API::instance();
		$settings_url = menu_page_url( MnumiDesigner_Settings::MENU, false );
		?>
		<?php if ( ! $settings->get_api_key_id() || ! $settings->get_api_key() ) : ?>
		<div class="notice notice-warning mnumidesigner-no-api-credentials">
			<p><?php esc_html_e( 'Your MnumiDesigner plugin needs to be configured to work properly', 'mnumidesigner' ); ?>.</p>
			<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Enter your MnumiDesigner Api access credentials', 'mnumidesigner' ); ?></a>.
		</div>
		<?php elseif ( $api->is_demo() ) : ?>
		<div class="notice notice-warning">
			<p>
			<?php
			echo esc_html(
				sprintf(
				/* translators: %s api access expiration date */
					__( 'You are using demo version of MnumiDesigner plugin which means that you can use it until: %s', 'mnumidesigner' ),
					$api->demo_active_to()->format( 'r' )
				)
			);
			?>
			.</p>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Include used files.
	 */
	public function includes() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

		include_once 'includes/helper-functions.php';
		include_once 'includes/class-mnumidesigner-api-client.php';
		include_once 'includes/class-mnumidesigner-api.php';
		include_once 'includes/class-mnumidesigner-backbone-list-table.php';

		include_once 'admin/class-mnumidesigner-templates-list-table.php';
		include_once 'admin/class-mnumidesigner-customer-projects-list-table.php';
		include_once 'admin/class-mnumidesigner-translations-list-table.php';
		include_once 'admin/class-mnumidesigner-calendars-list-table.php';
		include_once 'admin/class-mnumidesigner-settings.php';

		include_once 'includes/models/class-mnumidesigner-setting.php';
		include_once 'includes/models/class-mnumidesigner-translation.php';
		include_once 'includes/models/class-mnumidesigner-project.php';

		include_once 'includes/class-mnumidesigner-install.php';
		include_once 'includes/class-mnumidesigner-activator.php';
		include_once 'includes/class-mnumidesigner-deactivator.php';

		include_once 'includes/class-mnumidesigner-shortcodes.php';
		include_once 'includes/class-mnumidesigner-backurl-handler.php';
		include_once 'includes/rest-api/endpoints/class-mnumidesigner-rest-settings-controller.php';
		include_once 'includes/rest-api/endpoints/class-mnumidesigner-rest-translations-controller.php';
		include_once 'includes/rest-api/endpoints/class-mnumidesigner-rest-calendars-controller.php';
		include_once 'includes/rest-api/endpoints/class-mnumidesigner-rest-projects-controller.php';

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			include_once 'includes/woocommerce/class-mnumidesigner-woocommerce.php';

			// Product related (admin view).
			include_once 'admin/woocommerce/class-mnumidesigner-wc-product.php';

			// Order related (admin view).
			include_once 'admin/woocommerce/class-mnumidesigner-wc-order-data.php';

			include_once 'includes/woocommerce/class-mnumidesigner-wc-cart.php';

			include_once 'includes/rest-api/endpoints/class-mnumidesigner-rest-products-controller.php';
		}
	}

	/**
	 * Veeery simple assert for checking if plugin has been configured.
	 */
	public static function is_configured() {
		$settings = MnumiDesigner_Settings::instance();
		return $settings->get_api_key() && $settings->get_api_key_id() && $settings->get_api_host();
	}

	/**
	 * Get available project ownership types.
	 *
	 * @return array
	 */
	public function get_ownership_types() {
		return array(
			''       => __( 'All', 'mnumidesigner' ),
			'own'    => __( 'Own', 'mnumidesigner' ),
			'global' => __( 'Global', 'mnumidesigner' ),
			'trash'  => __( 'Pending removal', 'mnumidesigner' ),
		);
	}

	/**
	 * Get available project types.
	 *
	 * @return array
	 */
	public function get_types() {
		return array(
			''             => __( 'Filter by template type', 'mnumidesigner' ),
			'custom'       => __( 'Custom', 'mnumidesigner' ),
			'album-2pages' => __( 'Album', 'mnumidesigner' ),
			'calendar-12m' => __( 'Calendar', 'mnumidesigner' ),
		);
	}
}

