/**
 * Ajax install the Theme Plugin
 *
 */
(function($, window, document, undefined){
	"use strict";
	$(function(){
		$( '.starter-upsell-wrap .starter-upsell-dismiss' ).on( 'click', function( event ) {
			templify_import_dismissNotice();
		} );
		function templify_import_dismissNotice(){
			var data = new FormData();
			data.append( 'action', 'templify_import_dismiss_notice' );
			data.append( 'security', templifyStarterAdmin.ajax_nonce );
			$.ajax({
				url : templifyStarterAdmin.ajax_url,
				method:  'POST',
				data: data,
				contentType: false,
				processData: false,
			});
			$( '.starter-upsell-wrap' ).remove();
		}
	});
})(jQuery, window, document);
