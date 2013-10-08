$(function() {

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}

TAO.fields.image = TAO.fields.image || {}

TAO.fields.image.build_url = function(url, query) {
	if (url.indexOf("?") != -1)
		return url + '&' + query;
	else
		return url + '?' + query;
}

TAO.fields.image.bind_info_buttons = function(field_name, field, input) {
	$("#image-button-del-"+field_name, field).unbind().click(function() {
		if (confirm(TAO.settings.fields[field_name].confirm)) {
			var del_url = TAO.fields.image.build_url($(this).attr('data-url'),'filename='+TAO.fields.image.upload_filename(input));
			TAO.helpers.ajax_block($('td', field), {
				url: del_url
			}).always(function(rc) {
				$("#image-info-"+field_name, field).empty().hide();
				input.attr('value', '#');
				$("#image-preview-"+field_name, field).hide();
				$(".upload-image", field).show();
			});
		}
	});
	$("#image-button-left-"+field_name, field).unbind().click(function() {
		var url = $(this).attr('data-url');

		//$('.image-preview', field).add('.image-info', field)
		TAO.helpers.ajax_block($('td', field), {
			url: url,
			data: {filename: TAO.fields.image.upload_filename(input)}
		}).always(function(resp) {
			if (resp.charAt(0)!='!') {
				alert(resp);
			}

			else {
				var filename = resp.substr(1);
				TAO.fields.image.reload(field_name, field, input, filename);
			}
		});

	});
	$("#image-button-right-"+field_name, field).unbind().click(function() {
		var url = TAO.fields.image.build_url($(this).attr('data-url'),'filename='+TAO.fields.image.upload_filename(input));
		TAO.helpers.ajax_block($('td', field), {
			url: url
		}).always(function(resp) {
			if (resp.charAt(0)!='!') {
				alert(resp);
			}

			else {
				var filename = resp.substr(1);
				TAO.fields.image.reload(field_name, field, input, filename);
			}
		});
	});
}

TAO.fields.image.build_preview_url = function(url, filename) {
	return TAO.fields.image.build_url(url,'filename='+filename+'&rand='+Math.floor(Math.random()*10000));
}

TAO.fields.image.upload_filename = function(input) {
	var filename = input.val();
	if (filename[0] == '#') {
		return filename.substr(1);
	}
	return 'none';
}

TAO.fields.image.reload = function(field_name, field, input, filename) {
	var $img = $("#image-preview-"+field_name, field);
	var url = TAO.fields.image.build_preview_url($img.attr('data-url-upload'), filename);
	$img.css('background-image','url('+url+')').show();
	input.attr('value','#'+filename);
	//_<?= $vname ?>TempFile = filename;
	TAO.fields.image.load_info(field_name, field, input, filename);
}

TAO.fields.image.load_info = function(field_name, field, input, filename) {
	var $info = $("#image-info-"+field_name, field);
	$info.load(TAO.fields.image.build_url($info.attr('data-url'), 'filename='+filename),{},function() {
			$info.show();
			TAO.fields.image.bind_info_buttons(field_name, field, input);
		});
}

TAO.fields.image.process = function (field) {
	var $input = $(field).find('input');
	var field_name = $input.attr('data-field-name');
	var $img_preview = $("#image-preview-"+field_name, field);
	var $img_info = $("#image-info-"+field_name, field);
	var $img_upload = $("#upload-image-"+field_name, field);
	var filename = $input.val();//.substr(1);
	var preview_url = $img_preview.attr('data-url');

	if (filename) {
		var url = TAO.fields.image.build_url(preview_url, 'filename='+filename);
		$img_preview.css('background-image','url('+url+')').show();
		$img_info.show();
		TAO.fields.image.bind_info_buttons(field_name, field, $input);
	}
	$img_upload.show();
	var $upload = $("#upload-image-"+field_name, field);
	$upload.attr('id', $upload.attr('id')+Math.floor(Math.random()*10000));
	var $indicator = $("#image-load-indicator-"+field_name, field);

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
				TAO.fields.image.reload(field_name, field, $input, filename);
			}
		}
	});
}


});