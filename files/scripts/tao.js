 window.TAO =  window.TAO || {
	settings: {},
	data: {},
	debug : false
};

window.TAO.settings = $.extend(true, {},  {base_static_path: '/files/', base_lib_static_path: '/tao/'},  window.TAO.settings);

Ext = {
	buildSettings:{
		"scopeResetCSS": true
	}
};

TAO.helpers = TAO.helpers || {};

TAO.helpers.addStylesheet = function(url, callback) {
	if ($('link[href*="' + url + '"]').length > 0) {
		if (callback) {
			callback();
		}
	};
	var link = $("<link>");
	link.attr({
				type: 'text/css',
				rel: 'stylesheet',
				href: url
	});
	$("head").append( link );
	if (callback) {
		var id;
		var attempts = 0;
		var time = 200;
		var limit = 5;
		function wait() {
			var styles = document.styleSheets;
			var find = false;
			for (i = 0; i < styles.length; i++) {
				if (styles[i].href && styles[i].href.indexOf(url) !== -1) {
					find = true;
					break;
				}
 			}
 			if (find) {
 				callback();
 			} else if (attempts < limit) {
 				id = setTimeout(wait, time);
 			} else {
 				callback();
 			}
 			attempts += 1;
		}
		id = setTimeout(wait, time);
	}
};

TAO.helpers.scripts = [];

TAO.helpers.addScript = function (url, callback) {
	var self = this;
	if (!this.stack) {
		this.stack = TAO.helpers.scripts;
	}
	if (!this.called) {
		this.called = {};
	}
	if (this.stack.indexOf(url) > -1 || $('script[src*="' + url + '"]').length > 0) {
		this.stack.push(url);
		return callback ? callback(url) : null;
	}
	if (!this.called[url]) {
		this.called[url] = $.getScript(url);
	}
	return this.called[url]
		.done(function() {
			callback ? callback(url) : null;
			self.stack.push(url);
			delete self.called[url];
		})
		.fail(function(jqxhr, settings, exception) {
			console.error(url + ":" + exception);
		});
}

TAO.helpers.process_data = function(data, async) {
	if (typeof async == 'undefined') {
		async = false;
	}

	var original_document_write = document.write;
	document.write = function(content) {
		$('head').append(content);
	};

	var jsCount = 0;
	if (data.js) {
		var files = data.js, filesUrls = [];

		if (typeof files == 'object') {
			files = TAO.helpers.objectToArray(files);
		}
		files.sort(function (x, y) {
			return parseInt(x.weight) - parseInt(y.weight);
		});
		
		for(var key in files) {
			filesUrls.push(key);
		}

		var jsCount = filesUrls.length;

		TAO.require(filesUrls, function () {
			if (data.eval) {
				jQuery.globalEval(data.eval);
			}
			document.write = original_document_write;
		}, async);
	}

	if (jsCount  == 0) {
		if (data.eval) {
			jQuery.globalEval(data.eval);
		}
		document.write = original_document_write;
	}

	if (data.css) {
		$.each(data.css, function (k, v) {
			TAO.helpers.addStylesheet(k);
		});
	}
 };

TAO.settings.messages = {
	processing: 'Обработка'
};


TAO.helpers.ajax_block = function(element, config) {
	var old_config = jQuery.extend({}, config);
	return $.ajax(
		$.extend(
			config,
			{
				beforeSend: function () {
					$(element).block({message: TAO.settings.messages.processing});
					if ($.isFunction(old_config.beforeSend))
						return old_config.beforeSend.apply({}, arguments);
					return true;
				},
				complete: function() {
					$(element).unblock({message: TAO.settings.messages.processing});
					if ($.isFunction(old_config.complete))
						return old_config.complete.apply({}, arguments);
					return true;
				}
			}
		)
	);
};

TAO.helpers.stringToFunction = function(str) {
	var arr = str.split(".");
	var fn = (window || this);
	for (var i = 0, len = arr.length; i < len; i++) {
		fn = fn[arr[i]];
	}
	if (typeof fn !== "function") {
		return false;
		throw new Error("function " + str + " not found");
	}
	return  fn;
}

TAO.helpers.scriptExists = function(url) {
	var scripts = TAO.helpers.scripts;
	return (scripts.indexOf(url) > -1) || $('script[src*="' + url + '"]').length > 0;
}

TAO.helpers.objectToArray = function (obj) {
	var arr = [];
	for (var key in obj) {
		arr[key] = obj[key];
	}
	return arr;
}

TAO.require = function(name, callback, async) {
	if (typeof this.level == 'undefined') {
		this.level = 0;
	} else {
		this.level += 1;
	}
	if (typeof async == 'undefined') {
		async = true;
	}
	var self = this;
	var starting_level = this.level;

	if (TAO.debug) {
		console.log('run require:', name);
		console.log('run method:', async ? 'async' : 'sync');
		console.log('require levels (starting, currnet):', starting_level, self.level);
	}

	var base = TAO.settings.base_lib_static_path + 'scripts/';
	if (!$.isArray(name)) {
		name = [name];
	}
	var count = name.length;
	var number = 0;
	var attempt = {};
	var attempt_limit = 8;
	var attempt_timeout = 50;
	var check = function(_url, wait) {
		if (!attempt[_url]) {
			attempt[_url] = 0;
		}
		attempt[_url] += 1;
		if (TAO.debug) {
			console.log('require attempt', attempt[_url], _url);
			console.log('require check levels', starting_level, self.level);
			console.log('require is exist', TAO.helpers.scriptExists(_url), _url);
		}
		if (!TAO.helpers.scriptExists(_url) && attempt[_url] < attempt_limit && starting_level < self.level) {
			if (TAO.debug) {
				console.log('require levels not synced !!!');
			}
			setTimeout(function() {check(_url, true)}, attempt_timeout);
			return;
		}
		number++;
		if (TAO.debug) {
			console.log('require check numbers', number, count, _url);
		}

		if (!async && number < name.length) {
			TAO.helpers.addScript(name[number], check);
			return;
		}

		if (number == count) {
			self.level = Math.max(0, self.level-1);
			if (TAO.debug) {
				console.log('!! require complete load', _url);
			}
			return callback();
		}
	};
	if(async) {
		for (var i = 0; i < count; i++) {
			var path;
			if (name[i].indexOf('.js') !=-1) {
				path = name[i];
			} else {
				path = base + name[i] + '.js';
			}
			TAO.helpers.addScript(path, check);
		}
	} else {
		TAO.helpers.addScript(name[0], check);
	}
};