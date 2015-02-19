window.TAO = window.TAO || {};
TAO.settings = TAO.settings || {}
TAO.settings.popup = {default_plugin: 'magnific'}
TAO.popup = TAO.popup || { plugins : {}};

//------ BASE ----

TAO.popup.create = function (options, initialize, plugin, callback) {
	var set_current = false;
	if (typeof(options) === 'function') {
		callback = options;
		options = {};
	}
	if (typeof(options) === 'string') {
		plugin = options;
		options = {};
	}
	if (typeof(initialize) === 'function') {
		callback = initialize;
		initialize = true;
	}
	if (typeof(initialize) === 'undefined') {
		initialize = true;
	}
	if (typeof(plugin) === 'undefined') {
		plugin = TAO.settings.popup.default_plugin;
		set_current = true;
	}
	var def = TAO.popup.plugins[plugin];

	if (initialize) {
		def.initialize(options, callback);
	}

	var res = new def(options);
	if (!TAO.popup.current_plugin && set_current) {
		TAO.popup.current_plugin = res;
	}
	return res;
}

TAO.popup.type = function(plugin, callback, options, initialize) {
	if (typeof(initialize) === 'undefined') initialize = true;
	if (typeof(options) === 'undefined') options = {};
	return TAO.popup.create(options, initialize, plugin, callback);
}

TAO.popup.get_current_plugin = function () {
	return TAO.popup.current_plugin;
}

TAO.popup.wrap = function(method, type, opts) {
	if (typeof(type) != 'string') {
		opts = type;
		type = undefined;
	}
	return TAO.popup.call(method, type, opts);
}

TAO.popup.call = function(method, type, opts) {
	TAO.popup.last_call_type = type;
	return TAO.popup.type(type, function(obj) {
		obj[method].call(obj, opts);
	});
}

TAO.popup.string_args = function(type, opts) {
	if ((typeof(type) == 'string' && typeof(TAO.popup.plugins[type]) == 'undefined') ||
		typeof(type) != 'string') {
		opts = type;
		type = undefined;
	}
	if (typeof opts == 'string') {
		opts = {content: opts}
	}
	return [type, opts];
}

//------ END BASE ----

// ----- API -----

TAO.popup.inline = function(type, opts) {
	var args = TAO.popup.string_args(type, opts);
	return TAO.popup.call('inline', args[0], args[1]);
}

TAO.popup.close = function(type, opts) {
	if (typeof(type) == 'undefined' && TAO.popup.last_call_type) {
		type = TAO.popup.last_call_type;
	}
	return TAO.popup.wrap('close', type, opts);
}

TAO.popup.process = function(type, opts) {
	var args = TAO.popup.string_args(type, opts);
	return TAO.popup.call('process', args[0], args[1]);
}

TAO.popup.open = function(type, opts) {
	return TAO.popup.inline(type, opts);
}

TAO.popup.get_instance = function(type, opts) {
	return TAO.popup.wrap('get_instance', type, opts);
}

// ----- END API -----

//------ UTILS -------

TAO.popup.extra_paths = function(paths, opts)
{
	if (opts.extra_paths) {
		if (opts.extra_paths.js) {
			paths.js = $.merge(paths.js, opts.extra_paths.js);
		}
		if (opts.extra_paths.css) {
			paths.css = $.merge(paths.css, opts.extra_paths.css);
		}
	}
	return paths;
}

TAO.popup.include = function(paths, callback) {
	var count = paths.css.length;
	var number = 0;
	var wait_all_css = function() {
		number++;
		if (number == count) {
			TAO.require(paths.js, callback);
		}
	};
	for (k in paths.css) {
		TAO.helpers.addStylesheet(paths.css[k], wait_all_css);
	}
}

TAO.popup.initialize = function(self, name, std_paths, opts, callback, on_complete) {
	if (typeof(opts) === 'undefined') opts = {};
	if (typeof self.in_process == 'undefined') {
		self.in_process = false;
	}
	if (typeof self.init_complete == 'undefined') {
		self.init_complete = false;
	}
	self.stack = self.stack || [];
	if (callback){
		self.stack.push(callback);
	}
	if (!self.init_complete && !self.in_process) {
		self.in_process = true;
		var p = TAO.settings.base_lib_static_path;
		var paths = {};
		if (opts.paths) {
			paths = opts.paths;
		} else {
			paths.js = [];
			paths.css = [];
			for (i in std_paths.js) {
				paths.js.push(p+std_paths.js[i])
			}
			for (i in std_paths.css) {
				paths.css.push(p+std_paths.css[i])
			}
		}
		paths = TAO.popup.extra_paths(paths, opts);
		if (!on_complete) {
			on_complete = function() {
				self.init_complete = true;
				TAO.popup.type(name)
			};
		}
		TAO.popup.include(paths, on_complete);
	}
	else if (self.init_complete){
		while(self.stack.length){
			var callback_item = self.stack.pop();
			callback_item(TAO.popup.type(name));
		}
	}	
}

//------ END UTILS -------

// magnific

