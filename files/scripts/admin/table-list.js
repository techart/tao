$(function() {

	$('table.table-data tr.odd')
		.bind('mouseenter',function() {
			$(this).addClass('odd-hover');
		})
		.bind('mouseleave',function() {
			$(this).removeClass('odd-hover');
		})
	;

	$('table.table-data tr.even')
		.bind('mouseenter',function() {
			$(this).addClass('even-hover');
		})
		.bind('mouseleave',function() {
			$(this).removeClass('even-hover');
		})
	;

});