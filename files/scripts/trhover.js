$(function(){
	$('tr.hoverable').each(function(){
		var tr = $(this);
		var classover = tr.attr('classover');
		var classout = tr.attr('classout');
		tr
			.bind('mouseenter',function(){
				$(this).removeClass(classout).addClass(classover);
			})
			.bind('mouseleave',function(){
				$(this).removeClass(classover).addClass(classout);
			})
		;
	});
});