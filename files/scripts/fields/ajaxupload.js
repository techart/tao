$(function() {

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}

TAO.fields.ajaxupload = TAO.fields.ajaxupload || {}

TAO.fields.ajaxupload.build_url = function(url, query) {
	if (url.indexOf("?") != -1)
		return url + '&' + query;
	else
		return url + '?' + query;
}

TAO.fields.ajaxupload.bind_info_buttons = function(field_name, field, input) {
	$("#file-button-del-"+field_name, field).unbind().click(function() {
		if (confirm(TAO.settings.fields[field_name].confirm)) {
			var del_url = TAO.fields.ajaxupload.build_url($(this).attr('data-url'),'filename='+TAO.fields.ajaxupload.upload_filename(input));
			TAO.helpers.ajax_block($('td', field), {
				url: del_url
			}).always(function(rc) {
				$("#file-info-"+field_name, field).empty().hide();
				input.attr('value', '#');
			});
		}
	});
}

TAO.fields.ajaxupload.upload_filename = function(input) {
	var filename = input.val();
	if (filename[0] == '#') {
		return filename.substr(1);
	}
	return 'none';
}

TAO.fields.ajaxupload.reload = function(field_name, field, input, filename) {
	input.attr('value','#'+filename);
	TAO.fields.ajaxupload.load_info(field_name, field, input, filename);
}

TAO.fields.ajaxupload.load_info = function(field_name, field, input, filename) {
	var $info = $("#file-info-"+field_name, field);
	$info.load(TAO.fields.ajaxupload.build_url($info.attr('data-url'), 'filename='+filename),{},function() {
			$info.show();
			TAO.fields.ajaxupload.bind_info_buttons(field_name, field, input);
		});
}

TAO.fields.ajaxupload.process = function (field) {
	var $input = $(field).find('input');
	var field_name = $input.attr('data-field-name');
	var $file_info = $("#file-info-"+field_name, field);
	var $file_upload = $("#upload-file-"+field_name, field);
	var filename = $input.val();//.substr(1);

	if (filename) {
		$file_info.show();
		TAO.fields.ajaxupload.bind_info_buttons(field_name, field, $input);
	}
	$file_upload.show();
	var $upload = $("#upload-file-"+field_name, field);
	$upload.attr('id', $upload.attr('id')+Math.floor(Math.random()*10000));
	var $indicator = $("#file-load-indicator-"+field_name, field);
	
	new AjaxUpload($upload.attr('id'), {//!!!!!!
		action: $upload.attr('data-url'),
		name: 'attachement',
		autoSubmit: true,
		responseType: false,
		onSubmit: function(file, extension){
			// $upload.hide();
			$indicator.show();
		},
		onComplete: function(file, resp){
			$indicator.hide();
			if (resp.charAt(0)!='!') {
				$upload.show();
				alert(resp);
			}

			else {
				var filename = resp.substr(1);
				TAO.fields.ajaxupload.reload(field_name, field, $input, filename);
			}
		}
	});
}


});