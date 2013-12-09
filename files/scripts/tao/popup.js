window.TAO = window.TAO || {};
TAO.popup = TAO.popup || { plugins : {}, default_plugin: 'magnific' };



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
		plugin = TAO.popup.default_plugin;
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

TAO.popup.inline = function(opts) {
	return TAO.popup.get_current_plugin().inline(opts);
}

TAO.popup.ajax = function(opts) {
	return TAO.popup.get_current_plugin().ajax(opts);
}

TAO.popup.close = function(opts) {
	return TAO.popup.get_current_plugin().close(opts);
}

TAO.popup.open = function(opts) {
	return TAO.popup.get_current_plugin().open(opts);
}

TAO.popup.process = function(opts) {
	return TAO.popup.get_current_plugin().process(opts);
}

TAO.popup.get_instance = function(opts) {
	return TAO.popup.get_current_plugin().get_instance(opts);
}

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
		if (number >= count) {
			// load js
			for (k in paths.js) {
				// TODO: maybe use TAO.require
				TAO.helpers.addScript(paths.js[k], callback);
			}
		}
	};
	for (k in paths.css) {
		TAO.helpers.addStylesheet(paths.css[k], wait_all_css);
	}
}

// magnific

TAO.popup.plugins.magnific = function(options) {
	var self = this;

	return	{

		inline: function(opts) {
			if (typeof(opts) === 'undefined') opts = {};
			if (typeof(opts.callbacks) === 'undefined') opts.callbacks = {};
			var inline_config = {
				items: {
					src: opts.content,
					type: 'inline',
				}
				,callbacks: opts.callbacks
			};
			delete opts.content;
			opts = $.extend(true, {}, opts, inline_config);
			this.get_instance().open(opts);
		},

		process: function (opts) {
			var content = opts.content;
			delete opts.content;
			return $(content).magnificPopup(opts);
		},

		ajax: function(opts) {
			var content = opts.content;
			delete opts.content;
			opts.type = 'ajax';
			return $(content).magnificPopup(opts);
		},

		close: function(opts) {
			$.magnificPopup.instance.close();
		},

		open: function (opts) {
			this.get_instance().open(opts);
		},

		get_instance: function(opts) {
			return $.magnificPopup;
		}
	}
};

TAO.popup.plugins.magnific.initialize = function(opts, callback) {
	if (typeof(opts) === 'undefined') opts = {};
	if (!this.init_complete) {
		var p = TAO.settings.base_lib_static_path;
		var paths = opts.paths || {
			js: [p + 'scripts/jquery/popup/magnific-popup.js'],
			css: [p + 'styles/jquery/magnific-popup.css', p + 'styles/tao/popup.css']
		};
		paths = TAO.popup.extra_paths(paths, opts);
		TAO.popup.include(paths, function() {
			if (callback) {
				callback(TAO.popup.type('magnific'));
			}
		});
	} else {
		if (callback) {
			callback(TAO.popup.type('magnific'));
		}
	}
	this.init_complete = true;
}

// colorbox

TAO.popup.plugins.colorbox = function(options) {
	var self = this;

	return	{

		inline: function(opts) {
			if (typeof(opts) === 'undefined') opts = {};
			if (typeof(opts.callbacks) === 'undefined') opts.callbacks = {};
			$.colorbox({inline:true, href:opts.content, onComplete: opts.callbacks.open, onClosed: opts.callbacks.close});
		},

		ajax: function(opts) {
			return this.process(opts);
		},

		process: function(opts) {
			var content = opts.content;
			delete opts.content;
			return $(content).colorbox(opts);
		},

		close: function(opts) {
			$.colorbox.close();
		},

		open: function (opts) {
			$.colorbox(opts);
		},

		get_instance: function(opts) {
			return $.colorbox;
		}
	}
};

TAO.popup.plugins.colorbox.initialize = function(opts, callback) {
	if (typeof(opts) === 'undefined') opts = {};
	if (!this.init_complete) {
		var p = TAO.settings.base_lib_static_path;
		var paths = opts.paths || {
			js: [p + 'scripts/jquery/popup/colorbox.js'],
			css: [p + 'styles/jquery/colorbox/colorbox.css', p + 'styles/tao/popup.css']
		};
		paths = TAO.popup.extra_paths(paths, opts);
		TAO.popup.include(paths, function() {
			if (callback) {
				callback(TAO.popup.type('colorbox'));
			}
		});
	} else {
		if (callback) {
			callback(TAO.popup.type('colorbox'));
		}
	}
	this.init_complete = true;
}



// jquery ui dialog


TAO.popup.plugins.uidialog = function(options) {
	var self = this;

	return	{

		inline: function(opts) {
			if (typeof(opts) === 'undefined') opts = {};
			if (typeof(opts.callbacks) === 'undefined') opts.callbacks = {};
			var content = $(opts.content);
			opts = $.extend(true, {}, opts, {open: opts.callbacks.open, close: opts.callbacks.close, zindex: 9999});
			delete opts.content;
			this._fix(content);
			content.dialog(opts);
		},

		process: function(opts) {
			return self.inline(opts);
		},

		ajax: function(opts) {

		},

		close: function(opts) {
			$(opts.content).dialog('close');
		},

		open: function (opts) {
			
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
	if (typeof(opts) === 'undefined') opts = {};
	var self = this;
	if (!this.init_complete) {
		var p = TAO.settings.base_lib_static_path;
		var paths = opts.paths || {
			js: [p + 'scripts/jquery/ui.js'],
			css: [p + 'styles/jquery/ui.css', p + 'styles/tao/popup.css']
		};
		paths = TAO.popup.extra_paths(paths, opts);
		TAO.popup.include(paths, function() { //fix
			$.ui.dialog.prototype._makeDraggable = function() { 
				this.uiDialog.draggable({
					containment: false
				});
				if (callback) {
					callback(TAO.popup.type('uidialog'));
				}
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
		});
	} else {
		if (callback) {
			callback(TAO.popup.type('uidialog'));
		}
	}
	this.init_complete = true;
}



// TODO: modalbox

TAO.popup.plugins.modalbox = function(options) {
	var self = this;

	return	{
	};
};

TAO.popup.plugins.modalbox.initialize = function(opts) {

}