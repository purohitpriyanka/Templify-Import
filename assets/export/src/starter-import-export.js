( function( $, api ) {
	var $window = $( window ),
		$document = $( document ),
		$body = $( 'body' );
	/**
	 * API on ready event handlers
	 *
	 * All handlers need to be inside the 'ready' state.
	 */
	wp.customize.bind( 'ready', function() {
		/**
		 * Init import export.
		 */
		var kadenceImportExport = {
			init: function() {
				$( 'input[name=templify-import-export-button]' ).on( 'click', kadenceImportExport.export );
				$( 'input[name=templify-importer-import-button]' ).on( 'click', kadenceImportExport.import );
				$( 'input[name=templify-import-reset-button]' ).on( 'click', kadenceImportExport.reset );
			},
		
			export: function() {
				window.location.href = TemplifyImporterImport.customizerURL + '?templify-import-export=' + TemplifyImporterImport.nonce.export;
			},
			import: function() {
				var win			= $( window ),
					body		= $( 'body' ),
					form		= $( '<form class="templify-importer-import-form" method="POST" enctype="multipart/form-data"></form>' ),
					controls	= $( '.templify-importer-import-controls' ),
					file		= $( 'input[name=templify-importer-import-file]' ),
					message		= $( '.templify-import-uploading' );
				
				if ( '' == file.val() ) {
					alert( TemplifyImporterImport.emptyImport );
				}
				else {
					win.off( 'beforeunload' );
					body.append( form );
					form.append( controls );
					message.show();
					form.submit();
				}
			},
			reset: function() {
				var data = {
					wp_customize: 'on',
					action: 'templify_import_reset',
					nonce: TemplifyImporterImport.nonce.reset
				};
		
				var r = confirm( TemplifyImporterImport.resetConfirm );
		
				if (!r) return;
		
				$( 'input[name=templify-import-reset-button]' ).attr('disabled', 'disabled');
		
				$.post( ajaxurl, data, function () {
					wp.customize.state('saved').set( true );
					location.reload();
				});
			}
		};
		
		$( kadenceImportExport.init );
	});

} )( jQuery, wp );
