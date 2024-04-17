<?php
/**
 * Class for the import actions used in the One Click Demo Import plugin.
 * Register default WP actions for OCDI plugin.
 *
 * @package Templify Import Templates
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class ImportActions {
	/**
	 * Register all action hooks for this class.
	 */
	public function register_hooks() {

        // Special widget import cases.
		add_action( 'templify-import-templates/widget_settings_array', array( $this, 'fix_custom_menu_widget_ids' ) );
    }

    /**
	 * Change the menu IDs in the custom menu widgets in the widget import data.
	 * This solves the issue with custom menu widgets not having the correct (new) menu ID, because they
	 * have the old menu ID from the export site.
	 *
	 * @param array $widget The widget settings array.
	 */
	public function fix_custom_menu_widget_ids( $widget ) {
		// Skip (no changes needed), if this is not a custom menu widget.
		if ( ! array_key_exists( 'nav_menu', $widget ) || empty( $widget['nav_menu'] ) || ! is_int( $widget['nav_menu'] ) ) {
			return $widget;
		}

		// Get import data, with new menu IDs.
		$ocdi                = Importer_Templates::get_instance();
		$content_import_data = $ocdi->importer->get_importer_data();
		$term_ids            = $content_import_data['mapping']['term_id'];

		// Set the new menu ID for the widget.
		if ( is_array( $term_ids ) && isset( $term_ids[ $widget['nav_menu'] ] ) ) {
			$widget['nav_menu'] = $term_ids[ $widget['nav_menu'] ];
		}

		return $widget;
	}

    /**
	 * Execute the widgets import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `templify-import-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function widgets_import( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {
		if ( ! empty( $selected_import_files['widgets'] ) ) {
			WidgetImporter::import( $selected_import_files['widgets'] );
		}
	}

	/**
	 * Execute the customizer import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `templify-import-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function customizer_import_color_only( $selected_import_files ) {
		if ( ! empty( $selected_import_files['customizer'] ) ) {
			CustomizerImporter::import_color( $selected_import_files['customizer'] );
		}
	}

	/**
	 * Execute the customizer import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `templify-import-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function customizer_import_font_only( $selected_import_files ) {
		if ( ! empty( $selected_import_files['customizer'] ) ) {
			CustomizerImporter::import_font( $selected_import_files['customizer'] );
		}
	}
	/**
	 * Execute the customizer import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `templify-import-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function customizer_import( $selected_import_files ) {
		if ( ! empty( $selected_import_files['customizer'] ) ) {
			CustomizerImporter::import( $selected_import_files['customizer'] );
		}
	}


    

}
