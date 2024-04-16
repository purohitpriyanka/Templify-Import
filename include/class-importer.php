<?php
/**
 * Class for declaring the content importer used in the One Click Demo Import plugin
 *
 * @package Templify Import Templates
 */



class Importer {
	/**
	 * The importer class object used for importing content.
	 *
	 * @var object
	 */
	private $importer;

	/**
	 * Time in milliseconds, marking the beginning of the import.
	 *
	 * @var float
	 */
	private $microtime;

	/**
	 * The instance of the TemplifyImport\TemplifyImportemplates\Logger class.
	 *
	 * @var object
	 */
	public $logger;

	/**
	 * The instance of the TemplifyImport\TemplifyImportemplates class.
	 *
	 * @var object
	 */
	private $templify_import_templates;
	/**
	 * A list of allowed mimes.
	 *
	 * @var array
	 */
	protected $extensions = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
		'svg'          => 'image/svg+xml',
	);
	/**
	 * Constructor method.
	 *
	 * @param array  $importer_options Importer options.
	 * @param object $logger           Logger object used in the importer.
	 */
	public function __construct( $importer_options = array(), $logger = null ) {
		// Include files that are needed for WordPress Importer v2.
		$this->include_required_files();

		// Set the WordPress Importer v2 as the importer used in this plugin.
		// More: https://github.com/humanmade/WordPress-Importer.
		$this->importer = new WXRImporter( $importer_options );

		// Set logger to the importer.
		$this->logger = $logger;
		if ( ! empty( $this->logger ) ) {
			$this->set_logger( $this->logger );
		}

		// Get the tempilfy_import_templates (main plugin class) instance.
		$this->templify_import_templates = Importer_Templates::get_instance();
	}


	/**
	 * Include required files.
	 */
	private function include_required_files() {
		if ( ! class_exists( '\WP_Importer' ) ) {
			require ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		}
		if ( ! class_exists( '\AwesomeMotive\WPContentImporter2\WXRImporter' ) ) {
			require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'wxr-importer/WXRImporter.php';
		}
		if ( ! class_exists( '\AwesomeMotive\WPContentImporter2\WXRImportInfo' ) ) {
			require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'wxr-importer/WXRImportInfo.php';
		}
		if ( ! class_exists( '\AwesomeMotive\WPContentImporter2\Importer' ) ) {
			require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'wxr-importer/Importer.php';
		}
		require_once TEMPLIFY_IMPORT_TEMPLATES_PATH . 'include/class-wxr-importer.php';
	}


}
