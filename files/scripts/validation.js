$(function() {

window.TAO = window.TAO || {}
TAO.helpers = TAO.helpers || {}

TAO.helpers.form_validation = function(form){

$(form).submit(function() {
		var form = $(this);

		var form_valid = true;

		$('.validable',form).each(function() {
				var valid = true;
				var input = $(this).unbind('focus');
				var message = '';

				if (input.attr('data-validate-presence')!==undefined) {
					var value = input.val();
					value = value.replace(/\s+/,'');
					if (value=='') {
						valid = false;
						message = input.attr('data-validate-presence');
					}
				}

				if (input.attr('data-validate-match')!==undefined) {
					var regexp = new RegExp(input.attr('data-validate-match'),input.attr('data-validate-match-mods'));
					var value = input.val();
					if (!value.match(regexp)) {
						valid = false;
						message = input.attr('data-validate-match-message');
					}
				}

				if (valid && input.attr('data-validate-ajax')!==undefined && input.attr('data-field-name')!==undefined) {
					var url = input.attr('data-validate-ajax');
					$.ajaxSetup({async:false});
					var rc = '';
					var request = {};
					request[input.attr('data-field-name')] = input.val();
					$.post(url,request,function(data) {
						rc = data;
					});
					$.ajaxSetup({async:true});
					if (rc!='ok') {
						valid = false;
						message = rc;
						if (rc=='error'&&input.attr('data-error-message')!==undefined) {
							message = input.attr('data-error-message');
						}
					}
				}

				if (!valid) {
					$(form).addClass('error');
					input.addClass('validation-error').focus(function() {
						$(this).removeClass('validation-error');
					});
					form_valid = false;
					alert(message);
				}
		});
		if (form_valid) $(form).removeClass('error');
		return form_valid;
	});
	
}

$('form').each(function(i, item) {TAO.helpers.form_validation(item)})

});

