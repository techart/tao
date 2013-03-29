$(function() {

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}

TAO.fields.content = function(field) {
	var ff = field;
	var labels = $('.field-content-selection-label', field);
	if (labels.length > 0) {
		var txw = labels.parents('.field').find('textarea').width();
		var sel = labels.siblings('select')
		labels.css('margin-left', txw - labels.outerWidth(true) - sel.outerWidth(true) - 5)
	}
	
	$('.field-content-selection .field-content-selection-item',field).each(function() {
	var el = $(this);
	var fcode = el.parent().siblings('input[type="hidden"]').val();
	if (el.attr('data-format') == fcode) {
		if (el.is('option'))
			el.attr('selected', true);
		else
			el.addClass('active');
		field = $('.' + el.attr('data-for'), el.parent().parent());
		field.show();
		field.siblings().hide();
	}
	
	var switch_content = function() {
		//console.debug(el);
		if (el.is('option'))
			var ell = $(this).find(':selected');
		else
			var ell = el;
		//console.debug(ell, $(this));
		var field;
		if ($('.field-lang-visible',ff).length)
			field = $('.field-lang-visible .' + ell.attr('data-for'), ff);
		else
			field = $('.' + ell.attr('data-for'), ff);
		//console.debug('.' + el.attr('data-for')+' textarea');
		var current = field.siblings(':visible');
		if (field.length <= 0) return;
		ell.addClass('active');
		ell.siblings().removeClass('active');
		if (current.length) {
			var val = current.find('textarea').val();
			if (tinyMCE) {
				var to_editor = tinyMCE.get(field.find('textarea').attr('id'));
				var from_editor = tinyMCE.get(current.find('textarea').attr('id'));
			}
			val = from_editor ? from_editor.getContent() : val;
			if (to_editor) {
				to_editor.setContent(val, {format : 'raw'});
			}
			else {
				field.find('textarea').val(val);
			}
		}
		field.siblings().hide();
		//console.debug(field);
		field.show();
		var format = ell.parent().siblings('input[type="hidden"]');
		format.val(ell.attr('data-format'));
	}
	
	if (el.is('option'))
		el.parent().change(switch_content);
	else
		el.click(switch_content);
	
})
}

})




