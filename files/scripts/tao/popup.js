window.TAO = window.TAO || {};
TAO.popup = TAO.popup || { plugins : {}, default_plugin: 'magnific' };

TAO.popup.create = function (options, initialize, plugin) {
	var set_current = false;
	if (typeof(initialize) === 'undefined') initialize = true;
	if (typeof(plugin) === 'undefined') {
		plugin = TAO.popup.default_plugin;
		set_current = true;
	}
	var def = TAO.popup.plugins[plugin];

	// ассинхронная подгрузка, может не успеть загрузиться :-)
	if (initialize) {
		def.initialize(options);
	}

	var res = new def(options);
	if (!TAO.popup.current_plugin && set_current) {
		TAO.popup.current_plugin = res;
	}
	return res;
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

TAO.popup.include = function(paths, callback) {
	for (k in paths.js) {
		TAO.helpers.addScript(paths.js[k], callback);
	}
	for (k in paths.css) {
		TAO.helpers.addStylesheet(paths.css[k]);
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
			return $(conent).magnificPopup(opts);
		},

		ajax: function(opts) {
			var content = opts.content;
			delete opts.content;
			opts.type = 'ajax';
			return $(conent).magnificPopup(opts);
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

TAO.popup.plugins.magnific.initialize = function(opts) {
	if (typeof(opts) === 'undefined') opts = {};
	if (!this.init_complete) {
		var p = TAO.settings.base_lib_static_path;
		var paths = opts.paths || {
			js: [p + 'scripts/jquery/popup/magnific-popup.js'],
			css: [p + 'styles/jquery/magnific-popup.css', p + 'styles/tao/popup.css']
		};
		TAO.popup.include(paths);
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
			return $(conent).colorbox(opts);
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

TAO.popup.plugins.colorbox.initialize = function(opts) {
	if (typeof(opts) === 'undefined') opts = {};
	if (!this.init_complete) {
		var p = TAO.settings.base_lib_static_path;
		var paths = opts.paths || {
			js: [p + 'scripts/jquery/popup/colorbox.js'],
			css: [p + 'styles/jquery/colorbox/colorbox.css', p + 'styles/tao/popup.css']
		};
		TAO.popup.include(paths);
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


TAO.popup.plugins.uidialog.initialize = function(opts) {
	if (typeof(opts) === 'undefined') opts = {};
	if (!this.init_complete) {
		var p = TAO.settings.base_lib_static_path;
		var paths = opts.paths || {
			js: [p + 'scripts/jquery/ui.js'],
			css: [p + 'styles/jquery/ui.css', p + 'styles/tao/popup.css']
		};
		TAO.popup.include(paths, function() { //fix
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
		});
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