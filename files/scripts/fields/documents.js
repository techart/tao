$(function() {

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}
TAO.fields.documents = TAO.fields.documents || {}

TAO.fields.documents.updateInput = function(field) {
	var data = {"files" : []};
	$(field).find('.field-attaches-row').each(function() {
		if (this.attributes) {
			var fdata = {}
			$.each(this.attributes, function(i, attrib){
				var name = attrib.name;
				var value = attrib.value;
				if (name.substring(0, 6) == 'data-f') {
					fdata[name.substring(7)] = value;
			}
			});
			if (fdata) data.files.push(fdata)
		}
	});
	$(field).find('input').val($.toJSON(data));
}

TAO.fields.documents.submit = function(field) {
	TAO.fields.documents.updateInput(field);
	var input = $(field).find('input');
	var str = input.val();
	var save_url = $(field).find('.attaches-list-container').attr('data-save-url');
	$.post(save_url, {'data': str}, function(data) {
		if (data != 'ok') alert(data);
	});
	
}

TAO.fields.documents.default_form_config = {
	style: 'margin:5px auto 0 auto',
	xtype: 'textfield',
	name: 'caption',
	fieldLabel: 'Название',
	anchor: '90%'
}

TAO.fields.documents.build_form_items = function(el, items) {
	res = []
	$.each(items, function(name, config) {
		config['name'] = name;
		config['value'] = el.attr('data-f-' + name);
		Ext.applyIf(config, TAO.fields.documents.default_form_config);
		if (config['xtype'] == 'datefield' && !config['value'])
			config['value'] = new Date();
		res.push(config);
	})
	return res;
}

TAO.fields.documents.run = function(list, field_name, field) {
	$('body').trigger('documents_run', arguments);

		$(list).find('table').tableDnD({
			onDrop:function(table, row) {
				TAO.fields.documents.submit(field)
			},
			dragHandle: ".order"
	});

	$('.attachment-icon-edit', list).click(function() {
		var button = $(this);
		var el = button.parents('.field-attaches-row');
		var editForm = new Ext.FormPanel(
				{
					bodyPadding: 5,
					//TODO: from config
					defaults: {
						listeners: {
							specialkey: function (field, event) {
								if (event.getKey() == event.ENTER) {
									var b = field.up('panel').down('#submit-button');
									b.handler.call(b.scope,b,Ext.EventObject);
									//field.up('form').getForm().submit();
								}
							}
						}
					},
					items: TAO.fields.documents.build_form_items(el, TAO.settings.fields[field_name].fields),
					buttons: [
		    {
		      text: 'Сохранить',
		      style: 'margin: 5px',
		      height: 30,
		      itemId: 'submit-button',
		      handler: function() {
		      	var vals = editForm.getValues();
		      	for (k in vals) {
		      		var value = vals[k];
		      		//if (k == 'caption' && !value) {
		      		//		value = el.attr('data-f-name');
		      		//	}
		      		if (typeof value != 'undefined') {
		      			el.attr('data-f-' + k, value)
		      			var td = el.find('td.'+k);
		      			if (td.length > 0) {
		      				var link = td.find('a');
		      				if (link.length > 0) {
		      					link.html(value);
		      				} else {
		      					td.html(value);
		      				}
		      			}
		      		}
		      	}
		      	TAO.fields.documents.submit(field)
		      	win.close()
		      }
		    },{
		      text: 'Отмена',
		      height: 30,
		      style: 'margin: 5px',
		      handler: function() {
		      	win.close()
		      }
		    }
		]
				})
		var win = new Ext.Window(
			{
				layout: 'fit',
				width: 500,
				resizable:false,
				title: '<div style="padding:2px 0 2px 10px">Изменение параметров файла</div>',
				//height: 300,
				modal: true,
				//closeAction: 'hide',
				items: editForm
			});
		win.show();
		return false;
	})
	}

TAO.fields.documents.process = function(field) {
	
	var list = $(field).find('.attaches-list');
	var field_name = $(field).find('input').attr('data-field-name');

	TAO.fields.documents.run(list, field_name, field)
	
	//from attaches field
	id = $(list).attr('id');
	$(list).bind('reload', function(e, list, type) {
		TAO.fields.documents.run($(list), field_name, field)
		TAO.fields.documents.updateInput(field);
		if (type == 'upload' && TAO.settings.fields[field_name].autoedit_on_upload)
			$(list).find('.attachment-icon-edit:last').click();
	})

}



})
