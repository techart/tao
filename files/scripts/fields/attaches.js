$(function() {

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}
TAO.fields.attaches = TAO.fields.attaches || {}

TAO.fields.attaches.list_reload = function(field_name, field) {
	var $list = $("#attaches-list-"+field_name, field);
	var url = $('.addattache', field).attr('data-url-reload');
	var callback = function() {
	 	TAO.fields.attaches.binds_delete_attachment(field_name, field);
	 	$list.trigger('reload', [$list, 'upload']);
	};
	if (url) {
		$list.load(url, {}, callback);
	} else {
		callback();
	}
	TAO.fields.attaches.binds_delete_attachment(field_name, field);
}

TAO.fields.attaches.binds_delete_attachment = function(field_name, field)  {
	$(".delete-attachment", field).unbind('click').click(function() {
		var $d_link = $(this);
		if (confirm(TAO.settings.fields[field_name].confirm)) {//confirm
			var href = $(this).attr('href');
			$.get(href, function() {$d_link.parents('div.attaches-list')[0].__reload()});
			return false;
		}
		return false;
	});
}

TAO.fields.attaches.process = function(field) {
	var $input = $(field).find('input');
	var field_name = $input.attr('data-field-name');
	var $add_button = $('.addattache', field);
	var $ind = $("#attaches-load-indicator-"+field_name, field);

	//console.debug($('#add-attache-'+field_name));

	new AjaxUpload($add_button.attr('id'), {
			action: $add_button.attr('data-url'),
			name: 'attachement[]',
			autoSubmit: true,
			responseType: false,
			multiple: $input.attr('multiple'),
			onSubmit: function(file, extension){
				$ind.show();
				if (TAO.settings.fields[field_name].block)
					$('.attaches-list-container', field).block({message: TAO.settings.messages.processing});
			},
			onComplete: function(file, resp){
				$ind.hide();
				if (TAO.settings.fields[field_name].block)
					$('.attaches-list-container', field).unblock();
				if (resp!='success') {
					alert(resp);
				}
				else {
					TAO.fields.attaches.list_reload(field_name, field);
				}
			}
	});

	//TODO: replace:
	$("#attaches-list-"+field_name, field)[0].__reload = function() {
			var $list = $(this);
			var url = $('.addattache', field).attr('data-url-reload');
			$list.load(url,{},
			 function() {
			 	TAO.fields.attaches.binds_delete_attachment(field_name, field);
			 	$list.trigger('reload', [$list, 'delete']);
			 });
			TAO.fields.attaches.binds_delete_attachment(field_name, field);
	}
	
	TAO.fields.attaches.binds_delete_attachment(field_name, field);

}

});
