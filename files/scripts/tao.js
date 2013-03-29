var TAO = TAO || {
	settings: {},
	data: {}
}

Ext = {
  buildSettings:{
    "scopeResetCSS": true
  }
};

TAO.helpers = TAO.helpers || {}

TAO.helpers.addStylesheet = function(url) {
  if ($('link[href*="' + url + '"]').length > 0) return;
  var link = $("<link>");
  link.attr({
        type: 'text/css',
        rel: 'stylesheet',
        href: url
  });
  return $("head").append( link ); 
}

TAO.helpers.addScript = function(url, callback) {
  if ($('script[src*="' + url + '"]').length > 0) return;
 	var script = document.createElement( 'script' );
 	script.type = "text/javascript";
	script.src = url
	script.onload = callback
	var head= document.getElementsByTagName('head')[0];
	head.appendChild(script);
  return script; 
}

TAO.helpers.process_data = function(data) {
	if (data.js) {
			var count = 0;
			var load = 0;
			
		if (data.css) {
			$.each(data.css, function(k, v) {
				TAO.helpers.addStylesheet(v);
			})
		}
			
			$.each(data.js, function(k, v) {
				var sc = TAO.helpers.addScript(v, function() {
					load++;
					if (load == count && data.eval) {
							jQuery.globalEval(data.eval);
						}
				});
				if (sc) count++;
			})
		}
		
		if(count == 0 && data.eval) jQuery.globalEval(data.eval);
}


TAO.settings

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
					console.debug(element);
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
}