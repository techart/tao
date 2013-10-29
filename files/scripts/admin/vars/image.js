$(function() {
	$('span.var-image-preview')
		.mouseenter(function() {
			var url = $(this).attr('data-image');
			var cid = $(this).attr('data-container');
			
			var x = Math.floor($(this).offset().left)+'px';
			var y = (Math.floor($(this).offset().top)-160)+'px';
			
			var container = $('<div>')
						.attr('id',cid)
						.css('width','150px')
						.css('height','150px')
						.css('background-color','#fff')
						.css('border','1px solid #ccc')
						.css('background-image','url('+url+')')
						.css('background-repeat','no-repeat')
						.css('background-position','center')
						.css('padding','3px')
						.css('top',y)
						.css('left',x)
						.css('z-index','9')
						.css('position','absolute');
			$('body').append(container);
		})
		.mouseleave(function() {
			var cid = $(this).attr('data-container');
			$('#'+cid).remove();
		});
});