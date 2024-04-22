<?php
/**
 * Importer class.
 *
 * @package Templify Import Templates
 */



/**
 * Block direct access to the main plugin file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Main plugin class with initialization tasks.
 */
class Importer_Templates {

    /**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * The instance of the Importer class.
	 *
	 * @var object
	 */
	public $importer;

	/**
	 * The resulting page's hook_suffix, or false if the user does not have the capability required.
	 *
	 * @var boolean or string
	 */
	private $plugin_page;

	/**
	 * Holds the verified import files.
	 *
	 * @var array
	 */
	public $import_files;

	/**
	 * The path of the log file.
	 *
	 * @var string
	 */
	public $log_file_path;

	/**
	 * The index of the `import_files` array (which import files was selected).
	 *
	 * @var int
	 */
	private $selected_index;

	/**
	 * The palette for the import.
	 *
	 * @var string
	 */
	private $selected_palette;

	/**
	 * The font for the import.
	 *
	 * @var string
	 */
	private $selected_font;

	/**
	 * The page for the import.
	 *
	 * @var string
	 */
	private $selected_page;

	/**
	 * The selected builder for import.
	 *
	 * @var string
	 */
	private $selected_builder;

	/**
	 * Import Single Override colors
	 *
	 * @var boolean
	 */
	private $override_colors;

	/**
	 * Import Single Override fonts
	 *
	 * @var boolean
	 */
	private $override_fonts;

	/**
	 * Import Shop/Cart/Checkout Pages.
	 *
	 * @var boolean
	 */
	private $ss = false;

	/**
	 * The paths of the actual import files to be used in the import.
	 *
	 * @var array
	 */
	private $selected_import_files;

	/**
	 * Holds any error messages, that should be printed out at the end of the import.
	 *
	 * @var string
	 */
	public $frontend_error_messages = array();

	/**
	 * Was the before content import already triggered?
	 *
	 * @var boolean
	 */
	private $before_import_executed = false;

	/**
	 * Make plugin page options available to other methods.
	 *
	 * @var array
	 */
	private $plugin_page_setup = array();

	/**
	 * Used to cache token authorization to prevent multiple
	 * remote requests to the licensing server in the same
	 * request lifecycle.
	 *
	 * @var null|bool
	 */
	private static $authorized_cache = null;

    /**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Construct function
	 */
	public function __construct() {
		// Set plugin constants.
		$this->include_plugin_files();
		add_action( 'init', array( $this, 'init_config' ) );
    }

    	/**
	 * Include all plugin files.
	 */
	private function include_plugin_files() {
        require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-importer.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-actions.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-widget-importer.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-customizer-importer.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-helpers.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-export-option.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-plugin-check.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-fluent.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-give.php';
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-elementor.php';
	}

    /**
	 * Templify Import
	 */
	public function init_config() {
			add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
	}


    /**
	 * Creates the plugin page and a submenu item in WP Appearance menu.
	 */
	public function create_admin_page() {
	
		add_menu_page(
            'Templify Import',            // Page title
            'Templify Import',            // Menu title
            'manage_options',              // Capability
            'templify-import',            // Menu slug
            '',                             // Callback function
            'dashicons-layout',            // Icon URL or Dashicons class
            30                             // Position
        );


        add_submenu_page(
            'templify-import',             // Parent slug
            'Home',             // Page title
            'Home',             // Menu title
            'manage_options',               // Capability
            'templify-import',             // Menu slug
            array( $this, 'templify_import_main_page')   // Callback function
        );


        add_submenu_page(
            'templify-import',            // Parent slug
            'Templify Template',   // Page title
            'Templify Template',                    // Menu title
            'manage_options',              // Capability
            'templify-import-templates',   // Menu slug
            array( $this, 'templify_import_templates_page')     // Callback function
        );


        add_submenu_page(
            'templify-import',            // Parent slug
            'Settings',   // Page title
            'Settings',                    // Menu title
            'manage_options',              // Capability
            'templify-import-settings',   // Menu slug
            array( $this, 'templify_import_settings_page')   // Callback function
        );
    
		
	}

