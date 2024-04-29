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

			if ( is_admin() ) {
				// Ajax Calls.
				add_action( 'wp_ajax_templify_import_demo_data', array( $this, 'import_demo_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_import_initial', array( $this, 'initial_install_ajax_callback' ) );
				add_action( 'wp_ajax_templify_import_install_plugins', array( $this, 'install_plugins_ajax_callback' ) );
				add_action( 'wp_ajax_templify_import_customizer_data', array( $this, 'import_customizer_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_after_import_data', array( $this, 'after_all_import_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_import_single_data', array( $this, 'import_demo_single_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_remove_past_import_data', array( $this, 'remove_past_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_import_subscribe', array( $this, 'subscribe_ajax_callback' ) );
				add_action( 'wp_ajax_templify_check_plugin_data', array( $this, 'check_plugin_data_ajax_callback' ) );
				add_action( 'wp_ajax_templify_starter_dismiss_notice', array( $this, 'ajax_dismiss_starter_notice' ) );
			}
	
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
		$old_data = get_option( '_templify_import_templates_last_import_data', array() );
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
		$subscribed       = ( class_exists( 'Kadence_Theme_Pro' ) || ! empty( apply_filters( 'templify_import_templates_custom_array', array() ) ) ? true : get_option( 'templify_import_templates_subscribe' ) );
		wp_enqueue_media();
		$templify_import_templates_meta = $this->get_asset_file( 'dist/importer-templates' );
		wp_enqueue_style( 'templify-import-templates', TEMPLIFY_IMPORT_TEMPLATES_URL . 'dist/importer-templates.css', array( 'wp-components' ), TEMPLIFY_IMPORT_TEMPLATES_VERSION );
		wp_enqueue_script( 'templify-import-templates', TEMPLIFY_IMPORT_TEMPLATES_URL . 'dist/importer-templates.js', array_merge( array( 'wp-api', 'wp-components', 'wp-plugins', 'wp-edit-post' ), $templify_import_templates_meta['dependencies'] ), $templify_import_templates_meta['version'], true );
		wp_localize_script(
			'templify-import-templates',
			'kadenceStarterParams',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'homeUrl'             => home_url( '/' ),
				'pagesUrl'            => admin_url( 'edit.php?post_type=page' ),
				'ajax_nonce'           => wp_create_nonce( 'kadence-ajax-verification' ),
				'isKadence'            => class_exists( 'Kadence\Theme' ),
				'livePreviewStyles'    => TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/css/live-preview-base.css?ver=' . TEMPLIFY_IMPORT_TEMPLATES_VERSION,
				'ctemplates'           => apply_filters( 'kadence_custom_child_starter_templates_enable', false ),
				'custom_icon'          => apply_filters( 'kadence_custom_child_starter_templates_logo', '' ),
				'custom_name'          => apply_filters( 'kadence_custom_child_starter_templates_name', '' ),
				'plugins'              => apply_filters( 'templify_import_templates_plugins_array', $plugins ),
				'palettes'             => apply_filters( 'templify_import_templates_palettes_array', $palettes ),
				'fonts'                => apply_filters( 'templify_import_templates_fonts_array', $fonts ),
				'logo'                 => esc_attr( TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/kadence_logo.png' ),
				'svgMaskPath'          => defined( 'TEMPLIFY_BLOCKS_URL' ) ? TEMPLIFY_BLOCKS_URL . 'includes/assets/images/masks/' : TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/images/masks/',
				'has_content'          => $has_content,
				'has_previous'         => $has_previous,
				'starterSettings'      => get_option( 'templify_import_templates_config' ),
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
		$asset_path = TEMPLIFY_IMPORT_TEMPLATES_PATH . $filepath . '.asset.php';
		return file_exists( $asset_path )
			? include $asset_path
			: array(
				'dependencies' => array( 'lodash', 'react', 'react-dom', 'wp-block-editor', 'wp-blocks', 'wp-data', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-primitives', 'wp-api' ),
				'version'      => TEMPLIFY_IMPORT_TEMPLATES_VERSION,
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
			'templify_import_templates_config',
			'templify_import_templates_config',
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


	/**
	 * Main AJAX callback function for:
	 * 1). prepare import files (uploaded or predefined via filters)
	 * 2). execute 'before content import' actions (before import WP action)
	 * 3). import content
	 * 4). execute 'after content import' actions (before widget import WP action, widget import, customizer import, after import WP action)
	 */
	public function import_demo_single_data_ajax_callback() {
		// Try to update PHP memory limit (so that it does not run out of it).
		ini_set( 'memory_limit', apply_filters( 'templify-import-templates/import_memory_limit', '350M' ) );

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();
		// Is this a new AJAX call to continue the previous import?
		$use_existing_importer_data = $this->use_existing_importer_data();

		if ( ! $use_existing_importer_data ) {
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			if ( apply_filters( 'templify_import_templates_save_log_files', false ) ) {
				// Define log file path.
				$this->log_file_path = Helpers::get_log_path();
			} else {
				$this->log_file_path = '';
			}
			// Get selected file index or set it to 0.
			$this->selected_index = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
			$this->selected_builder = empty( $_POST['builder'] ) ? 'blocks' : sanitize_text_field( $_POST['builder'] );
			$this->selected_page = empty( $_POST['page_id'] ) ? '' : sanitize_text_field( $_POST['page_id'] );
			$this->override_colors = 'true' === $_POST['override_colors'] ? true : false;
			$this->override_fonts = 'true' === $_POST['override_fonts'] ? true : false;
			$this->selected_palette = empty( $_POST['palette'] ) ? '' : sanitize_text_field( $_POST['palette'] );
			$this->selected_font    = empty( $_POST['font'] ) ? '' : sanitize_text_field( $_POST['font'] );

			if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $this->selected_index ] ) ) ) {
				$template_database  = Template_Database_Importer::get_instance();
				$this->import_files = $template_database->get_importer_files( $this->selected_index, $this->selected_builder );
			}
			if ( ! isset( $this->import_files[ $this->selected_index ] ) ) {
				wp_send_json_error();
			}
			/**
			 * 1). Prepare import files.
			 * Predefined import files via filter: templify-import-templates/import_files
			 */
			if ( ! empty( $this->import_files[ $this->selected_index ] ) && ! empty( $this->selected_page ) && isset( $this->import_files[ $this->selected_index ]['pages'] ) && isset( $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ] ) ) { // Use predefined import files from wp filter: templify-import-templates/import_files.

				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_file( $this->import_files[ $this->selected_index ], $this->selected_page );

				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'templify-import-templates' )
					);
				}
				if ( apply_filters( 'templify_import_templates_save_log_files', false ) ) {
					// Add this message to log file.
					$log_added = Helpers::append_to_file(
						sprintf(
							__( 'The import files for: %s were successfully downloaded!', 'templify-import-templates' ),
							$this->import_files[ $this->selected_index ]['slug']
						) . Helpers::import_file_info( $this->selected_import_files ),
						$this->log_file_path,
						esc_html__( 'Downloaded files' , 'templify-import-templates' )
					);
				}
			} else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'templify-import-templates' ) );
			}
		}

		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_import_data_transient( $this->get_current_importer_data() );

		// If elementor make sure the defaults are off.
		$elementor = false;
		if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
			update_option( 'elementor_disable_color_schemes', 'yes' );
			update_option( 'elementor_disable_typography_schemes', 'yes' );
			$elementor = true;
			if ( class_exists( 'Kadence\Theme' ) ) {
				$component = \Kadence\Theme::instance()->components['elementor'];
				if ( $component ) {
					$component->elementor_add_theme_colors();
				}
			}
		}

		/**
		 * 3). Import content (if the content XML file is set for this import).
		 * Returns any errors greater then the "warning" logger level, that will be displayed on front page.
		 */
		$new_post = '';
		if ( ! empty( $this->selected_import_files['content'] ) ) {
			$meta = ( ! empty( $this->import_files[ $this->selected_index ] ) && ! empty( $this->selected_page ) && isset( $this->import_files[ $this->selected_index ]['pages'] ) && isset( $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ] ) && isset( $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ]['meta'] ) ? $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ]['meta'] : 'inherit' );
			$logger = $this->importer->import_content( $this->selected_import_files['content'], true, $meta, $elementor );
			if ( is_object( $logger ) && property_exists( $logger, 'error_output' ) && $logger->error_output ) {
				$this->append_to_frontend_error_messages( $logger->error_output );
			} elseif ( is_object( $logger ) && $logger->messages ) {
				$messages = $logger->messages;
				if ( isset( $messages[1] ) && isset( $messages[1]['level'] ) && 'debug' == $messages[1]['level'] && isset( $messages[1]['message'] ) && ! empty( $messages[1]['message'] ) ) {
					$pieces   = explode( ' ', $messages[1]['message'] );
					$new_post = array_pop( $pieces );
				}
			}
		}

		if ( $this->override_colors ) {
			if ( $this->selected_palette && ! empty( $this->selected_palette ) ) {
				$palette_presets = json_decode( '{"base":[{"color":"#2B6CB0"},{"color":"#265E9A"},{"color":"#222222"},{"color":"#3B3B3B"},{"color":"#515151"},{"color":"#626262"},{"color":"#E1E1E1"},{"color":"#F7F7F7"},{"color":"#ffffff"}],"bright":[{"color":"#255FDD"},{"color":"#00F2FF"},{"color":"#1A202C"},{"color":"#2D3748"},{"color":"#4A5568"},{"color":"#718096"},{"color":"#EDF2F7"},{"color":"#F7FAFC"},{"color":"#ffffff"}],"darkmode":[{"color":"#3296ff"},{"color":"#003174"},{"color":"#ffffff"},{"color":"#f7fafc"},{"color":"#edf2f7"},{"color":"#cbd2d9"},{"color":"#2d3748"},{"color":"#252c39"},{"color":"#1a202c"}],"orange":[{"color":"#e47b02"},{"color":"#ed8f0c"},{"color":"#1f2933"},{"color":"#3e4c59"},{"color":"#52606d"},{"color":"#7b8794"},{"color":"#f3f4f7"},{"color":"#f9f9fb"},{"color":"#ffffff"}],"pinkish":[{"color":"#E21E51"},{"color":"#4d40ff"},{"color":"#040037"},{"color":"#032075"},{"color":"#514d7c"},{"color":"#666699"},{"color":"#deddeb"},{"color":"#efeff5"},{"color":"#f8f9fa"}],"pinkishdark":[{"color":"#E21E51"},{"color":"#4d40ff"},{"color":"#f8f9fa"},{"color":"#efeff5"},{"color":"#deddeb"},{"color":"#c3c2d6"},{"color":"#514d7c"},{"color":"#221e5b"},{"color":"#040037"}],"green":[{"color":"#049f82"},{"color":"#008f72"},{"color":"#222222"},{"color":"#353535"},{"color":"#454545"},{"color":"#676767"},{"color":"#eeeeee"},{"color":"#f7f7f7"},{"color":"#ffffff"}],"fire":[{"color":"#dd6b20"},{"color":"#cf3033"},{"color":"#27241d"},{"color":"#423d33"},{"color":"#504a40"},{"color":"#625d52"},{"color":"#e8e6e1"},{"color":"#faf9f7"},{"color":"#ffffff"}],"mint":[{"color":"#2cb1bc"},{"color":"#13919b"},{"color":"#0f2a43"},{"color":"#133453"},{"color":"#587089"},{"color":"#829ab1"},{"color":"#e0fcff"},{"color":"#f5f7fa"},{"color":"#ffffff"}],"rich":[{"color":"#295CFF"},{"color":"#0E94FF"},{"color":"#1C0D5A"},{"color":"#3D3D3D"},{"color":"#57575D"},{"color":"#636363"},{"color":"#E1EBEE"},{"color":"#EFF7FB"},{"color":"#ffffff"}],"fem":[{"color":"#D86C97"},{"color":"#282828"},{"color":"#282828"},{"color":"#333333"},{"color":"#4d4d4d"},{"color":"#646464"},{"color":"#f7dede"},{"color":"#F6F2EF"},{"color":"#ffffff"}],"hot":[{"color":"#FF5698"},{"color":"#000000"},{"color":"#020202"},{"color":"#020202"},{"color":"#4E4E4E"},{"color":"#808080"},{"color":"#FDEDEC"},{"color":"#FDF6EE"},{"color":"#ffffff"}],"bold":[{"color":"#000000"},{"color":"#D1A155"},{"color":"#000000"},{"color":"#010101"},{"color":"#111111"},{"color":"#282828"},{"color":"#F6E7BC"},{"color":"#F9F7F7"},{"color":"#ffffff"}],"teal":[{"color":"#7ACFC4"},{"color":"#044355"},{"color":"#000000"},{"color":"#010101"},{"color":"#111111"},{"color":"#282828"},{"color":"#F5ECE5"},{"color":"#F9F7F7"},{"color":"#ffffff"}]}', true );
				if ( isset( $palette_presets[ $this->selected_palette ] ) ) {
					$default = json_decode( '{"palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"second-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"third-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"active":"palette"}', true );
					$default['palette'][0]['color'] = $palette_presets[ $this->selected_palette ][0]['color'];
					$default['palette'][1]['color'] = $palette_presets[ $this->selected_palette ][1]['color'];
					$default['palette'][2]['color'] = $palette_presets[ $this->selected_palette ][2]['color'];
					$default['palette'][3]['color'] = $palette_presets[ $this->selected_palette ][3]['color'];
					$default['palette'][4]['color'] = $palette_presets[ $this->selected_palette ][4]['color'];
					$default['palette'][5]['color'] = $palette_presets[ $this->selected_palette ][5]['color'];
					$default['palette'][6]['color'] = $palette_presets[ $this->selected_palette ][6]['color'];
					$default['palette'][7]['color'] = $palette_presets[ $this->selected_palette ][7]['color'];
					$default['palette'][8]['color'] = $palette_presets[ $this->selected_palette ][8]['color'];
					update_option( 'kadence_global_palette', json_encode( $default ) );
				}
			} else {
				/**
				 * Execute the customizer import actions.
				 */
				do_action( 'templify-import-templates/customizer_import_color_only_execution', $this->selected_import_files );
			}
		}
		if ( $this->override_fonts ) {
			if ( class_exists( 'Kadence\Theme' ) ) {
				if ( $this->selected_font && ! empty( $this->selected_font ) ) {
					switch ( $this->selected_font ) {
						case 'montserrat':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Montserrat';
							$current['google']  = true;
							$current['variant'] = array( '100', '100italic', '200', '200italic', '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
							set_theme_mod( 'heading_font', $current );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Source Sans Pro';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
						case 'playfair':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Playfair Display';
							$current['google']  = true;
							$current['variant'] = array( 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
							set_theme_mod( 'heading_font', $current );
							$h1_font = \Kadence\kadence()->option( 'h1_font' );
							$h1_font['weight'] = 'normal';
							$h1_font['variant'] = 'regualar';
							set_theme_mod( 'h1_font', $h1_font );
							$h2_font = \Kadence\kadence()->option( 'h2_font' );
							$h2_font['weight'] = 'normal';
							$h2_font['variant'] = 'regualar';
							set_theme_mod( 'h2_font', $h2_font );
							$h3_font = \Kadence\kadence()->option( 'h3_font' );
							$h3_font['weight'] = 'normal';
							$h3_font['variant'] = 'regualar';
							set_theme_mod( 'h3_font', $h3_font );
							$h4_font = \Kadence\kadence()->option( 'h4_font' );
							$h4_font['weight'] = 'normal';
							$h4_font['variant'] = 'regualar';
							set_theme_mod( 'h4_font', $h4_font );
							$h5_font = \Kadence\kadence()->option( 'h5_font' );
							$h5_font['weight'] = 'normal';
							$h5_font['variant'] = 'regualar';
							set_theme_mod( 'h5_font', $h5_font );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Raleway';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
						case 'oswald':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Oswald';
							$current['google']  = true;
							$current['variant'] = array( '200', '300', 'regular', '500', '600', '700' );
							set_theme_mod( 'heading_font', $current );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Open Sans';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
						case 'antic':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Antic Didone';
							$current['google']  = true;
							$current['variant'] = array( 'regular' );
							set_theme_mod( 'heading_font', $current );
							$h1_font = \Kadence\kadence()->option( 'h1_font' );
							$h1_font['weight'] = 'normal';
							$h1_font['variant'] = 'regualar';
							set_theme_mod( 'h1_font', $h1_font );
							$h2_font = \Kadence\kadence()->option( 'h2_font' );
							$h2_font['weight'] = 'normal';
							$h2_font['variant'] = 'regualar';
							set_theme_mod( 'h2_font', $h2_font );
							$h3_font = \Kadence\kadence()->option( 'h3_font' );
							$h3_font['weight'] = 'normal';
							$h3_font['variant'] = 'regualar';
							set_theme_mod( 'h3_font', $h3_font );
							$h4_font = \Kadence\kadence()->option( 'h4_font' );
							$h4_font['weight'] = 'normal';
							$h4_font['variant'] = 'regualar';
							set_theme_mod( 'h4_font', $h4_font );
							$h5_font = \Kadence\kadence()->option( 'h5_font' );
							$h5_font['weight'] = 'normal';
							$h5_font['variant'] = 'regualar';
							set_theme_mod( 'h5_font', $h5_font );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Raleway';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
						case 'gilda':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Gilda Display';
							$current['google']  = true;
							$current['variant'] = array( 'regular' );
							set_theme_mod( 'heading_font', $current );
							$h1_font = \Kadence\kadence()->option( 'h1_font' );
							$h1_font['weight'] = 'normal';
							$h1_font['variant'] = 'regualar';
							set_theme_mod( 'h1_font', $h1_font );
							$h2_font = \Kadence\kadence()->option( 'h2_font' );
							$h2_font['weight'] = 'normal';
							$h2_font['variant'] = 'regualar';
							set_theme_mod( 'h2_font', $h2_font );
							$h3_font = \Kadence\kadence()->option( 'h3_font' );
							$h3_font['weight'] = 'normal';
							$h3_font['variant'] = 'regualar';
							set_theme_mod( 'h3_font', $h3_font );
							$h4_font = \Kadence\kadence()->option( 'h4_font' );
							$h4_font['weight'] = 'normal';
							$h4_font['variant'] = 'regualar';
							set_theme_mod( 'h4_font', $h4_font );
							$h5_font = \Kadence\kadence()->option( 'h5_font' );
							$h5_font['weight'] = 'normal';
							$h5_font['variant'] = 'regualar';
							set_theme_mod( 'h5_font', $h5_font );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Raleway';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
						case 'cormorant':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Cormorant Garamond';
							$current['google']  = true;
							$current['variant'] = array( '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic' );
							set_theme_mod( 'heading_font', $current );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Proza Libre';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
						case 'libre':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Libre Franklin';
							$current['google']  = true;
							$current['variant'] = array( '100', '100italic', '200', '200italic', '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
							set_theme_mod( 'heading_font', $current );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Libre Baskerville';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
	
						case 'lora':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Lora';
							$current['google']  = true;
							$current['variant'] = array( 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic' );
							set_theme_mod( 'heading_font', $current );
							$h1_font = \Kadence\kadence()->option( 'h1_font' );
							$h1_font['weight'] = 'normal';
							$h1_font['variant'] = 'regualar';
							set_theme_mod( 'h1_font', $h1_font );
							$h2_font = \Kadence\kadence()->option( 'h2_font' );
							$h2_font['weight'] = 'normal';
							$h2_font['variant'] = 'regualar';
							set_theme_mod( 'h2_font', $h2_font );
							$h3_font = \Kadence\kadence()->option( 'h3_font' );
							$h3_font['weight'] = 'normal';
							$h3_font['variant'] = 'regualar';
							set_theme_mod( 'h3_font', $h3_font );
							$h4_font = \Kadence\kadence()->option( 'h4_font' );
							$h4_font['weight'] = 'normal';
							$h4_font['variant'] = 'regualar';
							set_theme_mod( 'h4_font', $h4_font );
							$h5_font = \Kadence\kadence()->option( 'h5_font' );
							$h5_font['weight'] = 'normal';
							$h5_font['variant'] = 'regualar';
							set_theme_mod( 'h5_font', $h5_font );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Merriweather';
							$body['google'] = true;
							$body['weight'] = '300';
							$body['variant'] = '300';
							set_theme_mod( 'base_font', $body );
							break;
	
						case 'proza':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Proza Libre';
							$current['google']  = true;
							$current['variant'] = array( 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic' );
							set_theme_mod( 'heading_font', $current );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Open Sans';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
	
						case 'worksans':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Work Sans';
							$current['google']  = true;
							$current['variant'] = array( '100', '100italic', '200', '200italic', '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
							set_theme_mod( 'heading_font', $current );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Work Sans';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
	
						case 'josefin':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Josefin Sans';
							$current['google']  = true;
							$current['variant'] = array( '100', '100italic', '200', '200italic', '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic' );
							set_theme_mod( 'heading_font', $current );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Lato';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
	
						case 'nunito':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Nunito';
							$current['google']  = true;
							$current['variant'] = array( '200', '200italic', '300', '300italic', 'regular', 'italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
							set_theme_mod( 'heading_font', $current );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Roboto';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
						case 'rubik':
							$current = \Kadence\kadence()->option( 'heading_font' );
							$current['family']  = 'Rubik';
							$current['google']  = true;
							$current['variant'] = array( '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
							set_theme_mod( 'heading_font', $current );
							$body = \Kadence\kadence()->option( 'base_font' );
							$body['family'] = 'Karla';
							$body['google'] = true;
							set_theme_mod( 'base_font', $body );
							break;
					}
				} else {
					/**
					 * Execute the customizer import actions.
					 */
					do_action( 'templify-import-templates/customizer_import_font_only_execution', $this->selected_import_files );
				}
				foreach ( array( 'h1_font', 'h2_font', 'h3_font', 'h4_font', 'h5_font', 'h6_font', 'h5_font', 'title_above_font' ) as $value ) {
					$font_settings = \Kadence\kadence()->option( $value );
					$font_settings['family'] = 'inherit';
					$font_settings['google'] = false;
					set_theme_mod( $value, $font_settings );
				}
			}
		}

		// If elementor make sure the defaults are off.
		if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
			if ( class_exists( 'Elementor\Plugin' ) ) {
				\Elementor\Plugin::instance()->files_manager->clear_cache();
			}
		}

		// Send a JSON response with final report.
		$this->final_response( $new_post );
	}


	/**
	 * Main AJAX callback function for:
	 * 1). prepare import files (uploaded or predefined via filters)
	 * 2). execute 'before content import' actions (before import WP action)
	 * 3). import content
	 * 4). execute 'after content import' actions (before widget import WP action, widget import, customizer import, after import WP action)
	 */
	public function import_demo_data_ajax_callback() {
		// Try to update PHP memory limit (so that it does not run out of it).
		ini_set( 'memory_limit', apply_filters( 'templify-import-templates/import_memory_limit', '350M' ) );
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();
		// Is this a new AJAX call to continue the previous import?
		$use_existing_importer_data = $this->use_existing_importer_data();
		if ( ! $use_existing_importer_data ) {
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			if ( apply_filters( 'templify_import_templates_save_log_files', false ) ) {
				// Define log file path.
				$this->log_file_path = Helpers::get_log_path();
			} else {
				$this->log_file_path = '';
			}

			// Get selected file index or set it to 0.
			$this->selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
			$this->selected_palette = empty( $_POST['palette'] ) ? '' : sanitize_text_field( $_POST['palette'] );
			$this->selected_font    = empty( $_POST['font'] ) ? '' : sanitize_text_field( $_POST['font'] );
			$this->selected_builder = empty( $_POST['builder'] ) ? 'blocks' : sanitize_text_field( $_POST['builder'] );

			if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $this->selected_index ] ) ) ) {
				$template_database  = Template_Database_Importer::get_instance();
				$this->import_files = $template_database->get_importer_files( $this->selected_index, $this->selected_builder );
			}
			if ( ! isset( $this->import_files[ $this->selected_index ] ) ) {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'templify-import-templates' ) );
			}
			/**
			 * 1). Prepare import files.
			 * Predefined import files via filter: templify-import-templates/import_files
			 */
			if ( ! empty( $this->import_files[ $this->selected_index ] ) ) { // Use predefined import files from wp filter: templify-import-templates/import_files.

				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_files( $this->import_files[ $this->selected_index ] );

				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'templify-import-templates' )
					);
				}
				if ( apply_filters( 'templify_import_templates_save_log_files', false ) ) {
					// Add this message to log file.
					$log_added = Helpers::append_to_file(
						sprintf(
							__( 'The import files for: %s were successfully downloaded!', 'templify-import-templates' ),
							$this->import_files[ $this->selected_index ]['slug']
						) . Helpers::import_file_info( $this->selected_import_files ),
						$this->log_file_path,
						esc_html__( 'Downloaded files' , 'templify-import-templates' )
					);
				}
			} else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'templify-import-templates' ) );
			}
		}
		// if ( class_exists( 'woocommerce' ) && isset( $this->import_files[ $this->selected_index ]['ecommerce'] ) && $this->import_files[ $this->selected_index ]['ecommerce'] && ! $this->import_woo_pages ) {
		// 	add_filter( 'stop_importing_woo_pages', '__return_true' );
		// }
		// If elementor make sure the defaults are off.
		if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
			update_option( 'elementor_disable_color_schemes', 'yes' );
			update_option( 'elementor_disable_typography_schemes', 'yes' );
		}
		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_import_data_transient( $this->get_current_importer_data() );
		if ( ! $this->before_import_executed ) {
			$this->before_import_executed = true;

			/**
			 * Save Current Theme mods for a potential undo.
			 */
			update_option( '_templify_import_templates_old_customizer', get_option( 'theme_mods_' . get_option( 'stylesheet' ) ) );
			// Save Import data for use if we need to reset it.
			update_option( '_templify_import_templates_last_import_data', $this->import_files[ $this->selected_index ], 'no' );
			/**
			 * 2). Execute the actions hooked to the 'templify-import-templates/before_content_import_execution' action:
			 *
			 * Default actions:
			 * 1 - Before content import WP action (with priority 10).
			 */
			/**
			 * Clean up default contents.
			 */
			$hello_world = $this->get_page_by_title( 'Hello World', OBJECT, 'post' );
			if ( $hello_world ) {
				wp_delete_post( $hello_world->ID, true );// Hello World.
			}
			$sample_page = $this->get_page_by_title( 'Sample Page' );
			if ( $sample_page ) {
				wp_delete_post( $sample_page->ID, true ); // Sample Page.
			}
			wp_delete_comment( 1, true ); // WordPress comment.
			/**
			 * Clean up default woocommerce.
			 */
			$woopages = array(
				'woocommerce_shop_page_id'      => 'shop',
				'woocommerce_cart_page_id'      => 'cart',
				'woocommerce_checkout_page_id'  => 'checkout',
				'woocommerce_myaccount_page_id' => 'my-account',
			);
			foreach ( $woopages as $woo_page_option => $woo_page_slug ) {
				if ( get_option( $woo_page_option ) ) {
					wp_delete_post( get_option( $woo_page_option ), true );
				}
			}
			// Move All active widgets into inactive.
			$sidebars = wp_get_sidebars_widgets();
			if ( is_array( $sidebars ) ) {
				foreach ( $sidebars as $sidebar_id => $sidebar_widgets ) {
					if ( 'wp_inactive_widgets' === $sidebar_id ) {
						continue;
					}
					if ( is_array( $sidebar_widgets ) && ! empty( $sidebar_widgets ) ) {
						foreach ( $sidebar_widgets as $i => $single_widget ) {
							$sidebars['wp_inactive_widgets'][] = $single_widget;
							unset( $sidebars[ $sidebar_id ][ $i ] );
						}
					}
				}
			}
			wp_set_sidebars_widgets( $sidebars );
			// Reset to default settings values.
			delete_option( 'theme_mods_' . get_option( 'stylesheet' ) );
			// Reset Global Palette
			update_option( 'kadence_global_palette', '{"palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"second-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"third-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"active":"palette"}' );
			do_action( 'templify-import-templates/before_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index, $this->selected_palette, $this->selected_font );
		}

		/**
		 * 3). Import content (if the content XML file is set for this import).
		 * Returns any errors greater then the "warning" logger level, that will be displayed on front page.
		 */
		if ( ! empty( $this->selected_import_files['content'] ) ) {
			$this->append_to_frontend_error_messages( $this->importer->import_content( $this->selected_import_files['content'] ) );
		}

		/**
		 * 4). Execute the actions hooked to the 'templify-import-templates/after_content_import_execution' action:
		 *
		 * Default actions:
		 * 1 - Before widgets import setup (with priority 10).
		 * 2 - Import widgets (with priority 20).
		 * 3 - Import Redux data (with priority 30).
		 */
		do_action( 'templify-import-templates/after_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index, $this->selected_palette, $this->selected_font );
		// Save the import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_import_data_transient( $this->get_current_importer_data() );
		// Request the customizer import AJAX call.
		if ( ! empty( $this->selected_import_files['customizer'] ) ) {
			wp_send_json( array( 'status' => 'customizerAJAX' ) );
		}

		// Request the after all import AJAX call.
		if ( false !== has_action( 'templify-import-templates/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		// Send a JSON response with final report.
		$this->final_response();
	}


	/**
	 * AJAX callback to install a plugin.
	 */
	public function initial_install_ajax_callback() {
		Helpers::verify_ajax_call();

		if ( ! isset( $_POST['selected'] ) || ! isset( $_POST['builder'] ) ) {
			wp_send_json_error( 'Missing Information' );
		}
		// Get selected file index or set it to 0.
		$selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		$selected_builder = empty( $_POST['builder'] ) ? '' : sanitize_text_field( $_POST['builder'] );
		if ( empty( $selected_index ) || empty( $selected_builder ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		delete_transient( 'kadence_importer_data' );
		if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $selected_index ] ) ) ) {
			$template_database  = Template_Database_Importer::get_instance();
			$this->import_files = $template_database->get_importer_files( $selected_index, $selected_builder );
		}
		if ( ! isset( $this->import_files[ $selected_index ] ) ) {
			wp_send_json_error( 'Missing Template' );
		}
		wp_send_json( array( 'status' => 'initialSuccess' ) );
	}

	/**
	 * AJAX callback to install a plugin.
	 */
	public function install_plugins_ajax_callback() {
		Helpers::verify_ajax_call();

		if ( ! isset( $_POST['selected'] ) || ! isset( $_POST['builder'] ) ) {
			wp_send_json_error( 'Missing Information' );
		}
		// Get selected file index or set it to 0.
		$selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		$selected_builder = empty( $_POST['builder'] ) ? '' : sanitize_text_field( $_POST['builder'] );
		if ( empty( $selected_index ) || empty( $selected_builder ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		delete_transient( 'kadence_importer_data' );
		if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $selected_index ] ) ) ) {
			$template_database  = Template_Database_Importer::get_instance();
			$this->import_files = $template_database->get_importer_files( $selected_index, $selected_builder );
		}
		if ( ! isset( $this->import_files[ $selected_index ] ) ) {
			wp_send_json_error( 'Missing Template' );
		}
		$info = $this->import_files[ $selected_index ];
		$install = true;
		if ( isset( $info['plugins'] ) && ! empty( $info['plugins'] ) ) {

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			}
			if ( ! class_exists( 'WP_Upgrader' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			}
			$importer_plugins = array (
				'woocommerce' => array(
					'title' => 'Woocommerce',
					'base'  => 'woocommerce',
					'slug'  => 'woocommerce',
					'path'  => 'woocommerce/woocommerce.php',
					'src'   => 'repo',
				),
				'elementor' => array(
					'title' => 'Elementor',
					'base'  => 'elementor',
					'slug'  => 'elementor',
					'path'  => 'elementor/elementor.php',
					'src'   => 'repo',
				),
				'kadence-blocks' => array(
					'title' => 'Kadence Blocks',
					'base'  => 'kadence-blocks',
					'slug'  => 'kadence-blocks',
					'path'  => 'kadence-blocks/kadence-blocks.php',
					'src'   => 'repo',
				),
				'kadence-blocks-pro' => array(
					'title' => 'Kadence Block Pro',
					'base'  => 'kadence-blocks-pro',
					'slug'  => 'kadence-blocks-pro',
					'path'  => 'kadence-blocks-pro/kadence-blocks-pro.php',
					'src'   => 'bundle',
				),
				'kadence-pro' => array(
					'title' => 'Kadence Pro',
					'base'  => 'kadence-pro',
					'slug'  => 'kadence-pro',
					'path'  => 'kadence-pro/kadence-pro.php',
					'src'   => 'bundle',
				),
				'fluentform' => array(
					'title' => 'Fluent Forms',
					'src'   => 'repo',
					'base'  => 'fluentform',
					'slug'  => 'fluentform',
					'path'  => 'fluentform/fluentform.php',
				),
				'wpzoom-recipe-card' => array(
					'title' => 'Recipe Card Blocks by WPZOOM',
					'base'  => 'recipe-card-blocks-by-wpzoom',
					'slug'  => 'wpzoom-recipe-card',
					'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
					'src'   => 'repo',
				),
				'recipe-card-blocks-by-wpzoom' => array(
					'title' => 'Recipe Card Blocks by WPZOOM',
					'base'  => 'recipe-card-blocks-by-wpzoom',
					'slug'  => 'wpzoom-recipe-card',
					'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
					'src'   => 'repo',
				),
				'learndash' => array(
					'title' => 'LearnDash',
					'base'  => 'sfwd-lms',
					'slug'  => 'sfwd_lms',
					'path'  => 'sfwd-lms/sfwd_lms.php',
					'src'   => 'thirdparty',
				),
				'sfwd-lms' => array(
					'title' => 'LearnDash',
					'base'  => 'sfwd-lms',
					'slug'  => 'sfwd_lms',
					'path'  => 'sfwd-lms/sfwd_lms.php',
					'src'   => 'thirdparty',
				),
				'learndash-course-grid' => array(
					'title' => 'LearnDash Course Grid Addon',
					'base'  => 'learndash-course-grid',
					'slug'  => 'learndash_course_grid',
					'path'  => 'learndash-course-grid/learndash_course_grid.php',
					'src'   => 'thirdparty',
				),
				'lifterlms' => array(
					'title' => 'LifterLMS',
					'base'  => 'lifterlms',
					'slug'  => 'lifterlms',
					'path'  => 'lifterlms/lifterlms.php',
					'src'   => 'repo',
				),
				'tutor' => array(
					'title' => 'Tutor LMS',
					'base'  => 'tutor',
					'slug'  => 'tutor',
					'path'  => 'tutor/tutor.php',
					'src'   => 'repo',
				),
				'give' => array(
					'title' => 'GiveWP',
					'base'  => 'give',
					'slug'  => 'give',
					'path'  => 'give/give.php',
					'src'   => 'repo',
				),
				'the-events-calendar' => array(
					'title' => 'The Events Calendar',
					'base'  => 'the-events-calendar',
					'slug'  => 'the-events-calendar',
					'path'  => 'the-events-calendar/the-events-calendar.php',
					'src'   => 'repo',
				),
				'event-tickets' => array(
					'title' => 'Event Tickets',
					'base'  => 'event-tickets',
					'slug'  => 'event-tickets',
					'path'  => 'event-tickets/event-tickets.php',
					'src'   => 'repo',
				),
				'orderable' => array(
					'title' => 'Orderable',
					'base'  => 'orderable',
					'slug'  => 'orderable',
					'path'  => 'orderable/orderable.php',
					'src'   => 'repo',
				),
				'restrict-content' => array(
					'title' => 'Restrict Content',
					'base'  => 'restrict-content',
					'slug'  => 'restrictcontent',
					'path'  => 'restrict-content/restrictcontent.php',
					'src'   => 'repo',
				),
				'kadence-woo-extras' => array(
					'title' => 'Kadence Shop Kit',
					'base'  => 'kadence-woo-extras',
					'slug'  => 'kadence-woo-extras',
					'path'  => 'kadence-woo-extras/kadence-woo-extras.php',
					'src'   => 'bundle',
				),
				'depicter' => array(
					'title' => 'Depicter Slider',
					'base'  => 'depicter',
					'slug'  => 'depicter',
					'path'  => 'depicter/depicter.php',
					'src'   => 'repo',
				),
				'seriously-simple-podcasting' => array(
					'title' => 'Seriously Simple Podcasting',
					'base'  => 'seriously-simple-podcasting',
					'slug'  => 'seriously-simple-podcasting',
					'path'  => 'seriously-simple-podcasting/seriously-simple-podcasting.php',
					'src'   => 'repo',
				),
			);
			foreach( $info['plugins'] as $plugin ) {
				$path = false;
				if ( strpos( $plugin, '/' ) !== false ) {
					$path = $plugin;
					$arr  = explode( '/', $plugin, 2 );
					$base = $arr[0];
					if ( isset( $importer_plugins[ $base ] ) && isset( $importer_plugins[ $base ]['src'] ) ) {
						$src = $importer_plugins[ $base ]['src'];
					} else {
						$src = 'unknown';
					}
				} elseif ( isset( $importer_plugins[ $plugin ] ) ) {
					$path = $importer_plugins[ $plugin ]['path'];
					$base = $importer_plugins[ $plugin ]['base'];
					$src  = $importer_plugins[ $plugin ]['src'];
				}
				if ( $path ) {
					$state = Plugin_Check::active_check( $path );
					if ( 'woocommerce' === $base && empty( get_option( 'woocommerce_db_version' ) ) ) {
						update_option( 'woocommerce_db_version', '4.0' );
					}
					if ( 'woocommerce' === $base && ( 'notactive' === $state || 'installed' === $state ) ) {
						$this->ss = true;
					}
					if ( 'unknown' === $src ) {
						$check_api = plugins_api(
							'plugin_information',
							array(
								'slug' => $base,
								'fields' => array(
									'short_description' => false,
									'sections' => false,
									'requires' => false,
									'rating' => false,
									'ratings' => false,
									'downloaded' => false,
									'last_updated' => false,
									'added' => false,
									'tags' => false,
									'compatibility' => false,
									'homepage' => false,
									'donate_link' => false,
								),
							)
						);
						if ( ! is_wp_error( $check_api ) ) {
							$src = 'repo';
						}
					}
					if ( 'notactive' === $state && 'repo' === $src ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							wp_send_json_error( 'Permissions Issue' );
						}
						$api = plugins_api(
							'plugin_information',
							array(
								'slug' => $base,
								'fields' => array(
									'short_description' => false,
									'sections' => false,
									'requires' => false,
									'rating' => false,
									'ratings' => false,
									'downloaded' => false,
									'last_updated' => false,
									'added' => false,
									'tags' => false,
									'compatibility' => false,
									'homepage' => false,
									'donate_link' => false,
								),
							)
						);
						if ( ! is_wp_error( $api ) ) {

							// Use AJAX upgrader skin instead of plugin installer skin.
							// ref: function wp_ajax_install_plugin().
							$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );

							$installed = $upgrader->install( $api->download_link );
							if ( $installed ) {
								$silent = ( 'give' === $base || 'elementor' === $base || 'fluentform' === $base || 'restrict-content' === $base ? false : true );
								if ( 'give' === $base ) {
									add_option( 'give_install_pages_created', 1, '', false );
								}
								if ( 'restrict-content' === $base ) {
									update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
								}
								$activate = activate_plugin( $path, '', false, $silent );
								if ( is_wp_error( $activate ) ) {
									$install = false;
								}
							} else {
								$install = false;
							}
						} else {
							$install = false;
						}
					} elseif ( 'installed' === $state ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							wp_send_json_error( 'Permissions Issue' );
						}
						$silent = false; 
						//$silent = ( 'give' === $base || 'elementor' === $base ? false : true );
						if ( 'give' === $base ) {
							// Make sure give doesn't add it's pages, prevents having two sets.
							update_option( 'give_install_pages_created', 1, '', false );
						}
						if ( 'restrict-content' === $base ) {
							$silent = true;
							update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
						}
						$activate = activate_plugin( $path, '', false, $silent );
						if ( is_wp_error( $activate ) ) {
							$install = false;
						}
					}
					if ( 'give' === $base ) {
						update_option( 'give_version_upgraded_from', '2.13.2' );
						//add_option( 'give_install_pages_created', 1, '', false );
					}
					if ( 'kadence-pro' === $base ) {
						$enabled = json_decode( get_option( 'kadence_pro_theme_config' ), true );
						$enabled['elements'] = true;
						$enabled['header_addons'] = true;
						$enabled['mega_menu'] = true;
						$enabled = json_encode( $enabled );
						update_option( 'kadence_pro_theme_config', $enabled );
					}
				}
			}
		}
		if ( false === $install ) {
			wp_send_json_error();
		} else {
			wp_send_json( array( 'status' => 'pluginSuccess' ) );
		}
	}



	/**
	 * AJAX callback for importing the customizer data.
	 * This request has the wp_customize set to 'on', so that the customizer hooks can be called
	 * (they can only be called with the $wp_customize instance). But if the $wp_customize is defined,
	 * then the widgets do not import correctly, that's why the customizer import has its own AJAX call.
	 */
	public function import_customizer_data_ajax_callback() {
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();
		$use_existing_importer_data = $this->use_existing_importer_data();

		if ( ! $use_existing_importer_data ) {
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			if ( apply_filters( 'templify_import_templates_save_log_files', false ) ) {
				// Define log file path.
				$this->log_file_path = Helpers::get_log_path();
			} else {
				$this->log_file_path = '';
			}

			// Get selected file index or set it to 0.
			$this->selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
			$this->selected_palette = empty( $_POST['palette'] ) ? '' : sanitize_text_field( $_POST['palette'] );
			$this->selected_font    = empty( $_POST['font'] ) ? '' : sanitize_text_field( $_POST['font'] );
			$this->selected_builder = empty( $_POST['builder'] ) ? 'blocks' : sanitize_text_field( $_POST['builder'] );

			
			if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $this->selected_index ] ) ) ) {
				$template_database  = Template_Database_Importer::get_instance();
				$this->import_files = $template_database->get_importer_files( $this->selected_index, $this->selected_builder );
			}
			if ( ! isset( $this->import_files[ $this->selected_index ] ) ) {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'templify-import-templates' ) );
			}
			/**
			 * 1). Prepare import files.
			 * Predefined import files via filter: templify-import-templates/import_files
			 */
			if ( ! empty( $this->import_files[ $this->selected_index ] ) ) { // Use predefined import files from wp filter: templify-import-templates/import_files.

				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_files( $this->import_files[ $this->selected_index ] );
				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'templify-import-templates' )
					);
				}
				if ( apply_filters( 'templify_import_templates_save_log_files', false ) ) {
					// Add this message to log file.
					$log_added = Helpers::append_to_file(
						sprintf(
							__( 'The import files for: %s were successfully downloaded!', 'templify-import-templates' ),
							$this->import_files[ $this->selected_index ]['slug']
						) . Helpers::import_file_info( $this->selected_import_files ),
						$this->log_file_path,
						esc_html__( 'Downloaded files' , 'templify-import-templates' )
					);
				}
			} else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'templify-import-templates' ) );
			}
			// If elementor make sure the defaults are off.
			if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
				update_option( 'elementor_disable_color_schemes', 'yes' );
				update_option( 'elementor_disable_typography_schemes', 'yes' );
			}
			// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
			Helpers::set_import_data_transient( $this->get_current_importer_data() );
			if ( ! $this->before_import_executed ) {
				$this->before_import_executed = true;
	
				/**
				 * Save Current Theme mods for a potential undo.
				 */
				update_option( '_templify_import_templates_old_customizer', get_option( 'theme_mods_' . get_option( 'stylesheet' ) ) );
				// Save Import data for use if we need to reset it.
				update_option( '_templify_import_templates_last_import_data', $this->import_files[ $this->selected_index ], 'no' );
				// Reset to default settings values.
				delete_option( 'theme_mods_' . get_option( 'stylesheet' ) );
				// Reset Global Palette
				if ( get_option( 'kadence_global_palette' ) !== false ) {
					// The option already exists, so update it.
					update_option( 'kadence_global_palette', '{"palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"second-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"third-palette":[{"color":"#3182CE","slug":"palette1","name":"Palette Color 1"},{"color":"#2B6CB0","slug":"palette2","name":"Palette Color 2"},{"color":"#1A202C","slug":"palette3","name":"Palette Color 3"},{"color":"#2D3748","slug":"palette4","name":"Palette Color 4"},{"color":"#4A5568","slug":"palette5","name":"Palette Color 5"},{"color":"#718096","slug":"palette6","name":"Palette Color 6"},{"color":"#EDF2F7","slug":"palette7","name":"Palette Color 7"},{"color":"#F7FAFC","slug":"palette8","name":"Palette Color 8"},{"color":"#ffffff","slug":"palette9","name":"Palette Color 9"}],"active":"palette"}' );
				}
			}
		}
		
	
		/**
		 * Execute the customizer import actions.
		 *
		 * Default actions:
		 * 1 - Customizer import (with priority 10).
		 */
		do_action( 'templify-import-templates/customizer_import_execution', $this->selected_import_files );

		// Request the after all import AJAX call.
		if ( false !== has_action( 'templify-import-templates/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		// Send a JSON response with final report.
		$this->final_response();
	}


	
	/**
	 * AJAX callback for the after all import action.
	 */
	public function after_all_import_data_ajax_callback() {
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Get existing import data.
		if ( $this->use_existing_importer_data() ) {
			/**
			 * Execute the after all import actions.
			 *
			 * Default actions:
			 * 1 - after_import action (with priority 10).
			 */
			do_action( 'kadence-starter-templates/after_all_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index, $this->selected_palette, $this->selected_font );
		}

		// Send a JSON response with final report.
		$this->final_response();
	}



	/**
	 * AJAX callback to remove past content..
	 */
	public function remove_past_data_ajax_callback() {
		Helpers::verify_ajax_call();

		if ( ! current_user_can( 'customize' ) ) {
			wp_send_json_error();
		}
		global $wpdb;
		// Prevents elementor from pushing out an confrimation and breaking the import.
		$_GET['force_delete_kit'] = true;
		$removed_content = true;

		$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_templify_import_templates_imported_post'" );
		$term_ids = $wpdb->get_col( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key='_templify_import_templates_imported_term'" );
		if ( isset( $post_ids ) && is_array( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				$worked = wp_delete_post( $post_id, true );
				if ( false === $worked ) {
					$removed_content = false;
				}
			}
		}
		if ( isset( $term_ids ) && is_array( $term_ids ) ) {
			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) ) {
					wp_delete_term( $term_id, $term->taxonomy );
				}
			}
		}

		if ( false === $removed_content ) {
			wp_send_json_error();
		} else {
			wp_send_json( array( 'status' => 'removeSuccess' ) );
		}
	}


	/**
	 * AJAX callback to subscribe..
	 */
	public function subscribe_ajax_callback() {
		Helpers::verify_ajax_call();
		$email = empty( $_POST['email'] ) ? '' : sanitize_text_field( $_POST['email'] );
		$selected_index = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		// Do you have the data?
		if ( $email && is_email( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			list( $user, $domain ) = explode( '@', $email );
			list( $pre_domain, $post_domain ) = explode( '.', $domain );
			$spell_issue_domains = array( 'gmaiil', 'gmai', 'gmaill' );
			$spell_issue_domain_ends = array( 'local', 'comm', 'orgg', 'cmm' );
			if ( in_array( $pre_domain, $spell_issue_domain_ends, true ) ) {
				return wp_send_json( 'emailDomainPreError' );
			}
			if ( in_array( $post_domain, $spell_issue_domain_ends, true ) ) {
				return wp_send_json( 'emailDomainPostError' );
			}
			$args = array(
				'email'   => $email,
				'tag'     => 'starter',
				'list'    => '20',
				'starter' => $selected_index,
			);
			// Get the response.
			$api_url  = add_query_arg( $args, 'https://www.kadencewp.com/kadence-blocks/wp-json/kadence-subscribe/v1/subscribe/' );
			$response = wp_remote_get( $api_url );
			// Early exit if there was an error.
			if ( is_wp_error( $response ) ) {
				return wp_send_json( array( 'status' => 'subscribeSuccess' ) );
			}
			// Get the CSS from our response.
			$contents = wp_remote_retrieve_body( $response );
			// Early exit if there was an error.
			if ( is_wp_error( $contents ) ) {
				return wp_send_json( array( 'status' => 'subscribeSuccess' ) );
			}
			if ( ! $contents ) {
				// Send JSON Error response to the AJAX call.
				wp_send_json( array( 'status' => 'subscribeSuccess' ) );
			} else {
				update_option( 'templify_import_templates_subscribe', true );
				wp_send_json( array( 'status' => 'subscribeSuccess' ) );
			}
		}
		// Send JSON Error response to the AJAX call.
		wp_send_json( 'emailDomainPreError' );
		die;
	}


	/**
	 * AJAX callback to install a plugin.
	 */
	public function check_plugin_data_ajax_callback() {
		Helpers::verify_ajax_call();
		if ( ! isset( $_POST['selected'] ) || ! isset( $_POST['builder'] ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		$selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		$selected_builder = empty( $_POST['builder'] ) ? '' : sanitize_text_field( $_POST['builder'] );
		if ( empty( $selected_index ) || empty( $selected_builder ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $selected_index ] ) ) ) {
			$template_database  = Template_Database_Importer::get_instance();
			$this->import_files = $template_database->get_importer_files( $selected_index, $selected_builder );
		}
		if ( ! isset( $this->import_files[ $selected_index ] ) ) {
			wp_send_json_error( 'Missing Template' );
		}
		$info = $this->import_files[ $selected_index ];

		if ( isset( $info['plugins'] ) && ! empty( $info['plugins'] ) ) {

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			}
			$importer_plugins = array (
				'woocommerce' => array(
					'title' => 'Woocommerce',
					'base'  => 'woocommerce',
					'slug'  => 'woocommerce',
					'path'  => 'woocommerce/woocommerce.php',
					'src'   => 'repo',
				),
				'elementor' => array(
					'title' => 'Elementor',
					'base'  => 'elementor',
					'slug'  => 'elementor',
					'path'  => 'elementor/elementor.php',
					'src'   => 'repo',
				),
				'kadence-blocks' => array(
					'title' => 'Kadence Blocks',
					'base'  => 'kadence-blocks',
					'slug'  => 'kadence-blocks',
					'path'  => 'kadence-blocks/kadence-blocks.php',
					'src'   => 'repo',
				),
				'kadence-blocks-pro' => array(
					'title' => 'Kadence Block Pro',
					'base'  => 'kadence-blocks-pro',
					'slug'  => 'kadence-blocks-pro',
					'path'  => 'kadence-blocks-pro/kadence-blocks-pro.php',
					'src'   => 'bundle',
				),
				'kadence-pro' => array(
					'title' => 'Kadence Pro',
					'base'  => 'kadence-pro',
					'slug'  => 'kadence-pro',
					'path'  => 'kadence-pro/kadence-pro.php',
					'src'   => 'bundle',
				),
				'fluentform' => array(
					'title' => 'Fluent Forms',
					'src'   => 'repo',
					'base'  => 'fluentform',
					'slug'  => 'fluentform',
					'path'  => 'fluentform/fluentform.php',
				),
				'wpzoom-recipe-card' => array(
					'title' => 'Recipe Card Blocks by WPZOOM',
					'base'  => 'recipe-card-blocks-by-wpzoom',
					'slug'  => 'wpzoom-recipe-card',
					'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
					'src'   => 'repo',
				),
				'recipe-card-blocks-by-wpzoom' => array(
					'title' => 'Recipe Card Blocks by WPZOOM',
					'base'  => 'recipe-card-blocks-by-wpzoom',
					'slug'  => 'wpzoom-recipe-card',
					'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
					'src'   => 'repo',
				),
				'learndash' => array(
					'title' => 'LearnDash',
					'base'  => 'sfwd-lms',
					'slug'  => 'sfwd_lms',
					'path'  => 'sfwd-lms/sfwd_lms.php',
					'src'   => 'thirdparty',
				),
				'learndash-course-grid' => array(
					'title' => 'LearnDash Course Grid Addon',
					'base'  => 'learndash-course-grid',
					'slug'  => 'learndash_course_grid',
					'path'  => 'learndash-course-grid/learndash_course_grid.php',
					'src'   => 'thirdparty',
				),
				'sfwd-lms' => array(
					'title' => 'LearnDash',
					'base'  => 'sfwd-lms',
					'slug'  => 'sfwd_lms',
					'path'  => 'sfwd-lms/sfwd_lms.php',
					'src'   => 'thirdparty',
				),
				'lifterlms' => array(
					'title' => 'LifterLMS',
					'base'  => 'lifterlms',
					'slug'  => 'lifterlms',
					'path'  => 'lifterlms/lifterlms.php',
					'src'   => 'repo',
				),
				'tutor' => array(
					'title' => 'Tutor LMS',
					'base'  => 'tutor',
					'slug'  => 'tutor',
					'path'  => 'tutor/tutor.php',
					'src'   => 'repo',
				),
				'give' => array(
					'title' => 'GiveWP',
					'base'  => 'give',
					'slug'  => 'give',
					'path'  => 'give/give.php',
					'src'   => 'repo',
				),
				'the-events-calendar' => array(
					'title' => 'The Events Calendar',
					'base'  => 'the-events-calendar',
					'slug'  => 'the-events-calendar',
					'path'  => 'the-events-calendar/the-events-calendar.php',
					'src'   => 'repo',
				),
				'event-tickets' => array(
					'title' => 'Event Tickets',
					'base'  => 'event-tickets',
					'slug'  => 'event-tickets',
					'path'  => 'event-tickets/event-tickets.php',
					'src'   => 'repo',
				),
				'orderable' => array(
					'title' => 'Orderable',
					'base'  => 'orderable',
					'slug'  => 'orderable',
					'path'  => 'orderable/orderable.php',
					'src'   => 'repo',
				),
				'restrict-content' => array(
					'title' => 'Restrict Content',
					'base'  => 'restrict-content',
					'slug'  => 'restrictcontent',
					'path'  => 'restrict-content/restrictcontent.php',
					'src'   => 'repo',
				),
				'kadence-woo-extras' => array(
					'title' => 'Kadence Shop Kit',
					'base'  => 'kadence-woo-extras',
					'slug'  => 'kadence-woo-extras',
					'path'  => 'kadence-woo-extras/kadence-woo-extras.php',
					'src'   => 'bundle',
				),
				'depicter' => array(
					'title' => 'Depicter Slider',
					'base'  => 'depicter',
					'slug'  => 'depicter',
					'path'  => 'depicter/depicter.php',
					'src'   => 'repo',
				),
				'seriously-simple-podcasting' => array(
					'title' => 'Seriously Simple Podcasting',
					'base'  => 'seriously-simple-podcasting',
					'slug'  => 'seriously-simple-podcasting',
					'path'  => 'seriously-simple-podcasting/seriously-simple-podcasting.php',
					'src'   => 'repo',
				),
			);
			$plugin_information = array();
			foreach( $info['plugins'] as $plugin ) {
				$path = false;
				if ( strpos( $plugin, '/' ) !== false ) {
					$path = $plugin;
					$arr  = explode( '/', $plugin, 2 );
					$base = $arr[0];
					if ( isset( $importer_plugins[ $base ] ) && isset( $importer_plugins[ $base ]['src'] ) ) {
						$src = $importer_plugins[ $base ]['src'];
					} else {
						$src = 'unknown';
					}
					if ( isset( $importer_plugins[ $base ] ) && isset( $importer_plugins[ $base ]['title'] ) ) {
						$title = $importer_plugins[ $base ]['title'];
					} else {
						$title = $base;
					}
				} elseif ( isset( $importer_plugins[ $plugin ] ) ) {
					$path   = $importer_plugins[ $plugin ]['path'];
					$base   = $importer_plugins[ $plugin ]['base'];
					$src    = $importer_plugins[ $plugin ]['src'];
					$title  = $importer_plugins[ $plugin ]['title'];
				}
				if ( $path ) {
					$state = Plugin_Check::active_check( $path );
					if ( 'unknown' === $src ) {
						$check_api = plugins_api(
							'plugin_information',
							array(
								'slug' => $base,
								'fields' => array(
									'short_description' => false,
									'sections' => false,
									'requires' => false,
									'rating' => false,
									'ratings' => false,
									'downloaded' => false,
									'last_updated' => false,
									'added' => false,
									'tags' => false,
									'compatibility' => false,
									'homepage' => false,
									'donate_link' => false,
								),
							)
						);
						if ( ! is_wp_error( $check_api ) ) {
							$title = $check_api->name;
							$src   = 'repo';
						}
					}
					$plugin_information[ $plugin ] = array(
						'state' => $state,
						'src'   => $src,
						'title' => $title,
					);
				} else {
					$plugin_information[ $plugin ] = array(
						'state' => 'unknown',
						'src'   => 'unknown',
						'title' => $plugin,
					);
				}
			}
			wp_send_json( $plugin_information );
		} else {
			wp_send_json_error( 'Missing Plugins' );
		}
	}


	/**
	 * Run check to see if we need to dismiss the notice.
	 * If all tests are successful then call the dismiss_notice() method.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function ajax_dismiss_starter_notice() {

		// Sanity check: Early exit if we're not on a wptrt_dismiss_notice action.
		if ( ! isset( $_POST['action'] ) || 'kadence_starter_dismiss_notice' !== $_POST['action'] ) {
			return;
		}
		// Security check: Make sure nonce is OK.
		check_ajax_referer( 'kadence-starter-ajax-verification', 'security', true );

		// If we got this far, we need to dismiss the notice.
		update_option( 'templify_import_templates_dismiss_upsell', true, false );
	}

}

Importer_Templates::get_instance();
