$(function() {
	init_calendar_informer();
});


function init_calendar_informer()
{
	$('.calendar-informer').each(function() {
		var inf = $(this);
		$('.calendar-informer-button',inf).unbind('click').click(function() {
			var url = $(this).attr('data-url');
			$.get(url,function(data) {
				inf.empty().append(data);
				init_calendar_informer();
			});
		});
	});
}
