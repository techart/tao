$(function() {
	$('#mainform_parent_id').change(function() {
		var id = $(this).val();
		if (id) {
			var url = $('.varcode-prefix').attr('data-url');
			$.get(url, {id: id}, function(data) {
				$('.varcode-prefix').html(data);
			});
		}
	});
});