window.TAO = window.TAO || {};
TAO.editor = TAO.editor || {plugins : {}};

TAO.editor.get = function(selector)
{
	for (var name in TAO.editor.plugins) {
		p = TAO.editor.plugins[name];
		if (p.has(selector)) {
			return p;
		}
	}
	return null;
};

TAO.editor.has = function(selector)
{
	for (var p in TAO.editor.plugins) {
		if (p.has(selector)) {
			return true;
		}
	}
	return false;
};


TAO.editor.plugins.tiny = function()
{


	return	{

		get : function(selector)
		{
			var id = $(selector).attr('id');
			if (typeof tinyMCE != 'undefined' && tinyMCE.majorVersion <= this.getVersion()) {
				return tinyMCE.get(id);
			}
		},
		getName: function()
		{
			return 'tiny';
		},
		getVersion : function()
		{
			return 3;
		},
		has : function (selector)
		{
			var editor = this.get(selector);
			return editor ? true : false;
		},
		attach : function(selector, options)
		{
			var name = this.getName();
			var editor = this.get(selector);
			if (!$.isEmptyObject(editor)) return editor;
			options = $.extend(true, {}, TAO.settings.editor[name], options);
			return this.create(selector, options);

		},
		detach : function(selector)
		{
			var id = $(selector).attr('id');
			if (typeof tinyMCE != 'undefined') {
				tinyMCE.execCommand('mceRemoveControl', true, id);
			}
		},
		getContent : function(selector)
		{
			var editor = this.get(selector);
			if (!$.isEmptyObject(editor)) {
				return editor.getContent();
			} else {
				return '';
			}
		},
		setContent : function(selector, content)
		{
			var editor = this.get(selector);
			if (!$.isEmptyObject(editor)) {
				return editor.setContent(content, {format : 'raw'});
			} else {
				return null;
			}
		},


		create : function(selector, options)
		{
			$(function () {
				$(selector).tinymce(options);
			});
			return this.get(selector);
		}
	};

}();




TAO.editor.plugins.redactor = function()
{


	return	{
		get : function(selector)
		{
			var $el = $(selector);
			if (typeof $el.getObject === 'function') {
				return $el.getObject();
			}
			return null;
		},
		has : function (selector)
		{
			var obj = this.get(selector);
			return obj ? true : false;
		},
		attach : function(selector, options)
		{
			var self = this;
			$(function () {
				options = $.extend(true, {}, TAO.settings.editor.redactor, options);
				return $(selector).redactor(options);
			});

		},
		detach : function(selector)
		{
			$(selector).destroyEditor();
		},
		getContent: function(selector)
		{
			return $(selector).getCode();
		},
		setContent : function(selector, content)
		{
			return $(selector).setCode(content);
		}
	};

}();


TAO.editor.plugins.nicedit = function()
{

	return	{
		get : function(selector)
		{
			var id = $(selector).attr('id');
			if (id && typeof nicEditors != 'undefined') {
				return nicEditors.findEditor(id);
			}
			return null;
		},
		has : function (selector)
		{
			var editor = this.get(selector);
			return editor ? true : false;
		},
		attach : function(selector, options)
		{
			var self = this;
			$(function () {
				if ($(selector).length) {
					var id = $(selector).attr('id');
					options = $.extend(true, {}, TAO.settings.editor.nicedit, options);
					return self.create(id, options);
				} else {
					return null;
				}
			});

		},
		detach : function(selector)
		{
			return false;
		},
		getContent : function(selector)
		{
			var editor = this.get(selector);
			if (!$.isEmptyObject(editor)) {
				return editor.getContent();
			} else {
				return '';
			}
		},
		setContent : function(selector, content)
		{
			var editor = this.get(selector);
			if (!$.isEmptyObject(editor)) {
				return editor.setContent(content);
			} else {
				return null;
			}
		},

		create : function(id, options) {
			if (typeof nicEditor != 'undefined') {
				var ins = new nicEditor(options).panelInstance(id, {hasPanel : true});
				var elm = $('#'+id).prev().find('.nicEdit-main').get(0);
				elm.addEventListener('keypress', function(ev){
					if(ev.keyCode == '13') {
						document.execCommand('formatBlock', false, 'p');
					}
				}, false);
				return ins;
			}
			return null;
		},
		refresh: function(selector)
		{
			var width = $(selector)[0].style.width;
			var height = $(selector)[0].style.height;
			var container = $(selector).parent();
			if (width) {
				$('.nicEdit-panelContain', container).parent().width(width);
				$('.nicEdit-panelContain', container).parent().next().width(width);
				$('.nicEdit-panelContain', container).parent().next().children(":first").width(width);
			}
		}
	};

}();


TAO.editor.plugins.ckeditor = function()
{

	return	{
		get : function(selector)
		{
			var id = $(selector).attr('id');
			if (id && typeof CKEDITOR != 'undefined') {
				return CKEDITOR.instances[id];
			}
			return null;
		},
		has : function (selector)
		{
			var editor = this.get(selector);
			return editor ? true : false;
		},
		detach : function(selector)
		{
			return false;
		},
		attach : function(selector, options)
		{
			var self = this;
			$(function () {
				var id = $(selector).attr('id');
				options = $.extend(true, {}, TAO.settings.editor.ckeditor, options);
				return self.create(id, options);
			});

		},
		getContent : function(selector)
		{
			var editor = this.get(selector);
			if (editor) {
				return editor.getData();
			} else {
				return '';
			}
		},
		setContent : function(selector, content)
		{
			var editor = this.get(selector);
			if (editor) {
				return editor.setData(content);
			} else {
				return null;
			}
		},

		create : function(id, options) {
			if (typeof CKEDITOR != 'undefined') {
				return CKEDITOR.replace(id);
			}
			return null;
		}

	};

}();



TAO.editor.plugins.epic = function()
{

	return	{
		objects: {},


		get : function(selector)
		{
			var id = $(selector).attr('id');
			if (id && id in this.objects) {
				return this.objects[id];
			}
			return null;
		},
		has : function (selector)
		{
			var editor = this.get(selector);
			return editor ? true : false;
		},
		detach : function(selector)
		{
			return false;
		},
		attach : function(selector, options)
		{
			var self = this;
			$(function () {
				var id = $(selector).attr('id');
				options = $.extend(true, {}, TAO.settings.editor.epic, options, {'container' : id});
				return self.create(options);
			});

		},
		getContent : function(selector)
		{
			var editor = this.get(selector);
			if (editor) {
				return editor.exportFile(null, 'html', true);
			}
			return '';
		},
		setContent : function(selector, content)
		{
			var editor = this.get(selector);
			if (editor) {
				var reMarker = new reMarked();
				var md = reMarker.render(content);
				return editor.importFile(null, md);
			}
			return null;
		},
		refresh: function(selector)
		{
			var editor = this.get(selector);
			if (editor) {
				editor.reflow();
			}
		},

		create : function(options) {
			var self = this;
			var originId = options.container;
			if (typeof EpicEditor != 'undefined') {
				$textarea = $('#'+options.container);
				var tagName = $textarea.prop("tagName");
				var editor = null;
				if (tagName == 'TEXTAREA') {
					options.container = null;
					options.textarea = originId;
					var id = originId+'_epiceditor';
					$container = $('<div id="'+id+'">').insertBefore($textarea);
					options.container = id;
					editor = new EpicEditor(options);
					$textarea.hide();
				} else {
					editor = new EpicEditor(options);
				}
				if (editor) {
					self.objects[originId] = editor;
					editor.load();
					return editor;
				}
				return editor;
			}
			return null;
		}

	};

}();