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
		type: 'text',
		name: 'caption',
		caption: 'Название',
	}

	TAO.fields.documents.build_form_items = function(items) {
		res = []
		$.each(items, function(name, config) {
			config['name'] = name;
			config = $.extend(true, {}, TAO.fields.documents.default_form_config, config);
			if (config['xtype'] == 'textfield') {
				config['type'] = 'text';
			}
			if ('fieldLabel' in config) {
				config['caption'] = config['fieldLabel'];
			}
			// !!!!!!
			if (config['xtype'] == 'datefield' || config['type'] == 'date') {
				config['datepicker'] = {"defaultDate" : new Date()};
				config['type'] = "text";
				if (config['format']) {
					config.datepicker.dateFormat = config['format'].replace('Y', 'yy');
				}
			}
			delete config['xtype'];
			delete config['fieldLabel'];
			res.push(config);
		})
		return res;
	}

	TAO.fields.documents.run = function(list, field_name, field) {

	
		var uidialog = TAO.popup.create({}, {}, 'uidialog');

		$('body').trigger('documents_run', arguments);

		$(list).find('table').tableDnD({
				onDrop:function(table, row) {
					TAO.fields.documents.submit(field)
				},
				dragHandle: ".order"
		});
			
		var form_items = TAO.fields.documents.build_form_items(TAO.settings.fields[field_name].fields);
		var items = form_items;

		var $form = $('<form class="white-popup popup-form popup-hide" title="Изменение параметров файла"></form>').appendTo('body').dform({
			"html" : items
		});

		$form.__element = null;

		// обработчики на кноки: запись данных и закрытие
		// открытие диалога
		$('.attachment-icon-edit', list).click(function(e) {
			e.preventDefault();
			var button = $(this);
			var el = button.parents('.field-attaches-row');
			$form.__element = el;
			uidialog.inline({
				content: $form,
				width:'auto',
				draggable: false,
				modal: true,
				callbacks: {
					open : function() {
						// присвоить значения из данных
						$form.each(function(){
							this.reset();
						});
						$(el[0].attributes).each(function() {
							var aname = this.nodeName;
							var avalue = this.nodeValue;
							if (aname.substring(0, 7) == 'data-f-') {
								var fname = aname.substring(7);
								var $ff = $('[name="'+fname+'"]', $form);
								if ($ff.length > 0) {
									//?????
									if (!avalue && $ff.hasClass('hasDatepicker')) {
										$ff.datepicker("setDate", $ff.datepicker( "option", "defaultDate"));
									} else {
										$ff.val(avalue);
									}
								}
							}
						});
					}
				},
				buttons: [
					{
						text: "Сохранить",
						click: function(e) {
							e.preventDefault();
							if (!$form.__element) {
								return false;
							}
							var el = $form.__element;
							var vals = $form.serializeArray();
							for (k in vals) {
								var value = vals[k]['value'];
								var name = vals[k]['name'];
								//if (k == 'caption' && !value) {
								//		value = el.attr('data-f-name');
								//	}
								if (typeof value != 'undefined') {
									el.attr('data-f-' + name, value)
									var td = el.find('td.'+name);
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
							TAO.fields.documents.submit(field);
							uidialog.close({content: $form});
							return false;
						}
					},
					{
						text: "Отмена",
						click: function(e) {
							e.preventDefault();
							uidialog.close({content: $form});
							return false;
						}
					}
				]
			});
			return false;
		});
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



});
