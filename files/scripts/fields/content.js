$(function() {

window.TAO = window.TAO || {};
TAO.fields = TAO.fields || {};

TAO.fields.content = function(field) {
	var ff = field;
	var labels = $('.field-content-selection-label', field);
	if (labels.length > 0) {
		var txw = labels.parents('.field').find('textarea').width();
		var sel = labels.siblings('select');
		labels.css('margin-left', txw - labels.outerWidth(true) - sel.outerWidth(true) - 5);
	}
	
	$('.field-content-selection .field-content-selection-item',field).each(function() {
	var el = $(this);
	var fcode = el.parent().siblings('input[type="hidden"]').val();
	field = $('.' + el.attr('data-for'), el.parent().parent());
	var selector = field.find('textarea');
	var editor = TAO.editor.get(selector);
	if (el.attr('data-format') == fcode) {
		if (el.is('option')) {
			el.attr('selected', true);
		}
		else {
			el.addClass('active');
		}
		field.siblings().removeClass('field-content-pane-current');
		field.addClass('field-content-pane-current');
		if (editor && typeof editor.refresh === 'function') {
			editor.refresh(selector);
		}
		if (editor && typeof editor.enable === 'function') {
			editor.enable(selector);
		}
	} else {
		// hack to allow initialize fat editors like tinyMCE
		var attempt = 0;
		var interval_id = setInterval(function() {
			editor = TAO.editor.get(selector);
			if (editor && typeof editor.disable === 'function') {
				clearInterval(interval_id);
				editor.disable(selector);
			}
			if (attempt >= 3) {
				clearInterval(interval_id);
			}
			attempt++;
		}, 400);
	}
	
	var switch_content = function() {
		var ell;
		if (el.is('option'))
			ell = $(this).find(':selected');
		else
			ell = el;
		var field;
		if ($('.field-lang-visible',ff).length)
			field = $('.field-lang-visible .' + ell.attr('data-for'), ff);
		else
			field = $('.' + ell.attr('data-for'), ff);
		var current = field.siblings('.field-content-pane-current');
		if (field.length <= 0) return;
		ell.addClass('active');
		ell.siblings().removeClass('active');
		var to_editor, from_editor;
		var to_selector, from_selector;
		if (current.length) {
			var val = $(current.find('textarea')).val();
			to_selector = field.find('textarea'),
				from_selector = current.find('textarea');
			to_editor = TAO.editor.get(to_selector),
				from_editor = TAO.editor.get(from_selector);
			val = from_editor ? from_editor.getContent(from_selector) : val;
			if (to_editor) {
				to_editor.setContent(to_selector, val);
			}
			else {
				field.find('textarea').val(val);
			}
		}
		field.siblings().removeClass('field-content-pane-current');
		field.addClass('field-content-pane-current');
		var format = ell.parent().siblings('input[type="hidden"]');
		format.val(ell.attr('data-format'));

		if (to_editor && typeof to_editor.refresh === 'function') {
			to_editor.refresh(to_selector);
		}

		if (to_editor && typeof to_editor.enable === 'function') {
			to_editor.enable(to_selector);
		}
		if (from_editor && typeof from_editor.disable === 'function') {
			from_editor.disable(from_selector);
		}
	};
	
	if (el.is('option'))
		el.parent().change(switch_content);
	else
		el.click(switch_content);
	
});
};

});




