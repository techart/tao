TAO.data.Store = function(api, parms) {


	function load(parms, callback) {
		if (typeof parms == "function") {
			callback = parms;
			parms = {};	
		}
		$.ajax(api.urls.read, {
			data: parms,
			dataType: 'json',
			success: function(data, status) {
				if (callback) {
					callback(data);
				}
			}
		});
	}

	function post(url, data, callback) {
		$.ajax(url, {
			data: JSON.stringify(data),
			type: 'POST',
			contentType: "application/json; charset=utf-8",
			dataType: 'json',
			success: function(data, status) {
				if (callback) {
					callback(data);
				}
			}
		});
	}

	function update(item, callback, id) {
		var url = api.urls.update;
		if (id) {
			url += "/"+id+"/";
		}
		post(url, item, callback);
	}

	function save(items, callback) {
		var url = api.urls.save;
		post(url, items, callback);
	}

	function destroy(item, callback, id) {
		var url = api.urls.destroy;
		if (id) {
			url += "/"+id+"/";
		} 
		post(url, item, callback);
	}

	return {
		"load": load,
		"update" : update,
		"save" : save,
		"destroy": destroy
	}
};