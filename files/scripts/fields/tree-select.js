$(document).ready(function() {

//TODO: refactor + optimize

$('body').keyup(function(e) {
	if (e.keyCode == 27) $('.tree_select_selectioner:visible').siblings('.tree_select_top').click();
});

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}
TAO.fields.tree_select = function (select) {
	var name = select.attr('class');
	var classes = new Array();
	classes = name.split(' ');
	var regexp = /^name_(.+)$/i;
	for (var i=0; i< classes.length; i++) {
		name = regexp.exec(classes[i]);
		if (name) break;
	}
	class_name = name[0];
	input_name = name[1];
	
	var selector = select;
	var selectioner = selector.find('div.tree_select_selectioner');
	var selectioner_slide_time = 200;
	var ones_list = selector.find('div.tree_select_selectioner div.tree_select_one div.tree_select_node');
	var arrays_icons = selector.find('div.tree_select_elbow_icon');
	var arrays_list = selector.find('div.tree_select_selectioner div.tree_select_array div.tree_select_node');
	var head_arrow = selector.find('div.tree_select_top div.top_arrow');
	var head_caption = selector.find('div.tree_select_top div.top_caption');
	var head = selector.find('div.tree_select_top');
	var subs = selector.find('.tree_select_sub');
	var width_fixer = selector.find('.tree_width_fixer');

	// var input = $('input#mainform_' + input_name);
	var input = $('input', select);
	selectioner.width(selectioner.width() + 17);

	var disclosure_speed = 0;
	
	selectioner.find('.text-wrap_wrap_caption').css('max-width', selectioner.width()-67)
	selectioner.find('.text-wrap_ellipsis_caption').css('max-width', selectioner.width()-67)
	if (isIE8()) {
		selectioner.find('.text-wrap_ellipsis_caption').css('width', selectioner.width()-67)
	}

	function isScrolledIntoView(doc, elem) {
		var docViewTop = $(doc).scrollTop();
		var docViewBottom = docViewTop + $(doc).height();
		var elemTop = $(elem).position().top;
		var elemBottom = elemTop + $(elem).height();
    
		return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
	}
	
	function scroll_and_opening() {
		selectioner.scrollTo('0%');
		var current = $('.tree_select_caption_active', selectioner).parent('.tree_select_node');
		if (selectioner.attr('disclosure')!='true') {
			current.parents('.tree_select_sub').each(
				function() {
					if ($(this).css('display')=='none')
						$(this).prev('.tree_select_node').prev('.tree_select_elbow_icon').click();
				}
			);
			selectioner.attr('disclosure', 'true');
		};
		var lasts = $('.tree_item_last .tree_select_node', selectioner);
		var last = lasts[lasts.length - 1];
		if (current.length > 0 && !isScrolledIntoView(selectioner, current))
			if (current[0] == last) {
				selectioner.animate({scrollTop: selectioner.attr("scrollHeight") + 50 }, 400);
			}
			else 
				selectioner.scrollTo(current, 400, {axis:'y'});
		disclosure_speed = 200;
	};
	
	function isIE8() {
		return document.all && document.querySelector && !document.addEventListener;
	}
	
	function sizing(element) {
		width_fixer.css('width', 'auto');
		width_fixer.width(width_fixer.width() + 30);
		element.attr('opened', 'true');
		if (isIE8()) {
			setTimeout(function() {
				selectioner.height(width_fixer.height() + 20);
			}, 50); 
		}
	}
	
	function selecting(element) {
		var parms = {process: true, element: element, head: head};
		$(select).trigger('before_select',[parms]);
		if (parms.process) {
			ones_list.find('div.tree_select_caption').removeClass('tree_select_caption_active');
			arrays_list.find('div.tree_select_caption').removeClass('tree_select_caption_active');
			element.find('div.tree_select_caption').addClass('tree_select_caption_active');
			head_caption.text(element.text());
			head.click();
		}
	}
	
	head.click(
		function(e) {
			if (selectioner.css('display')=='none') 
				selectioner.slideDown(selectioner_slide_time, function() {
					scroll_and_opening();
			});
			else selectioner.slideUp(selectioner_slide_time);

			setTimeout(function() {
				sizing(selectioner); 
			}, selectioner_slide_time + 10); 
			e.stopPropagation();
		}
	);
	  
	ones_list.click(
		function(e) {
			if (!$(this).hasClass('tree_select_node_disabled')) {
				var parent = $(this).parent('div.tree_select_one');
				var trig = input.val() != parent.attr("id");
				// input.val(parent.attr("id"));
				input.attr('value', parent.attr("id"));
				if (trig) input.trigger('change');
				selecting($(this));
			}
			e.stopImmediatePropagation();
		}
	);
	  
	arrays_list.click(
		function(e) {
			if (!$(this).hasClass('tree_select_node_disabled')) {
				var parent = $(this).parent('div.tree_select_array');
				var trig = input.val() != parent.attr("id");
				// input.val(parent.attr("id"));
				input.attr('value', parent.attr("id"));
				if (trig) input.trigger('change');
				selecting($(this));
			}
		}
		
	);
	  
	arrays_icons.click(
		function(event) {
			var sub = $(this).next('.tree_select_node').next('.tree_select_sub');
			var caption = $(this).next('.tree_select_node').children('.tree_select_caption');
			if (sub.css('display') != 'block') 
			{
				$(this).addClass('tree_select_elbow_open_icon');
				caption.addClass('tree_select_caption_array_open');
				sub.slideDown(disclosure_speed);
				$(this).parent('.tree_select_array').attr('opened', 'true');
				setTimeout(function() {
					sizing($(this).parent('.tree_select_array')); 
				}, disclosure_speed + 10); 
			}
			else 
			{
				sub.slideUp(disclosure_speed);
				$(this).removeClass('tree_select_elbow_open_icon');
				caption.removeClass('tree_select_caption_array_open');
							
				setTimeout(function() {
					sizing($(this).parent('.tree_select_array')); 
				}, disclosure_speed + 10); 
			}
			event.stopPropagation();
		}
	);
	  
	$('html').click(
		function() {
			if (selectioner.css('display') != 'none') selectioner.slideUp(selectioner_slide_time); 
		}
	);
	  
	selector.click(
		function(event) {
			$('div.tree_select', selector).find('div.tree_select_selectioner').not($(this).find('div.tree_select_selectioner')).hide(); 
			event.stopPropagation();
		}
	);
}
});
