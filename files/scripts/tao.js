 window.TAO =  window.TAO || {
	settings: {},
	data: {}
};

window.TAO.settings = $.extend(true, {},  {base_static_path: '/files/', base_lib_static_path: '/tao/'},  window.TAO.settings);

Ext = {
	buildSettings:{
		"scopeResetCSS": true
	}
};

TAO.helpers = TAO.helpers || {};

TAO.helpers.addStylesheet = function(url) {
	if ($('link[href*="' + url + '"]').length > 0) return;
	var link = $("<link>");
	link.attr({
				type: 'text/css',
				rel: 'stylesheet',
				href: url
	});
	return $("head").append( link ); 
};

TAO.helpers.addScript = function (url, callback) {
	var self = this;
	if (!this.stack) {
		this.stack = [];
	}
	if (!this.called) {
		this.called = {};
	}
	if (this.stack.indexOf(url) > -1 || $('script[src*="' + url + '"]').length > 0) {
		this.stack.push(url);
		return callback ? callback() : null;
	}
	if (!this.called[url]) {
		this.called[url] = $.getScript(url);
	}
	return this.called[url]
		.done(function() {
			callback ? callback() : null;
			self.stack.push(url);
			delete self.called[url];
		})
		.fail(function(jqxhr, settings, exception) {
			console.error(url + ":" + exception);
		});
}

TAO.helpers.process_data = function(data) {
	if (data.js) {
			var count = 0;
			var load = 0;
			
		if (data.css) {
			$.each(data.css, function(k, v) {
				TAO.helpers.addStylesheet(k);
			});
		}
			
			$.each(data.js, function(k, v) {
				var sc = TAO.helpers.addScript(k, function() {
					load++;
					if (load == count && data.eval) {
							jQuery.globalEval(data.eval);
						}
				});
				if (sc) count++;
			});
		}
		
		if(count == 0 && data.eval) jQuery.globalEval(data.eval);
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


TAO.require = function(name, callback) {
	var base = TAO.settings.base_lib_static_path + 'scripts/';
	if ($.isArray(name)) {
		var count = name.length;
		var number = 0;
		var check = function() {
			number++;
			if (number == count) {
				return callback();
			}
		};
		for (var i = 0; i < count; i++) {
			var path = base + name[i] + '.js';
			TAO.helpers.addScript(path, check);
		}
	} else {
		var path = base + name + '.js';
		TAO.helpers.addScript(path, callback);
	}
};
