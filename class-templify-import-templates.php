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