TAO.popup.plugins.magnific = function(options) {
	var self = this;

	return	{

		inline: function(opts) {
			if (typeof(opts) === 'undefined') opts = {};
			opts = this.callbacks(opts);
			var inline_config = {
				items: {
					src: opts.content,
					type: 'inline',
				}
				,callbacks: opts.callbacks
			};
			opts = $.extend(true, {}, inline_config, opts);
			this.get_instance().open(opts);
			$('.mfp-wrap').removeAttr('tabindex');
		},

		callbacks: function (opts) {
			if (typeof(opts.callbacks) === 'undefined') opts.callbacks = {};
			return $.extend(true, opts, {callbacks : {imageLoadComplete: opts.callbacks.load}});
		},

		process: function (opts) {
			var content = opts.content;
			delete opts.content;
			opts = this.callbacks(opts);
			opts = $.extend({}, {
				type: 'image',
				gallery: {enabled: true}
			}, opts);
			var res = $(content).magnificPopup(opts);
			$('.mfp-wrap').removeAttr('tabindex');
			return res;
		},

		get_type: function() {
			return 'magnific';
		},

		close: function(opts) {
			if ($.magnificPopup.instance) {
				$.magnificPopup.instance.close();
			}
		},

		open: function (opts) {
			return this.inline(opts);
		},

		get_instance: function(opts) {
			return $.magnificPopup;
		}
	}
};

TAO.popup.plugins.magnific.initialize = function(opts, callback) {
	var std_paths = {
		js: ['scripts/jquery/popup/magnific-popup.js'],
		css: ['styles/jquery/magnific-popup.css', 'styles/tao/popup.css']
	}
	TAO.popup.initialize(this, 'magnific', std_paths, opts, callback);
}

// colorbox

TAO.popup.plugins.colorbox = function(options) {
	var self = TAO.popup.plugins.colorbox;
	self.gallery_counter = 0;

	return	{

		inline: function(opts) {
			if (typeof(opts) === 'undefined') opts = {};
			opts = this.callbacks(opts);
			opts.inline = true;
			opts.href = opts.content;
			$.colorbox(opts);
			$('[role="dialog"]').removeAttr('tabindex');
		},

		callbacks: function (opts) {
			if (typeof(opts.callbacks) === 'undefined') opts.callbacks = {};
			return $.extend(true, opts, {onComplete: opts.callbacks.load, onOpen: opts.callbacks.open, onClosed: opts.callbacks.close});
		},

		process: function(opts) {
			var content = opts.content;
			delete opts.content;
			self.gallery_counter += 1
			opts = $.extend({}, {
				rel : 'gal' + self.gallery_counter
			}, opts);
			opts = this.callbacks(opts);
			var res = $(content).colorbox(opts);
			$('.mfp-wrap').removeAttr('tabindex');
			return res;
		},

		get_type: function() {
			return 'colorbox';
		},

		close: function(opts) {
			$.colorbox.close();
		},

		open: function (opts) {
			return this.inline(opts);
		},

		get_instance: function(opts) {
			return $.colorbox;
		}
	}
};

TAO.popup.plugins.colorbox.initialize = function(opts, callback) {
	var std_paths = {
		js: ['scripts/jquery/popup/colorbox.js'],
		css: ['styles/jquery/colorbox/colorbox.css', 'styles/tao/popup.css']
	}
	TAO.popup.initialize(this, 'colorbox', std_paths, opts, callback);
}



// jquery ui dialog


TAO.popup.plugins.uidialog = function(options) {
	var self = TAO.popup.plugins.uidialog;

	return	{

		inline: function(opts) {
			if (typeof(opts) === 'undefined') opts = {};
			var content = $(opts.content);
			opts = this.callbacks(opts);
			this._fix(content);
			self.last_content = content;
			content.dialog(opts);
		},

		callbacks: function(opts) {
			if (typeof(opts.callbacks) === 'undefined') opts.callbacks = {};
			return $.extend(true, {}, opts, {open: opts.callbacks.open, close: opts.callbacks.close, zindex: 9999});
		},

		process: function(opts) {
			return false;
			return self.inline(opts);
		},

		get_type: function() {
			return 'uidialog';
		},

		close: function(opts) {
			if (self.last_content) {
				$(self.last_content).dialog('close');
			}
		},

		open: function (opts) {
			return this.inline(opts);
		},

		_fix: function(content) {
			$('[role=dialog]').removeAttr('tabindex');
			$('.mfp-wrap').removeAttr('tabindex');
		}

		// get_instance: function(opts) {
		// 	return $(opts.content).dialog$.dialog;
		// }
	}
};


TAO.popup.plugins.uidialog.initialize = function(opts, callback) {
	var self = this;
	var std_paths = {
		js: ['scripts/jquery/ui.js'],
		css: ['styles/jquery/ui.css', 'styles/tao/popup.css']
	}
	var on_complete = function() {
		self.init_complete = true;
		$.ui.dialog.prototype._makeDraggable = function() { 
			this.uiDialog.draggable({
				containment: false
			});
		};
		$.widget("ui.dialog", $.extend({}, $.ui.dialog.prototype, {
			_createOverlay: function() {
				if ( !this.options.modal ) {
					return;
				}
				this.overlay = $("<div>")
					.addClass("ui-widget-overlay ui-front")
					.appendTo( this._appendTo() );
				this._on( this.overlay, {
					mousedown: "_keepFocus"
				});
				this.document.data( "ui-dialog-overlays",
					(this.document.data( "ui-dialog-overlays" ) || 0) + 1 );
			}
		}));
		if (callback) {
			callback(TAO.popup.type('uidialog'));
		}
	};
	TAO.popup.initialize(this, 'uidialog', std_paths, opts, callback, on_complete);
}



// TODO: modalbox

TAO.popup.plugins.modalbox = function(options) {
	var self = this;

	return	{
	};
};

TAO.popup.plugins.modalbox.initialize = function(opts) {

}