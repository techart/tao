$(function() {
	
	$('th.table-data-th').each(function() {
		var th = $(this);
		var thd = $('div.ta-th',$(this));
		var sort_asc = false;
		var sort_desc = false;

		var url_desc = th.attr('data-sort-desc');
		if (url_desc!==undefined) {
			sort_desc = true;
		}
		
		var url_asc = th.attr('data-sort-asc');
		if (url_asc!==undefined) {
			sort_asc = true;
		}


		if (sort_asc&&sort_desc) {
			thd.css('padding-right','36px');
		}
		else if (sort_asc||sort_desc) {
			thd.css('padding-right','18px');
		}


		var xr = 1;

		if (sort_desc) {
			var a = $('<a>').addClass('at-sort').addClass('at-sort-desc').html('&nbsp;').attr('href',url_desc).css('top','0').css('right',xr+'px');
			if (th.attr('data-sort-current')=='desc') a.addClass('at-sort-current').attr('href',th.attr('data-url-nosort'));
			$(thd).append(a);
		}
		if (sort_asc) {
			if (sort_desc) xr += 16;
			var a = $('<a>').addClass('at-sort').addClass('at-sort-asc').html('&nbsp;').attr('href',url_asc).css('top','0').css('right',xr+'px');
			if (th.attr('data-sort-current')=='asc') a.addClass('at-sort-current').attr('href',th.attr('data-url-nosort'));
			$(thd).append(a);
		}
	});


});