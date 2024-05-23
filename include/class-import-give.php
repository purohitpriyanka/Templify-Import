<?php
/**
 * Class for importing fluent data.
 *
 * @package Templify Import Templates
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'GIVE_VERSION' ) ) {
	return;
}




/**
 * Class for importing fluent forms.
 */
class Templify_Import_Templates_Give_Import {

    /**
	 * Import forms from JSON file.
	 *
	 * @param string $give_import_forms_file_path path to the widget import file.
	 */
	public static function import_forms( $give_import_forms_file_path ) {
		$results       = array();
		$templify_import_templates = Importer_Templates::get_instance();
		$log_file_path = $templify_import_templates->get_log_file_path();

		// Import Give forms and return result.
		if ( ! empty( $give_import_forms_file_path ) ) {
			$results = self::import_give_forms( $give_import_forms_file_path );
		}

		// Check for errors, else write the results to the log file.
		if ( is_wp_error( $results ) ) {
			$error_message = $results->get_error_message();

			// Add any error messages to the frontend_error_messages variable in OCDI main class.
			$templify_import_templates->append_to_frontend_error_messages( $error_message );
			
				// Write error to log file.
				Helpers::append_to_file(
					$error_message,
					$log_file_path,
					esc_html__( 'Importing Give forms', 'templify-importer-templates' )
				);
			
		} else {
			$message = ( ! empty( $results['message'] ) ? $results['message'] : esc_html__( 'No results for Give Form import!', 'templify-importer-templates' ) );
			
				// Add this message to log file.
				$log_added = Helpers::append_to_file(
					$message,
					$log_file_path,
					esc_html__( 'Importing Give forms' , 'templify-importer-templates' )
				);
			
		}
	}
	/**
	 * Imports widgets from a json file.
	 *
	 * @param string $data_file path to json file with WordPress widget export data.
	 */
	private static function import_give_forms( $data_file ) {
		// Get widgets data from file.
		$data = self::process_import_form_file( $data_file );

		// Return from this function if there was an error.
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		// error_log( print_r( $data, true ) );
		// Import the form data and save the results.
		return self::import_form_data( $data );
	}

    /**
	 * Import raw data forms.
	 *
	 * @param string $raw_data the data for the forms.
	 */
	public static function import_form_data( $raw_data ) {
		// Have valid data? If no data or could not decode.
		if ( empty( $raw_data ) || ! is_array( $raw_data ) ) {
			return new \WP_Error(
				'corrupted_give_forms_import_data',
				__( 'Error: Widget import data could not be read. Please try a different file.', 'templify-importer-templates' )
			);
		}
		// Begin results.
		$results = array();

		// Loop import data's sidebars.
		foreach ( $raw_data as $form_id => $form_data ) {
			foreach ( $form_data as $data_key => $data_value ) {
				//if ( '_give_sequoia_form_template_settings' === $data_key || '_give_form_template' === $data_key || '_give_goal_color' === $data_key ) {
					if ( is_array( $data_value ) && isset( $data_value[0] ) ) {
						$data_value = maybe_unserialize( $data_value[0] );
					}
					// error_log( print_r( $form_id, true ) );
					// error_log( print_r( $data_key, true ) );
					// error_log( print_r( $data_value, true ) );
					give_update_meta( $form_id, $data_key, $data_value, 'form' );
				//}
			}
		}
		return $results;
	}


    /**
	 * Import forms from JSON file.
	 *
	 * @param string $give_import_file_path path to the widget import file.
	 */
	public static function import( $give_import_file_path ) {
		$results       = array();
		$templify_import_templates = Importer_Templates::get_instance();
		$log_file_path = $templify_import_templates->get_log_file_path();

		// Import widgets and return result.
		if ( ! empty( $give_import_file_path ) ) {
			$results = self::import_donations( $give_import_file_path );
		}

		// Check for errors, else write the results to the log file.
		if ( is_wp_error( $results ) ) {
			$error_message = $results->get_error_message();
			// Add any error messages to the frontend_error_messages variable in OCDI main class.
			$templify_import_templates->append_to_frontend_error_messages( $error_message );
			
				// Write error to log file.
				Helpers::append_to_file(
					$error_message,
					$log_file_path,
					esc_html__( 'Importing Give Donations', 'templify-importer-templates' )
				);
			
		} else {
			$message = ( ! empty( $results['message'] ) ? $results['message'] : esc_html__( 'No results for Give import!', 'templify-importer-templates' ) );
			
				// Add this message to log file.
				$log_added = Helpers::append_to_file(
					$message,
					$log_file_path,
					esc_html__( 'Importing Give Donations' , 'templify-importer-templates' )
				);
			
		}

	}


    /**
	 * Imports widgets from a json file.
	 *
	 * @param string $data_file path to json file with WordPress widget export data.
	 */
	private static function import_donations( $data_file ) {
		// Get widgets data from file.
		$data = self::process_import_file( $data_file );

		// Return from this function if there was an error.
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Import the widget data and save the results.
		return self::import_data( $data );
	}


    /**
	 * Process import file - this parses the widget data and returns it.
	 *
	 * @param string $file path to json file.
	 * @return object $data decoded JSON string
	 */
	private static function process_import_form_file( $file ) {
		// File exists?
		if ( ! file_exists( $file ) ) {
			return new \WP_Error(
				'form_import_file_not_found',
				__( 'Error: Form import file could not be found.', 'templify-importer-templates' )
			);
		}
		// // Get file contents and decode.
		$data = Helpers::data_from_file( $file );

		// Return from this function if there was an error.
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return maybe_unserialize( $data, true );
	}
}
