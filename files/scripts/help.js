$(function() {
	$('a.helpbox').each(function() {
		var a = $(this);
		var href = '/cms-actions/help/' + a.attr('href') + '/';
		a.click(function() {
			jQuery.fn.modalBox({
				setWidthOfModalLayer: 900,
				directCall : {
					source : href
				}
			});
			return false;
		});
	});
});

