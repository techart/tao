function init_mce(options) {
	var default_options = {
		theme : 'advanced',
		mode : 'specific_textareas',
		elements : "editor_textarea",
		editor_selector : 'mce-advanced',
		content_css : "/styles/editor.css",
		paste_auto_cleanup_on_paste : true,
		dialog_type : "modal",
		language : 'ru',
		skin: 'o2k7',
		skin_variant : 'silver',
		plugins : "contextmenu,autosave,paste,preview,fullscreen,table,advimage,media,advlink,inlinepopups",
		//external_image_list_url : imglist,
		relative_urls : false,
		convert_urls: false,
		remove_linebreaks: false,
		theme_advanced_buttons1 : "formatselect,fontselect,fontsizeselect,bold,italic,underline,strikethrough,separator,code,preview,fullscreen,separator,undo,redo",
		theme_advanced_buttons2 : "justifyleft,justifycenter,justifyright,indent,outdent,separator,bullist,numlist,forecolor,backcolor,separator,link,unlink,image,media,table,separator,pastetext,pasteword,selectall,separator,hr",
		theme_advanced_buttons3 : "",
		theme_advanced_resize_horizontal : false,
		theme_advanced_resizing : true,
		theme_advanced_statusbar_location : 'bottom',
		theme_advanced_toolbar_align : 'left',
		theme_advanced_toolbar_location : 'top'
	};
	options = $.extend(true, default_options, options);
	tinyMCE.init(options);
};

//init_mce();
