
jQuery(function ($) {
// Initialize configData
var configData = null;

// Perform AJAX request
$.ajax({
    url: templify_importer_templates.ajax_url,
    type: 'GET',
    data: {
        action: 'get_custom_filter_data'
    },
    success: function(response) {
        // Iterate over keys in response.data to find the relevant object
        Object.keys(response.data).forEach(function(key) {
            var obj = response.data[key];
            // Check if obj has the properties you need (e.g., slug)
            if (obj && obj.hasOwnProperty('slug')) {
                // Use this object (obj) as configData
                configData = obj;
                return false; // Exit forEach loop once found
            }
        });


        if (configData) {
            // Access properties of configData
            var image = configData.image;
            var name = configData.name;

			initialAjaxCall(configData.slug);

			console.log(configData.plugins);

            // Create HTML dynamically using configData properties
            var html = '<div class="templify_theme_starter_dash_inner">';
				html += '<div class="main-panel">';
					html += '<div class="templify-site-grid-wrap">';
						html += '<div class="templates-grid">';
							html += '<div class="templify-importer-main-panel">';
								html += '<div class="kst-template-item">';
									html += '<button type="button" class="components-button kst-import-btn is-small">';
								html += '<div class="LazyLoad is-visible">';
									html += '<img src="' + image + '" alt="' + name + '">';
								html += '</div>';
									html += '<div class="demo-title"><h4>' + name + '</h4></div>';
								html += '</div>';
							html += '</div>';
						html += '</div>';
					html += '</div>';
				html += '</div>';
            html += '</div>';

            // Append HTML to target element
            $('.templify_importer_dashboard_main').after(html);
        } else {
            console.log('No object with required properties found in response.');
        }
    },
    error: function(error) {
        console.log('Error:', error);
    }
});


	function initialAjaxCall(configData) {
		var data = new FormData();
		
		data.append('action', 'templify_import_initial');
		data.append('security', templify_importer_templates.ajax_nonce);
		data.append('override_colors', 'true');
		data.append('override_fonts', 'true');
		data.append('builder', 'custom');
		data.append('selected', configData);
		data.append('configData',configData);

		ajaxCall(data);
	}

	function ajaxCall(data) {
		$.ajax({
			method: 'POST',
			url: templify_importer_templates.ajax_url,
			data: data,
			contentType: false,
			processData: false,
		})
		.done(function (response) {
			console.log(response);

			if (response.status === 'initialSuccess') {
				var newData = new FormData();
				newData.append('action', 'templify_import_demo_data');
				newData.append('security', templify_importer_templates.ajax_nonce);
				newData.append('override_colors', 'true');
				newData.append('override_fonts', 'true');
				newData.append('builder', 'custom');
				newData.append('selected', data.configData);
				ajaxCall(newData);
			} else if (response.status === 'newAJAX') {
                console.log('newajax call');
				ajaxCall(data);
			} else if (response.status === 'customizerAJAX') {
				var newData = new FormData();
				newData.append('action', 'templify_import_customizer_data');
				newData.append('security', templify_importer_templates.ajax_nonce);
				newData.append('wp_customize', 'on');
				console.log('customizerajax call');
				ajaxCall(newData);
			} else if (response.status === 'afterAllImportAJAX') {
				var newData = new FormData();
				newData.append('action', 'templify_after_import_data');
				newData.append('security', templify_importer_templates.ajax_nonce);
				console.log('afterAllImportajax call');
				ajaxCall(newData);
			}
		});
	}
	


 // Assuming imageSrc is defined globally or earlier in the script
 //var imageSrc = templify_importer_templates.custom_icon; // Replace with the actual variable or value



 $(document).on('click', '.kst-import-btn', function() {
    $.ajax({
        url: templify_importer_templates.ajax_url,
        type: 'POST',
        data: {
            action: 'load_create_plugin_template'
        },
        success: function(response) {
            // Append the response to a specific element
            $('.templify_theme_starter_dash_inner').append(response);
        }
    });
});



	
});

