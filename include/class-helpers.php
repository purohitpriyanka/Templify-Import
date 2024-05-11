<?php
/**
 * Static functions used in the Templify Import Templates plugin.
 *
 * @package Templify Import Templates
 */



//use function request_filesystem_credentials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class with static helper functions.
 */
class Helpers {
    /**
	 * Append content to the file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @param string $separator_text separates the existing content of the file with the new content.
	 * @return boolean|WP_Error, path to the saved file or WP_Error object with error message.
	 */
	public static function append_to_file( $content, $file_path, $separator_text = '' ) {
		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to create a file.
		global $wp_filesystem;

		$existing_data = '';
		if ( file_exists( $file_path ) ) {
			$existing_data = $wp_filesystem->get_contents( $file_path );
		}

		// Style separator.
		$separator = PHP_EOL . '---' . $separator_text . '---' . PHP_EOL;

		if ( ! $wp_filesystem->put_contents( $file_path, $existing_data . $separator . $content . PHP_EOL ) ) {
			return new \WP_Error(
				'failed_writing_file_to_server',
				sprintf(
					__( 'An error occurred while writing file to your server! Tried to write a file to: %s%s.', 'templify-import-templates' ),
					'<br>',
					$file_path
				)
			);
		}

		return true;
	}

    /**
	 * Get data from a file
	 *
	 * @param string $file_path file path where the content should be saved.
	 * @return string $data, content of the file or WP_Error object with error message.
	 */
	public static function data_from_file( $file_path ) {
		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to read a file.
		global $wp_filesystem;

		$data = $wp_filesystem->get_contents( $file_path );

		if ( ! $data ) {
			return new \WP_Error(
				'failed_reading_file_from_server',
				sprintf(
					__( 'An error occurred while reading a file from your server! Tried reading file from path: %s%s.', 'templify-import-templates' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file data.
		return $data;
	}


    /**
	 * Helper function: check for WP file-system credentials needed for reading and writing to a file.
	 *
	 * @return boolean|WP_Error
	 */
	private static function check_wp_filesystem_credentials() {
		// Check if the file-system method is 'direct', if not display an error.
		$file_system_method = apply_filters( 'templify-import-templates/file_system_method', 'direct' );
		if ( ! ( $file_system_method === get_filesystem_method() ) ) {
			return new \WP_Error(
				'no_direct_file_access',
				sprintf(
					__( 'This WordPress page does not have %sdirect%s write file access. This plugin needs it in order to save the demo import xml file to the upload directory of your site. You can change this setting with these instructions: %s.', 'templify-import-templates' ),
					'<strong>',
					'</strong>',
					'<a href="http://gregorcapuder.com/wordpress-how-to-set-direct-filesystem-method/" target="_blank">How to set <strong>direct</strong> filesystem method</a>'
				)
			);
		}

		// Get plugin page settings.
		$plugin_page_setup = apply_filters( 'templify-import-templates/plugin_page_setup', array(
				'parent_slug' => 'themes.php',
				'page_title'  => esc_html__( 'One Click Demo Import' , 'templify-import-templates' ),
				'menu_title'  => esc_html__( 'Import Demo Data' , 'templify-import-templates' ),
				'capability'  => 'import',
				'menu_slug'   => 'pt-one-click-demo-import',
			)
		);

		// Get user credentials for WP file-system API.
		$demo_import_page_url = wp_nonce_url( $plugin_page_setup['parent_slug'] . '?page=' . $plugin_page_setup['menu_slug'], $plugin_page_setup['menu_slug'] );

		if ( false === ( $creds = request_filesystem_credentials( $demo_import_page_url, '', false, false, null ) ) ) {
			return new \WP_error(
				'filesystem_credentials_could_not_be_retrieved',
				__( 'An error occurred while retrieving reading/writing permissions to your server (could not retrieve WP filesystem credentials)!', 'templify-import-templates' )
			);
		}

		// Now we have credentials, try to get the wp_filesystem running.
		if ( ! WP_Filesystem( $creds ) ) {
			return new \WP_Error(
				'wrong_login_credentials',
				__( 'Your WordPress login credentials don\'t allow to use WP_Filesystem!', 'templify-import-templates' )
			);
		}

		return true;
	}


	/**
	 * Download import files. Content .xml and widgets .wie|.json files.
	 *
	 * @param  array  $import_file_info array with import file details.
	 * @return array|WP_Error array of paths to the downloaded files or WP_Error object with error message.
	 */
	public static function download_import_files( $import_file_info ) {
		$downloaded_files = array(
			'content'    => '',
			'widgets'    => '',
			'customizer' => '',
			'redux'      => '',
			'forms'      => '',
		);
		$downloader = new Downloader();
		// ----- Set content file path -----
		// Check if 'content' is not defined. That would mean a local file.
		if ( empty( $import_file_info['content'] ) ) {
			if ( isset( $import_file_info['local_content'] ) && file_exists( $import_file_info['local_content'] ) ) {
				$downloaded_files['content'] = $import_file_info['local_content'];
			}
		} else {
			// Set the filename string for content import file.
			$content_filename = apply_filters( 'templify-import-templates/downloaded_content_file_prefix', 'demo-content-import-file_' ) . self::$demo_import_start_time . apply_filters( 'templify-import-templates/downloaded_content_file_suffix_and_file_extension', '.xml' );

			// Download the content import file.
			$downloaded_files['content'] = $downloader->download_file( $import_file_info['content'], $content_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['content'] ) ) {
				return $downloaded_files['content'];
			}
		}

		// ----- Set widget file path -----
		// Get widgets file as well. If defined!
		if ( ! empty( $import_file_info['widget_data'] ) ) {
			// Set the filename string for widgets import file.
			$widget_filename = apply_filters( 'templify-import-templates/downloaded_widgets_file_prefix', 'demo-widgets-import-file_' ) . self::$demo_import_start_time . apply_filters( 'templify-import-templates/downloaded_widgets_file_suffix_and_file_extension', '.json' );

			// Download the widgets import file.
			$downloaded_files['widgets'] = $downloader->download_file( $import_file_info['widget_data'], $widget_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['widgets'] ) ) {
				return $downloaded_files['widgets'];
			}
		} else if ( ! empty( $import_file_info['local_widget_data'] ) ) {
			if ( file_exists( $import_file_info['local_widget_data'] ) ) {
				$downloaded_files['widgets'] = $import_file_info['local_widget_data'];
			}
		}

		// ----- Set customizer file path -----
		// Get customizer import file as well. If defined!
		if ( ! empty( $import_file_info['theme_options'] ) ) {
			// Setup filename path to save the customizer content.
			$customizer_filename = apply_filters( 'templify-import-templates/downloaded_customizer_file_prefix', 'demo-customizer-import-file_' ) . self::$demo_import_start_time . apply_filters( 'templify-import-templates/downloaded_customizer_file_suffix_and_file_extension', '.dat' );

			// Download the customizer import file.
			$downloaded_files['customizer'] = $downloader->download_file( $import_file_info['theme_options'], $customizer_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['customizer'] ) ) {
				return $downloaded_files['customizer'];
			}
		} else if ( ! empty( $import_file_info['local_theme_options'] ) ) {
			if ( file_exists( $import_file_info['local_theme_options'] ) ) {
				$downloaded_files['customizer'] = $import_file_info['local_theme_options'];
			}
		}
		// ----- Set form file path -----
		// Get form file as well. If defined!
		if ( ! empty( $import_file_info['form_data'] ) ) {
			// Set the filename string for form import file.
			$form_filename = apply_filters( 'templify-import-templates/downloaded_forms_file_prefix', 'demo-forms-import-file_' ) . self::$demo_import_start_time . apply_filters( 'templify-import-templates/downloaded_form_file_suffix_and_file_extension', '.json' );

			// Download the form import file.
			$downloaded_files['forms'] = $downloader->download_file( $import_file_info['form_data'], $form_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['forms'] ) ) {
				return $downloaded_files['forms'];
			}
		} else if ( ! empty( $import_file_info['local_form_data'] ) ) {
			if ( file_exists( $import_file_info['local_form_data'] ) ) {
				$downloaded_files['forms'] = $import_file_info['local_form_data'];
			}
		}
		// ----- Set give form file path -----
		// Get form file as well. If defined!
		if ( ! empty( $import_file_info['give_donation_data'] ) ) {
			// Set the filename string for form import file.
			$give_filename = apply_filters( 'templify-import-templates/downloaded_forms_file_prefix', 'demo-give-donations-import-file_' ) . self::$demo_import_start_time . apply_filters( 'templify-import-templates/downloaded_give_donations_file_suffix_and_file_extension', '.json' );

			// Download the form import file.
			$downloaded_files['give-donations'] = $downloader->download_file( $import_file_info['give_donation_data'], $give_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['give-donations'] ) ) {
				return $downloaded_files['give-donations'];
			}
		}
		// ----- Set give form file path -----
		// Get form file as well. If defined!
		if ( ! empty( $import_file_info['give_form_data'] ) ) {
			// Set the filename string for form import file.
			$give_form_filename = apply_filters( 'templify-import-templates/downloaded_forms_file_prefix', 'demo-give-forms-import-file_' ) . self::$demo_import_start_time . apply_filters( 'templify-import-templates/downloaded_give_form_file_suffix_and_file_extension', '.json' );

			// Download the form import file.
			$downloaded_files['give-forms'] = $downloader->download_file( $import_file_info['give_form_data'], $give_form_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['give-forms'] ) ) {
				return $downloaded_files['give-forms'];
			}
		}
		// Get the slider
		if ( ! empty( $import_file_info['depicter_data'] ) ) {
			// Set the filename string for form import file.
			$depicter_filename = apply_filters( 'templify-import-templates/downloaded_depicter_file_prefix', 'demo-depicter-import-file_' ) . self::$demo_import_start_time . apply_filters( 'templify-import-templates/downloaded_depicter_file_suffix_and_file_extension', '.zip' );

			// Download the form import file.
			$downloaded_files['depicter'] = $downloader->download_file( $import_file_info['depicter_data'], $depicter_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['depicter'] ) ) {
				return $downloaded_files['depicter'];
			}
		}

		return $downloaded_files;
	}


	/**
	 * Write the error to the log file and send the AJAX response.
	 *
	 * @param string $error_text text to display in the log file and in the AJAX response.
	 * @param string $log_file_path path to the log file.
	 * @param string $separator title separating the old and new content.
	 */
	public static function log_error_and_send_ajax_response( $error_text, $log_file_path, $separator = '' ) {
		if ( apply_filters( 'templify_import_templates_save_log_files', false ) ) {
			// Add this error to log file.
			$log_added = self::append_to_file(
				$error_text,
				$log_file_path,
				$separator
			);
		}

		// Send JSON Error response to the AJAX call.
		wp_send_json( $error_text );
	}



	/**
	 * Process import file - this parses the widget data and returns it.
	 *
	 * @param string $file path to json file.
	 * @return object $data decoded JSON string
	 */
	private static function process_import_file( $file ) {
		// File exists?
		if ( ! file_exists( $file ) ) {
			return new \WP_Error(
				'form_import_file_not_found',
				__( 'Error: Form import file could not be found.', 'templify-import-templates' )
			);
		}
		$data = give_get_raw_data_from_file( $file, 1, 25, ',' );
		// // Get file contents and decode.
		// $data = Helpers::data_from_file( $file );

		// Return from this function if there was an error.
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $data;
	}

	/**
	 * Import raw data forms.
	 *
	 * @param string $raw_data the data for the forms.
	 */
	public static function import_data( $raw_data ) {
		// Have valid data? If no data or could not decode.
		if ( empty( $raw_data ) ) {
			return new \WP_Error(
				'corrupted_give_import_data',
				__( 'Error: Give import data could not be read. Please try a different file.', 'templify-import-templates' )
			);
		}
		$import_setting = [];
		$raw_key = maybe_unserialize( 'a:29:{i:0;s:2:"id";i:1;s:0:"";i:2;s:6:"amount";i:3;s:8:"currency";i:4;s:0:"";i:5;s:11:"post_status";i:6;s:9:"post_date";i:7;s:9:"post_time";i:8;s:7:"gateway";i:9;s:4:"mode";i:10;s:7:"form_id";i:11;s:10:"form_title";i:12;s:10:"form_level";i:13;s:10:"form_level";i:14;s:12:"title_prefix";i:15;s:10:"first_name";i:16;s:9:"last_name";i:17;s:5:"email";i:18;s:12:"company_name";i:19;s:5:"line1";i:20;s:5:"line2";i:21;s:4:"city";i:22;s:5:"state";i:23;s:3:"zip";i:24;s:7:"country";i:25;s:0:"";i:26;s:7:"user_id";i:27;s:8:"donor_id";i:28;s:8:"donor_ip";}' );
		$import_setting['raw_key'] = $raw_key;
		$import_setting['dry_run'] = false;
		$main_key = maybe_unserialize( 'a:29:{i:0;s:11:"Donation ID";i:1;s:15:"Donation Number";i:2;s:14:"Donation Total";i:3;s:13:"Currency Code";i:4;s:15:"Currency Symbol";i:5;s:15:"Donation Status";i:6;s:13:"Donation Date";i:7;s:13:"Donation Time";i:8;s:15:"Payment Gateway";i:9;s:12:"Payment Mode";i:10;s:7:"Form ID";i:11;s:10:"Form Title";i:12;s:8:"Level ID";i:13;s:11:"Level Title";i:14;s:12:"Title Prefix";i:15;s:10:"First Name";i:16;s:9:"Last Name";i:17;s:13:"Email Address";i:18;s:12:"Company Name";i:19;s:9:"Address 1";i:20;s:9:"Address 2";i:21;s:4:"City";i:22;s:5:"State";i:23;s:3:"Zip";i:24;s:7:"Country";i:25;s:13:"Donor Comment";i:26;s:7:"User ID";i:27;s:8:"Donor ID";i:28;s:16:"Donor IP Address";}' );
		// Prevent normal emails.
		remove_action( 'give_complete_donation', 'give_trigger_donation_receipt', 999 );
		remove_action( 'give_insert_user', 'give_new_user_notification', 10 );
		remove_action( 'give_insert_payment', 'give_payment_save_page_data' );
		$current_key = 1;
		foreach ( $raw_data as $row_data ) {
			$import_setting['donation_key'] = $current_key;
			give_save_import_donation_to_db( $raw_key, $row_data, $main_key, $import_setting );
			$current_key ++;
		}

		// Check if function exists or not.
		if ( function_exists( 'give_payment_save_page_data' ) ) {
			add_action( 'give_insert_payment', 'give_payment_save_page_data' );
		}

		$results = array(
			'message' => __( 'Give data has been successfully imported.', 'templify-import-templates' ),
		);
		// Return results.
		return apply_filters( 'templify-import-templates/give_import_results', $results );
	}

	
	/**
	 * Check if the AJAX call is valid.
	 */
	public static function verify_ajax_call() {
		check_ajax_referer( 'templify-ajax-verification', 'security' );

		// Check if user has the WP capability to import data.
		if ( ! current_user_can( 'import' ) ) {
			wp_die(
				sprintf(
					__( '%sYour user role isn\'t high enough. You don\'t have permission to import demo data.%s', 'templify-import-templates' ),
					'<div class="notice  notice-error"><p>',
					'</p></div>'
				)
			);
		}
	}


	/**
	 * Get log file path
	 *
	 * @return string, path to the log file
	 */
	public static function get_log_path() {
		$upload_dir  = wp_upload_dir();
		$upload_path = apply_filters( 'templify-import-templates/upload_file_path', trailingslashit( $upload_dir['path'] ) );

		$log_path = $upload_path . apply_filters( 'templify-import-templates/log_file_prefix', 'log_file_' ) . self::$demo_import_start_time . apply_filters( 'templify-import-templates/log_file_suffix_and_file_extension', '.txt' );

		self::register_file_as_media_attachment( $log_path );

		return $log_path;
	}


		/**
	 * Set the $demo_import_start_time class variable with the current date and time string.
	 */
	public static function set_demo_import_start_time() {
		self::$demo_import_start_time = date( apply_filters( 'templify-import-templates/date_format_for_file_names', 'Y-m-d__H-i-s' ) );
	}



	/**
	 * Set the templify transient with the current importer data.
	 *
	 * @param array $data Data to be saved to the transient.
	 */
	public static function set_import_data_transient( $data ) {
		set_transient( 'templify_importer_data', $data, 0.1 * HOUR_IN_SECONDS );
	}


}
