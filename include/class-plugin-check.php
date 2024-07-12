<?php
/**
 * Static functions used in the Templify Importer Templates plugin.
 *
 * @package Templify Importer Templates
 */

namespace TemplifyWP\TemplifyImporterTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Class with static helper functions.
 */
class Plugin_Check {

	/**
	 * Static var active plugins
	 *
	 * @var $active_plugins
	 */
	private static $active_plugins;

	/**
	 * Static var installed plugins
	 *
	 * @var $installed_plugins
	 */
	private static $installed_plugins;
	/**
	 * Initialize
	 */
	public static function init() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		self::$installed_plugins = (array) get_plugins();

		self::$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}


		
	}

	/**
	 * Active Check
	 *
	 * @param string $plugin_base_name is plugin folder/filename.php.
	 */
	public static function active_check( $plugin_base_name ) {
		if ( ! self::$active_plugins || ! $this->$installed_plugins ) {
			self::init();
		}
		if ( in_array( $plugin_base_name, self::$active_plugins, true ) || array_key_exists( $plugin_base_name, self::$active_plugins ) ) {
			return 'active';
		} elseif ( in_array( $plugin_base_name, self::$installed_plugins, true ) || array_key_exists( $plugin_base_name, self::$installed_plugins ) ) {
			return 'installed';
		} else {
			return 'notactive';
		}
	}

	/**
	 * AJAX callback for installing a plugin.
	 * Has to contain the `slug` POST parameter.
	 */
	public function install_plugin_callback() {

		check_ajax_referer( 'templify-ajax-verification', 'security' );

		// Check if user has the WP capability to install plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( esc_html__( 'Could not install the plugin. You don\'t have permission to install plugins.', 'one-click-demo-import' ) );
		} 

		$slug = ! empty( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';

		if ( empty( $slug ) ) {
			wp_send_json_error( esc_html__( 'Could not install the plugin. Plugin slug is missing.', 'one-click-demo-import' ) );
		}

		// Check if the plugin is already installed and activated.
		if ( $this->is_plugin_active( $slug ) ) {
			wp_send_json_success( esc_html__( 'Plugin is already installed and activated!', 'one-click-demo-import' ) );
		}

		// Activate the plugin if the plugin is already installed.
		if ( $this->is_plugin_installed( $slug ) ) {
			$activated = $this->activate_plugin( $this->get_plugin_basename_from_slug( $slug ), $slug );

			if ( ! is_wp_error( $activated ) ) {
				wp_send_json_success( esc_html__( 'Plugin was already installed! We activated it for you.', 'one-click-demo-import' ) );
			} else {
				wp_send_json_error( $activated->get_error_message() );
			}
		}

		// Check for file system permissions.
		if ( ! $this->filesystem_permissions_allowed() ) {
			wp_send_json_error( esc_html__( 'Could not install the plugin. Don\'t have file permission.', 'templif-importer-templates' ) );
		}

		// Do not allow WordPress to search/download translations, as this will break JS output.
		remove_action( 'upgrader_process_complete', [ 'Language_Pack_Upgrader', 'async_upgrade' ], 20 );

		// Prep variables for Plugin_Installer_Skin class.
		$extra         = array();
		$extra['slug'] = $slug; // Needed for potentially renaming of directory name.
		$source        = $this->get_download_url( $slug );
		$api           = empty( $this->get_plugin_data( $slugg )['source'] ) ? $this->get_plugins_api( $slug ) : null;
		$api           = ( false !== $api ) ? $api : null;

		if ( ! empty( $api ) && is_wp_error( $api ) ) {
			wp_send_json_error( $api->get_error_message() );
		}

		if ( ! class_exists( '\Plugin_Upgrader', false ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$skin_args = array(
			'type'   => 'web',
			'plugin' => '',
			'api'    => $api,
			'extra'  => $extra,
		);


		$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin( $skin_args ) );
		
		$upgrader->install( $source );

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		if ( $upgrader->plugin_info() ) {
			$activated = $this->activate_plugin( $upgrader->plugin_info(), $slug );

			if ( ! is_wp_error( $activated ) ) {
				wp_send_json_success(
					esc_html__( 'Plugin installed and activated succesfully.', 'templify-importer-templates' )
				);
			} else {
				wp_send_json_success( $activated->get_error_message() );
			}
		}

		wp_send_json_error( esc_html__( 'Could not install the plugin. WP Plugin installer could not retrieve plugin information.', 'templify-importer-templates' ) );
	}

}
