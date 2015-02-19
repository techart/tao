window.TAO = window.TAO || {}
TAO.helpers = TAO.helpers || {}
TAO.form_validation_plugins = TAO.form_validation_plugins || {}

var DefaultFromValidationPlugin = function () {
	return  {
		name: 'default',

		callback: function (form) {
			var self = this;
			$(form).submit(function () {
				var form = $(this);

				var form_valid = true;

				$('.validable', form).each(function () {
					var valid = true;
					var input = $(this).unbind('focus');
					var message = '';

					if (input.attr('data-validate-presence') !== undefined) {
						var value = input.val();
						value = value.replace(/\s+/, '');
						if (value == '') {
							valid = false;
							message = input.attr('data-validate-presence');
						}
					}

					if (input.attr('data-validate-match') !== undefined) {
						var regexp = new RegExp(input.attr('data-validate-match'), input.attr('data-validate-match-mods'));
						var value = input.val();
						if (!value.match(regexp)) {
							valid = false;
							message = input.attr('data-validate-match-message');
						}
					}

					if (valid && input.attr('data-validate-ajax') !== undefined && input.attr('data-field-name') !== undefined) {
						var url = input.attr('data-validate-ajax');
						$.ajaxSetup({async: false});
						var rc = '';
						var request = {};
						request[input.attr('data-field-name')] = input.val();
						$.post(url, request, function (data) {
							rc = data;
						});
						$.ajaxSetup({async: true});
						if (rc != 'ok') {
							valid = false;
							message = rc;
							if (rc == 'error' && input.attr('data-error-message') !== undefined) {
								message = input.attr('data-error-message');
							}
						}
					}

					if (!valid) {
						$(form).addClass('error');
						input.addClass('validation-error').focus(function () {
							$(this).removeClass('validation-error');
						});
						form_valid = false;
						self.on_error(input, message);
					}
				});
				if (form_valid) $(form).removeClass('error');
				return form_valid;
			});
		},

		on_error: function (input, message) {
			alert(message);
		}
	};
};

TAO.form_validation_plugins['default'] = new DefaultFromValidationPlugin();

TAO.helpers.form_validation = function (form) {
	var plugin_name = $(form).attr('data-validation-plugin');
	if (!plugin_name || !(plugin_name in TAO.form_validation_plugins)) {
		plugin_name = 'default';
	}
	var plugin = TAO.form_validation_plugins[plugin_name];
	plugin.callback(form);
}

$(function () {

	$('form').each(function (i, item) {
		TAO.helpers.form_validation(item)
	})

});

