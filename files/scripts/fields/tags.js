function tao_fields_tags_init(table,name,field) 
{
	$('div.tao-fields-tags-select-'+table+'-'+name+' span').click(function() {
		var tag = $(this).text();
		var f = $('#'+field);
		var val = f.val();
		val = val.replace(/\s+$/,'');
		val = val.replace(/^\s+/,'');
		if (val!='') val += ', ';
		val += tag;
		f.val(val);
	});
	$('div.tao-fields-tags-select-'+table+'-'+name+' span').on('contextmenu',function() {
		var elem = $(this);
		var id = elem.attr('data-id');
		var tag = elem.text();
		if (confirm('Удалить метку "'+tag+'"?')) {
			var url = elem.attr('data-del');
			$.ajax({
				url: url,
				cache: false,
				success: function(rc) {
					if (rc=='ok') {
						elem.remove();
					}
					
					else {
						alert(rc);
					}
				}
			});
		}
		return false;
	});
}

