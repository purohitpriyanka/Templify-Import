<?php
/**
 * Class for the Customizer Import/Export and Reset.
 * This is based on the Beaver Builders Import Export plugin.
 *
 * Used in the Customizer importer.
 *
 * @since 1.0.4
 * @package Templify Import Templates
 */



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for Customizer Import Export
 *
 * @category class
 */
class Customizer_Import_Export {
/**
	 * An array of core options that shouldn't be imported.
	 * @access private
	 * @var array $core_options
	 */
	static private $core_options = array(
		'blogname',
		'blogdescription',
		'show_on_front',
		'page_on_front',
		'page_for_posts',
	);

	/**
	 * @var null
	 */
	private static $instance = null;
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
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'import_export_requests' ), 999999 );
		add_action( 'customize_register', array( $this, 'register_controls' ) );
		add_action( 'customize_register', array( $this, 'import_export_setup' ) );
		add_action( 'customize_controls_print_scripts', array( $this, 'controls_print_scripts' ) );
		add_filter( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_customizer_scripts' ) );
		// Ajax handler for reset.
		add_action( 'wp_ajax_templify_importer_reset', array( $this, 'ajax_reset' ) );
	}


	/**
	 * Enqueue Customizer scripts
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_customizer_scripts() {
		wp_enqueue_style( 'templify-import-import-export', TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/css/starter-import-export.css', array( 'wp-components' ), TEMPLIFY_IMPORT_TEMPLATES_VERSION );
		wp_enqueue_script( 'templify-import-import-export', TEMPLIFY_IMPORT_TEMPLATES_URL . 'assets/export/starter-import-export.min.js', array( 'jquery' ), TEMPLIFY_IMPORT_TEMPLATES_VERSION, true );
		wp_localize_script(
			'templify-import-import-export',
			'templifyStarterImport',
			array(
				'resetConfirm'   => __( "Attention! This will remove all customizations to this theme!\n\nThis action is irreversible!", 'templify' ),
				'emptyImport'	 => __( 'Please choose a file to import.', 'templify-import-templates' ),
				'customizerURL'	 => admin_url( 'customize.php' ),
				'nonce'          => array(
					'reset'  => wp_create_nonce( 'templify-import-reseting' ),
					'export' => wp_create_nonce( 'templify-import-exporting' ),
				),
			)
		);
	}


    /**
	 * Add Control.
	 *
	 * @access public
	 * @param object $wp_customize the customizer object.
	 * @return void
	 */
	public function register_controls( $wp_customize ) {
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-import-export-control.php'; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound
	}

    /**
	 * Add Customizer Setup
	 *
	 * @access public
	 * @param object $wp_customize the object.
	 * @return void
	 */
	public static function import_export_setup( $wp_customize ) {
		$section_config = array(
			'title'    => __( 'Import/Export', 'templify-import-templates' ),
			'priority' => 999,
		);
		$wp_customize->add_section( 'templify_importer_import_export', $section_config );
		$control_config = array(
			'settings' => array(),
			'priority' => 2,
			'section'  => 'templify_importer_import_export',
			'label'    => esc_html__( 'Import/Export', 'templify' ),
		);
		$wp_customize->add_control( new Templify_Import_Control_Import_Export( $wp_customize, 'templify_importer_import_export', $control_config ) );

	}


	/**
	 * Check to see if we need to do an export or import.
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	public static function import_export_requests( $wp_customize ) {
		// Check if user is allowed to change values.
		if ( current_user_can( 'edit_theme_options' ) ) {
			if ( isset( $_REQUEST['templify-import-export'] ) ) {
				self::export_data( $wp_customize );
			}
			if ( isset( $_REQUEST['templify-import-import'] ) && isset( $_FILES['templify-import-import-file'] ) ) {
				self::import_data( $wp_customize );
			}
		}
	}

	/**
	 * Export Theme settings.
	 *
	 * @access private
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	private static function export_data( $wp_customize ) {
		if ( ! wp_verify_nonce( $_REQUEST['templify-import-export'], 'templify-import-exporting' ) ) {
			return;
		}
		$template = 'templify';
		$charset  = get_option( 'blog_charset' );
		$mods     = get_theme_mods();
		$data     = array(
			'template' => $template,
			'mods'     => $mods ? $mods : array(),
			'options'  => array(),
		);

		// Get options from the Customizer API.
		$settings = $wp_customize->settings();
		foreach ( $settings as $key => $setting ) {

			if ( 'option' == $setting->type ) {

				// Don't save widget data.
				if ( 'widget_' === substr( strtolower( $key ), 0, 7 ) ) {
					continue;
				}

				// Don't save sidebar data.
				if ( 'sidebars_' === substr( strtolower( $key ), 0, 9 ) ) {
					continue;
				}

				// Don't save core options.
				if ( in_array( $key, self::$core_options ) ) {
					continue;
				}

				$data['options'][ $key ] = $setting->value();
			}
		}
		if ( function_exists( 'wp_get_custom_css_post' ) ) {
			$data['wp_css'] = wp_get_custom_css();
		}

		// Set the download headers.
		header( 'Content-disposition: attachment; filename=templify-theme-export.dat' );
		header( 'Content-Type: application/octet-stream; charset=' . $charset );

		// Serialize the export data.
		echo serialize( $data );

		// Start the download.
		die();
	}


}
