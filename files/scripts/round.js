$(function(){
	if (!$.browser.msie) return;

	$('.roundbar').each(function(){
		$('<div>').addClass('round-corner-lt').appendTo(this);
		$('<div>').addClass('round-corner-rt').appendTo(this);
		$('<div>').addClass('round-corner-rb').appendTo(this);
		$('<div>').addClass('round-corner-lb').appendTo(this);
	});	

});