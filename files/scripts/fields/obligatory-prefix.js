$(function() {
	$('input[data-obligatory-prefix]').each(function() {
		var input = $(this);
		var prefix = input.attr('data-obligatory-prefix');
		var check = '';
		
		check_value();
		
		input.change(check_value).keyup(check_value);
		
		function check_value() {
			var v = input.val();
			if (check=='') {
				check = prefix+v;
			}
			if (v.length<prefix.length) {
				input.val(check);
			}
			else if (v.substr(0,prefix.length)!=prefix) {
				input.val(check);
			}
			else {
				check = v;
			}
		}
		
	});
});