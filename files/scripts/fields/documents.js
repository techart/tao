$(function() {
	window.TAO = window.TAO || {}
	TAO.fields = TAO.fields || {}
	TAO.fields.documents = TAO.fields.documents || {}

	TAO.fields.documents.update_input = function(field) {
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

	TAO.fields.documents.submit = function(field, $el, win, $form) {
		TAO.fields.documents.update_input(field);
		var input = $(field).find('input');
		var str = input.val();
		var save_url = $(field).find('.attaches-list-container').attr('data-save-url');
		$.post(save_url, {'data': str}, function(data) {
			if (data != 'ok') alert(data);
			if(typeof(win) != 'undefined')
				win.close({content: $form});
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

	TAO.fields.documents.create_form = function(items) {
		return $('<form class="white-popup popup-form popup-hide" title="Изменение параметров файла"></form>').appendTo('body').dform({
			"html" : items
		});
	}

	TAO.fields.documents.refresh_table_data = function(container, name, value) {
		var td = $('td.' + name, container);
		if (td.length > 0) {
			var link = td.find('a');
			if (link.length > 0) {
				link.html(value);
			} else {
				td.html(value);
			}
		}
	}

	TAO.fields.documents.set_value = function($input, value) {
		if($input.attr('type') != 'file')
			$input.val(value);
	}

	TAO.fields.documents.run = function(list, field_name, field) {
		var uidialog = TAO.popup.create({}, {}, 'uidialog');

		$('body').trigger('documents_run', arguments);

		$(list).find('table').tableDnD({
			onDrop:function(table, row) {
				TAO.fields.documents.submit(field, row);
			},
			dragHandle: ".order"
		});

		var items = TAO.fields.documents.build_form_items(TAO.settings.fields[field_name].fields);
			$form = null;


		// обработчики на кноки: запись данных и закрытие
		// открытие диалога
		$('.attachment-icon-edit', list).click(function(e) {
			e.preventDefault();

			if($form)
				$form.remove();

			$form = TAO.fields.documents.create_form(items);

			var $button = $(this),
				$el = $button.parents('.field-attaches-row');
			$form.__element = $el;

			TAO.popup.type('uidialog', function(win) { win.inline({
				content: $form,
				width:'auto',
				draggable: false,
				modal: true,
				callbacks: {
					open : function() {
						$form.find("[name]").each(function() {
							var $input = $(this);
							var name = $input.attr('name');
							if (!name) {
								return;
							}
							var value = $el.attr('data-f-'+name);
							if (typeof value != 'undefined') {
								if (!value && $input.hasClass('hasDatepicker')) {
									$input.datepicker("setDate", $input.datepicker( "option", "defaultDate"));
								} else {
									if ($input.is("[type='checkbox']")) {
										if (value == 'off') {
											$input.removeAttr('checked');
										} else {
											$input.attr('checked', 'checked');
										}
									} else {
										TAO.fields.documents.set_value($input, value);
									}
								}
							} else {
								TAO.fields.documents.set_value($input, '');
							}

						});

						field.trigger('dialog_open', {field: field, el: $el, form: $form});
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
							var $el = $form.__element;

							$form.find("[name]").each(function() {
								var $input = $(this),
									name = $input.attr('name'),
									attrName = 'data-f-'+name;

								var value = $input.val();
								if ($input.is("[type='checkbox']")) {
									$el.attr(attrName, $input.is(':checked') ? value : 'off');
								} else if (typeof value != 'undefined') {
									$el.attr(attrName, value);
									TAO.fields.documents.refresh_table_data($el, name, value);
								} else {
									$el.removeAttr(attrName);
								}
							});

							TAO.fields.documents.submit(field, $el, win, $form);
							return false;
						}
					},
					{
						text: "Отмена",
						click: function(e) {
							e.preventDefault();
							win.close({content: $form});
							return false;
						}
					}
				]
			})});
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
			TAO.fields.documents.update_input(field);
			if (type == 'upload' && TAO.settings.fields[field_name].autoedit_on_upload)
				$(list).find('.attachment-icon-edit:last').click();
		})

	}



});