    public function templify_import_main_page(){

    }


    
	/**
	 * Loads admin style sheets and scripts
	 */
	public function scripts() {
		// $using_network_enabled = false;
		// $is_network_admin      = is_multisite() && is_network_admin() ? true : false;
		// $network_enabled       = $this->is_network_authorize_enabled();
		// if ( $network_enabled && function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'templify-import-templates/templify-import-templates.php' ) ) {
		// 	$using_network_enabled = true;
		// }
		// $slug = class_exists( '\KadenceWP\KadenceBlocks\App' ) ? 'kadence-blocks' : 'templify-import-templates';
		// if ( class_exists( '\KadenceWP\KadenceBlocks\App' ) ) {
		// 	$token          = \KadenceWP\KadenceBlocks\StellarWP\Uplink\get_authorization_token( $slug );
		// 	$auth_url       = \KadenceWP\KadenceBlocks\StellarWP\Uplink\build_auth_url( apply_filters( 'kadence-blocks-auth-slug', $slug ), get_license_domain() );
		// } else {
		// 	$token          = get_authorization_token( $slug );
		// 	$auth_url       = build_auth_url( apply_filters( 'kadence-blocks-auth-slug', $slug ), get_license_domain() );
		// }
		// $license_key    = $this->get_current_license_key();
		// $disconnect_url = '';
		// $is_authorized  = false;
		// if ( ! empty( $token ) ) {
		// 	$is_authorized  = is_authorized( $license_key, $token, get_license_domain() );
		// }
		// if ( $is_authorized ) {
		// 	$disconnect_url = get_disconnect_url( apply_filters( 'kadence-blocks-auth-slug', $slug ) );
		// }
		$plugins = array (
			'woocommerce' => array(
				'title' => 'WooCommerce',
				'description' => __( 'WooCommerce is a flexible, open-source eCommerce solution built on WordPress.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'woocommerce/woocommerce.php' ),
				'src'   => 'repo',
			),
			'elementor' => array(
				'title' => 'Elementor',
				'state' => Plugin_Check::active_check( 'elementor/elementor.php' ),
				'src'   => 'repo',
			),
			'kadence-blocks' => array(
				'title' => 'Kadence Blocks',
				'description' => __( 'Kadence Blocks provides a collection powerful tools for the WordPress block editor.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'kadence-blocks/kadence-blocks.php' ),
				'src'   => 'repo',
			),
			'kadence-blocks-pro' => array(
				'title' => 'Kadence Block Pro',
				'description' => __( 'Kadence Blocks Pro is a plugin that adds additional features to Kadence Blocks.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'kadence-blocks-pro/kadence-blocks-pro.php' ),
				'src'   => 'bundle',
			),
			'kadence-pro' => array(
				'title' => 'Kadence Pro',
				'description' => __( 'Kadence Pro is a plugin that adds additional features to the Kadence Theme.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'kadence-pro/kadence-pro.php' ),
				'src'   => 'bundle',
			),
			'fluentform' => array(
				'title' => 'Fluent Forms',
				'src'   => 'repo',
				'state' => Plugin_Check::active_check( 'fluentform/fluentform.php' ),
			),
			'wpzoom-recipe-card' => array(
				'title' => 'Recipe Card Blocks by WPZOOM',
				'state' => Plugin_Check::active_check( 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php' ),
				'src'   => 'repo',
			),
			'learndash' => array(
				'title' => 'LearnDash',
				'description' => __( 'LearnDash is a learning management system (LMS) plugin for WordPress.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'sfwd-lms/sfwd_lms.php' ),
				'src'   => 'thirdparty',
			),
			'learndash-course-grid' => array(
				'title' => 'LearnDash Course Grid Addon',
				'description' => __( 'Add a course grid to any page or post.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'learndash-course-grid/learndash_course_grid.php' ),
				'src'   => 'thirdparty',
			),
			'lifterlms' => array(
				'title' => 'LifterLMS',
				'state' => Plugin_Check::active_check( 'lifterlms/lifterlms.php' ),
				'src'   => 'repo',
			),
			'tutor' => array(
				'title' => 'Tutor LMS',
				'state' => Plugin_Check::active_check( 'tutor/tutor.php' ),
				'src'   => 'repo',
			),
			'give' => array(
				'title' => 'GiveWP',
				'description' => __( 'GiveWP is the perfect online fundraising platform to increase your online donations.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'give/give.php' ),
				'src'   => 'repo',
			),
			'the-events-calendar' => array(
				'title' => 'The Events Calendar',
				'description' => __( 'The Events Calendar is a carefully crafted, extensible plugin that lets you easily manage and share events.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'the-events-calendar/the-events-calendar.php' ),
				'src'   => 'repo',
			),
			'event-tickets' => array(
				'title' => 'Event Tickets',
				'description' => __( 'Event Tickets provides a simple way for visitors to RSVP or purchase tickets to your events.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'event-tickets/event-tickets.php' ),
				'src'   => 'repo',
			),
			'orderable' => array(
				'title' => 'Orderable',
				'description' => __( 'Take restaurant orders online with Orderable. The WooCommerce plugin designed to help you manage your restaurant, your way – with no added fees!', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'orderable/orderable.php' ),
				'src'   => 'repo',
			),
			'restrict-content' => array(
				'title' => 'Restrict Content',
				'state' => Plugin_Check::active_check( 'restrict-content/restrictcontent.php' ),
				'src'   => 'repo',
			),
			'kadence-woo-extras' => array(
				'title' => 'Kadence Shop Kit',
				'description' => __( 'Kadence Shop Kit adds additional features to WooCommerce.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'kadence-woo-extras/kadence-woo-extras.php' ),
				'src'   => 'bundle',
			),
			'kadence-woocommerce-email-designer' => array(
				'title' => 'Kadence WooCommerce Email Designer',
				'description' => __( 'Kadence WooCommerce Email Designer lets you customize the default WooCommerce emails.', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'kadence-woocommerce-email-designer/kadence-woocommerce-email-designer.php' ),
				'src'   => 'repo',
			),
			'depicter' => array(
				'title' => 'Depicter Slider',
				'state' => Plugin_Check::active_check( 'depicter/depicter.php' ),
				'src'   => 'repo',
			),
			'seriously-simple-podcasting' => array(
				'title' => 'Seriously Simple Podcasting',
				'state' => Plugin_Check::active_check( 'seriously-simple-podcasting/seriously-simple-podcasting.php' ),
				'src'   => 'repo',
			),
			'better-wp-security' => array(
				'title' => 'Solid Security',
				'description' => __( 'Security, Two Factor Authentication, and Brute Force Protection', 'templify-import-templates' ),
				'state' => Plugin_Check::active_check( 'better-wp-security/better-wp-security.php' ),
				'src'   => 'repo',
			),
		);
		$palettes = array(
			array(
				'palette' => 'base',
				'colors' => array(
					'#2B6CB0',
					'#3B3B3B',
					'#E1E1E1',
					'#F7F7F7',
					'#ffffff',
				),
			),
			array(
				'palette' => 'orange',
				'colors' => array(
					'#e47b02',
					'#3E4C59',
					'#F3F4F7',
					'#F9F9FB',
					'#ffffff',
				),
			),
			array(
				'palette' => 'pinkish',
				'colors' => array(
					'#E21E51',
					'#032075',
					'#DEDDEB',
					'#EFEFF5',
					'#ffffff',
				),
			),
			array(
				'palette' => 'mint',
				'colors' => array(
					'#2cb1bc',
					'#133453',
					'#e0fcff',
					'#f5f7fa',
					'#ffffff',
				),
			),
			array(
				'palette' => 'green',
				'colors' => array(
					'#049f82',
					'#353535',
					'#EEEEEE',
					'#F7F7F7',
					'#ffffff',
				),
			),
			array(
				'palette' => 'rich',
				'colors' => array(
					'#295CFF',
					'#1C0D5A',
					'#E1EBEE',
					'#EFF7FB',
					'#ffffff',
				),
			),
			array(
				'palette' => 'fem',
				'colors' => array(
					'#D86C97',
					'#282828',
					'#f7dede',
					'#F6F2EF',
					'#ffffff',
				),
			),
			array(
				'palette' => 'teal',
				'colors' => array(
					'#7ACFC4',
					'#000000',
					'#F6E7BC',
					'#F9F7F7',
					'#ffffff',
				),
			),
			array(
				'palette' => 'bold',
				'colors' => array(
					'#000000',
					'#000000',
					'#F6E7BC',
					'#F9F7F7',
					'#ffffff',
				),
			),
			array(
				'palette' => 'hot',
				'colors' => array(
					'#FF5698',
					'#000000',
					'#FDEDEC',
					'#FDF6EE',
					'#ffffff',
				),
			),
			array(
				'palette' => 'darkmode',
				'colors' => array(
					'#3296ff',
					'#F7FAFC',
					'#2D3748',
					'#252C39',
					'#1a202c',
				),
			),
			array(
				'palette' => 'pinkishdark',
				'colors' => array(
					'#E21E51',
					'#EFEFF5',
					'#514D7C',
					'#221E5B',
					'#040037',
				),
			),
		);
		$fonts = array(
			array(
				'name' => 'Montserrat & Source Sans Pro',
				'font' => 'montserrat',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/montserrat.jpg',
			),
			array(
				'name' => 'Libre Franklin & Libre Baskerville',
				'font' => 'libre',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/libre.jpg',
			),
			array(
				'name' => 'Proza Libre & Open Sans',
				'font' => 'proza',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/proza.jpg',
			),
			array(
				'name' => 'Work Sans & Work Sans',
				'font' => 'worksans',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/worksans.jpg',
			),
			array(
				'name' => 'Josefin Sans & Lato',
				'font' => 'josefin',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/josefin.jpg',
			),
			array(
				'name' => 'Oswald & Open Sans',
				'font' => 'oswald',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/oswald.jpg',
			),
			array(
				'name' => 'Nunito & Roboto',
				'font' => 'nunito',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/nunito.jpg',
			),
			array(
				'name' => 'Rubik & Karla',
				'font' => 'rubik',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/rubik.jpg',
			),
			array(
				'name' => 'Lora & Merriweather',
				'font' => 'lora',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/lora.jpg',
			),
			array(
				'name' => 'Playfair Display & Raleway',
				'font' => 'playfair',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/playfair.jpg',
			),
			array(
				'name' => 'Antic Didone & Raleway',
				'font' => 'antic',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/antic.jpg',
			),
			array(
				'name' => 'Gilda Display & Raleway',
				'font' => 'gilda',
				'img'  => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/fonts/gilda.jpg',
			),
		);
		$old_data = get_option( '_kadence_starter_templates_last_import_data', array() );
		$has_content = false;
		$has_previous = false;
		if ( ! empty( $old_data ) ) {
			$has_content  = true;
			$has_previous = true;
		}
		// Check for multiple posts.
		if ( false === $has_content ) {
			$has_content = ( 1 < wp_count_posts()->publish ? true : false );
		}
		if ( false === $has_content ) {
			// Check for multiple pages.
			$has_content = ( 1 < wp_count_posts( 'page' )->publish ? true : false );
		}
		if ( false === $has_content ) {
			// Check for multiple images.
			$has_content = ( 0 < wp_count_posts( 'attachment' )->inherit ? true : false );
		}
		$pro_data = $this->get_current_license_data();
		$current_user     = wp_get_current_user();
		$user_email       = $current_user->user_email;
		$show_builder_choice = ( 'active' === $plugins['elementor']['state'] ? true : false );
		$subscribed       = ( class_exists( 'Kadence_Theme_Pro' ) || ! empty( apply_filters( 'kadence_starter_templates_custom_array', array() ) ) ? true : get_option( 'kadence_starter_templates_subscribe' ) );
		wp_enqueue_media();
		$kadence_starter_templates_meta = $this->get_asset_file( 'dist/starter-templates' );
		wp_enqueue_style( 'templify-import-templates', TEMPLIFY_IMPORT_TEMPLATES_URL . 'dist/starter-templates.css', array( 'wp-components' ), KADENCE_STARTER_TEMPLATES_VERSION );
		wp_enqueue_script( 'templify-import-templates', TEMPLIFY_IMPORT_TEMPLATES_URL . 'dist/starter-templates.js', array_merge( array( 'wp-api', 'wp-components', 'wp-plugins', 'wp-edit-post' ), $kadence_starter_templates_meta['dependencies'] ), $kadence_starter_templates_meta['version'], true );
		wp_localize_script(
			'templify-import-templates',
			'kadenceStarterParams',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'homeUrl'             => home_url( '/' ),
				'pagesUrl'            => admin_url( 'edit.php?post_type=page' ),
				'ajax_nonce'           => wp_create_nonce( 'kadence-ajax-verification' ),
				'isKadence'            => class_exists( 'Kadence\Theme' ),
				'livePreviewStyles'    => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/css/live-preview-base.css?ver=' . KADENCE_STARTER_TEMPLATES_VERSION,
				'ctemplates'           => apply_filters( 'kadence_custom_child_starter_templates_enable', false ),
				'custom_icon'          => apply_filters( 'kadence_custom_child_starter_templates_logo', '' ),
				'custom_name'          => apply_filters( 'kadence_custom_child_starter_templates_name', '' ),
				'plugins'              => apply_filters( 'kadence_starter_templates_plugins_array', $plugins ),
				'palettes'             => apply_filters( 'kadence_starter_templates_palettes_array', $palettes ),
				'fonts'                => apply_filters( 'kadence_starter_templates_fonts_array', $fonts ),
				'logo'                 => esc_attr( TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/kadence_logo.png' ),
				'svgMaskPath'          => defined( 'KADENCE_BLOCKS_URL' ) ? KADENCE_BLOCKS_URL . 'includes/assets/images/masks/' : KADENCE_STARTER_TEMPLATES_URL . 'assets/images/masks/',
				'has_content'          => $has_content,
				'has_previous'         => $has_previous,
				'starterSettings'      => get_option( 'kadence_starter_templates_config' ),
				'proData'              => $pro_data,
				'notice'               => esc_html__( 'Please Note: Full site importing is designed for new/empty sites with no content. Your site customizer settings, widgets, menus will all be overridden.', 'templify-import-templates' ),
				'notice_previous'      => esc_html( 'Please Note: Full site importing is designed for new/empty sites with no content. Your site customizer settings, widgets, menus will all be overridden. It is recommended that you enable "Delete Previously Imported Posts and Images" if you are testing out different starter templates.'),
				'remove_progress'      => esc_html__( 'Removing Past Imported Content', 'templify-import-templates' ),
				'subscribe_progress'   => esc_html__( 'Getting Started', 'templify-import-templates' ),
				'plugin_progress'      => esc_html__( 'Checking/Installing/Activating Required Plugins', 'templify-import-templates' ),
				'content_progress'     => esc_html__( 'Importing Content...', 'templify-import-templates' ),
				'content_new_progress' => esc_html__( 'Importing Content... Creating pages.', 'templify-import-templates' ),
				'content_newer_progress' => esc_html__( 'Importing Content... Downloading images.', 'templify-import-templates' ),
				'content_newest_progress' => esc_html__( 'Importing Content... Still Importing.', 'templify-import-templates' ),
				'widgets_progress'     => esc_html__( 'Importing Widgets...', 'templify-import-templates' ),
				'customizer_progress'  => esc_html__( 'Importing Customizer Settings...', 'templify-import-templates' ),
				'user_email'           => $user_email,
				'subscribed'           => $subscribed,
				'openBuilder'          => $show_builder_choice,
				'isAuthorized'        => $is_authorized,
				'licenseKey'          => $license_key,
				'authUrl'             => esc_url( $auth_url ),
				'disconnectUrl'       => esc_url( $disconnect_url ),
				'isNetworkAdmin'      => $is_network_admin,
				'isNetworkEnabled'    => $using_network_enabled,
				'blocksActive'        => class_exists( '\KadenceWP\KadenceBlocks\App' ) ? true : false,
			)
		);
	}


	/**
	 * Get the asset file produced by wp scripts.
	 *
	 * @param string $filepath the file path.
	 * @return array
	 */
	public function get_asset_file( $filepath ) {
		$asset_path = KADENCE_STARTER_TEMPLATES_PATH . $filepath . '.asset.php';
		return file_exists( $asset_path )
			? include $asset_path
			: array(
				'dependencies' => array( 'lodash', 'react', 'react-dom', 'wp-block-editor', 'wp-blocks', 'wp-data', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-primitives', 'wp-api' ),
				'version'      => KADENCE_STARTER_TEMPLATES_VERSION,
			);
	}


	/**
	 * Check if a setting is registered.
	 *
	 * @param string $option_name the option group.
	 */
	public function is_setting_registered( $option_name ) {
		global $wp_registered_settings;
		return isset( $wp_registered_settings[ $option_name ] );
	}


	
	/**
	 * Register settings
	 */
	public function load_api_settings() {
		register_setting(
			'kadence_starter_templates_config',
			'kadence_starter_templates_config',
			array(
				'type'              => 'string',
				'description'       => __( 'Config Kadence Starter Templates', 'kadence-blocks' ),
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
			)
		);
		if ( ! $this->is_setting_registered( 'kadence_blocks_prophecy' ) ) {
			register_setting(
				'kadence_blocks_prophecy',
				'kadence_blocks_prophecy',
				array(
					'type'              => 'string',
					'description'       => __( 'Config Kadence Block Prophecy AI', 'kadence-blocks' ),
					'sanitize_callback' => 'sanitize_text_field',
					'show_in_rest'      => true,
					'default'           => '',
				)
			);
		}
	}


    
	/**
	 * Plugin page display.
	 * Output (HTML) is in another file.
	 */
	public function templify_import_templates_page() {
		?>
		<div class="wrap templify_theme_starter_dash">
			<div class="templify_theme_starter_dashboard">
				<h2 class="notices" style="display:none;"></h2>
				<?php settings_errors(); ?>
				<div class="page-grid">
					<div class="templify_importer_dashboard_main">
					</div>
			</div>
		</div>
		<?php
	}


    public function templify_import_settings_page(){
        
    }


	
	/**
	 * Getter function to retrieve the private log_file_path value.
	 *
	 * @return string The log_file_path value.
	 */
	public function get_log_file_path() {
		return $this->log_file_path;
	}


}

Importer_Templates::get_instance();
