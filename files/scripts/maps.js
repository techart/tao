/* Common functions */
function TaoMaps_layout_process(html_string, parms) {
		var expr;
		var str = html_string;
		for (var parm in parms) {
			expr = new RegExp('%{' + parm + '}', 'g');
			str = str.replace(expr, parms[parm]);
		}
		return str;
}

function TaoMaps_prepare_options(map, map_id, name, options) {
	switch (map) {
		case 'google_maps':
			if (typeof(google_maps_options) != "undefined") google_maps_options[name] = options; 
			else { 
				window.google_maps_options = new Object(); 
				google_maps_options[name] = options; 
			}
			if (typeof(google_maps_list) != "undefined") google_maps_list[map_id] = name; 
			else { 
				window.google_maps_list = new Object(); 
				google_maps_list[map_id] = name; 
			}
			break;
		case 'yandex_maps':
			if (typeof(yandex_maps_options) != "undefined") yandex_maps_options[name] = options; 
			else { 
				window.yandex_maps_options = new Object(); 
				yandex_maps_options[name] = options; 
			}
			if (typeof(yandex_maps_list) != "undefined") yandex_maps_list[map_id] = name; 
			else { 
				window.yandex_maps_list = new Object(); 
				yandex_maps_list[map_id] = name; 
			}
			break;
	}
}
/* !Common functions */


/* Map window classes */
function Google_map_window(options, map, mode, marker) {

	var window_options = { 
		map: map,
		content: options.parms.content,
		padding: 12,
		borderRadius: 5,
		arrowSize: 20,
		borderColor: '#CCCCCC',
		disableAutoPan: true,
		arrowStyle: 2
	};
	
	if (typeof(options.layout)!='undefined') {
		var layout_insertions = { }
		if (typeof(options.parms.layout_insertions)!='undefined') {
			layout_insertions = options.parms.layout_insertions;
			window_options.content = TaoMaps_layout_process(options.layout, layout_insertions);
		}
	}
	if (mode=='position') {
		window_options.position = new google.maps.LatLng(options.parms.position.x, options.parms.position.y);
		infoBubble = new InfoBubble(window_options);
	}
	if (mode=='marker') {
		window_options.position = marker.position;
		window_options.disableAutoPan = false;
		var infoBubble = new InfoBubble(window_options);
		google.maps.event.addListener(marker, "click", function() {
					infoBubble.open(map, marker);
		});
	}
	return infoBubble;
}

function Yandex_map_window(options, map) {
	
	var window_geometry = [ options.parms.position.x, options.parms.position.y ];
	
	var window_options = {
		contentBody: options.parms.content
	}
	
	if (typeof(options.layout)!='undefined') {
		var layout_insertions = { }
		if (typeof(options.parms.layout_insertions)!='undefined') {
			layout_insertions = options.parms.layout_insertions;
			window_options.contentBody = TaoMaps_layout_process(options.layout, layout_insertions);
		}
	}

	map.balloon.open(
		window_geometry,
		window_options,
		{ autoPan: false }
	);
}
/* !Map window classes */

/* Map marker classes */
function Google_map_marker(options, map) {

	var marker_options = { };
	
	this.calculate_sign_shift = function(shift_coords) {
		return { x: -shift_coords.x, y: shift_coords.y }
	}
	
	marker_options = {
		position: new google.maps.LatLng(options.position.x, options.position.y),
		map: map
	}
	if (typeof(options.title)!='undefined') {
		marker_options.title = options.title;
	}
	if (typeof(options.draggable)!='undefined') {
		marker_options.draggable = true;
		marker_options.raiseOnDrag = false;
	}
	if (typeof(options.color)!='undefined') {
		options.color = (options.color=='lightblue') ? 'ltblue' : options.color;
		this.sign_image = 'http://google.com/mapfiles/ms/micons/' + options.color + '-dot.png';
	}
	if (typeof(options.icon)!='undefined') {
		if (typeof(options.icon.size)!='undefined') {
			this.sign_size = options.icon.size;
			this.sign_shift = {x: -this.sign_size.x/2, y: this.sign_size.y};
		}
		if (typeof(options.icon.shift)!='undefined') {
			this.sign_shift = options.icon.shift;
		}
		if (typeof(options.icon.href)!='undefined') {
			this.sign_image = options.icon.href;
		}
	}
	this.sign_shift = this.calculate_sign_shift(this.sign_shift);
	marker_options.icon = new google.maps.MarkerImage(this.sign_image, new google.maps.Size(this.sign_size.x, this.sign_size.y), new google.maps.Point(0,0), 
							new google.maps.Point(this.sign_shift.x, this.sign_shift.y));
		
	return new google.maps.Marker(marker_options);
}

Google_map_marker.prototype = {
	sign_image: 'http://google.com/mapfiles/ms/micons/red-dot.png',
	sign_size: { x: 32, y: 32 },
	sign_shift: { x: -16, y: 32 }
}

function Yandex_map_marker(options, layouts) {

	var marker_geometry = { };
	var	marker_properties = { };
	var	marker_options = { };
	var use_iconImageHref = true;
	
	this.calculate_sign_shift = function(shift_coords) {
		return { x: shift_coords.x, y: -shift_coords.y }
	}

	marker_geometry = [options.position.x, options.position.y];

	if (typeof(options.title)!='undefined') {
		marker_properties.hintContent = options.title;
	}
	if (typeof(options.window)!='undefined') {
		
		if ((typeof(options.window.layout)!='undefined')&&(typeof(layouts)!='undefined')) {
			var layout_insertions = { }
			if (typeof(options.window.layout_insertions)!='undefined') {
				layout_insertions = options.window.layout_insertions;
				options.window.content = TaoMaps_layout_process(layouts[options.window.layout], layout_insertions);
			}
		}
		marker_properties.balloonContentBody = options.window.content;
	}

	if (typeof(options.draggable)!='undefined') {
		marker_options.draggable = true;
	}
	if (typeof(options.color)!='undefined') {
		options.color = (options.color=='purple') ? 'violet' : options.color;
		marker_options.preset = 'twirl#' + options.color + 'Icon';
		use_iconImageHref = false;
	}
	if (typeof(options.icon)!='undefined') {
		if (typeof(options.icon.size)!='undefined') {
			this.sign_size = options.icon.size;
			this.sign_shift = {x: -this.sign_size.x/2, y: this.sign_size.y};
		}
		if (typeof(options.icon.shift)!='undefined') {
			this.sign_shift = options.icon.shift;
		}
		if (typeof(options.icon.href)!='undefined') {
			this.sign_image = options.icon.href;
			use_iconImageHref = true;
		}
	}
	if (use_iconImageHref && this.sign_image) {
		marker_options.iconImageHref = this.sign_image;
	}
	if (this.sign_size) {
		marker_options.iconImageSize = [this.sign_size.x, this.sign_size.y];
	}
	if (this.sign_shift) {
		this.sign_shift = this.calculate_sign_shift(this.sign_shift);
		marker_options.iconImageOffset = [this.sign_shift.x, this.sign_shift.y];
	}
	
	return new ymaps.Placemark(marker_geometry, marker_properties, marker_options);
}

Yandex_map_marker.prototype = {
	sign_image: null,
	sign_size: null,
	sign_shift: null
}
/* !Map marker classes */

/* Main map classes */
function Tao_map() {
	var tao_map = new Object();
	
	tao_map.properties = { 
			center : { x: 55.76, y: 37.64 }, 
			zoom : 7, 
			type : 'roads', 
			style : 'width: 600px; height: 300px;',
			states: { drag: 'true', scroll_zoom: 'false', dbl_click_zoom: 'true' }
	};	
	tao_map.controls = { scale: 'large', mapType: '' };
	tao_map.markers = { };
	tao_map.layouts = { };
	tao_map.windows = { };
	
	tao_map.use_options = function(options) {
		for (var property in options.properties) {
			tao_map.properties[property] = options.properties[property];
		}
		if (typeof(tao_map.map_types[tao_map.properties.type])=='undefined')
			tao_map.properties.type = 'roads';
		tao_map.properties.type = tao_map.map_types[tao_map.properties.type];
		
		if (typeof(options.controls)!='undefined')
			tao_map.controls = options.controls;
		
		for (var control in tao_map.controls) {
			if (typeof(tao_map.controls_types[control])=='undefined')
				tao_map.controls[control] = null;
		}
		tao_map.layouts = options.layouts;
		
		tao_map.markers = options.markers;
		
		if (typeof(options.windows)!='undefined') {
			for (var window_item in options.windows) {

				if ((typeof(options.windows[window_item].marker)!='undefined')&&(typeof(tao_map.markers)!='undefined'))
					
					if (typeof(tao_map.markers[options.windows[window_item].marker])!='undefined') {
						tao_map.markers[options.windows[window_item].marker].window = options.windows[window_item];   
						tao_map.markers[options.windows[window_item].marker].window.marker = null;
					}
					else tao_map.windows[window_item] = options.windows[window_item];
					
				else tao_map.windows[window_item] = options.windows[window_item];
			}
		}
	}
	
	return tao_map;
}

function Google_map(options, map_id) {
	/* Object initialization & defaults */
	var google_map = Tao_map();
	
	google_map.map_types = { 
		satellite : 'SATELLITE', 
		hybrid : 'HYBRID', 
		roads : 'ROADMAP', 
		terrain : 'TERRAIN'
	};
	
	google_map.controls_types = { 
		scale : 'zoomControl', 
		scaleLine : 'scaleControl', 
		mapType : 'mapTypeControl', 
		miniMap : 'overviewMapControl' 
	};
	
	google_map.controls_position = { 
		scale : 'TOP_LEFT', 
		scaleLine : 'RIGHT_TOP', 
		mapType : 'TOP_RIGHT', 
		miniMap : 'BOTTOM_RIGHT' 
	};
	
	google_map.states_types = {
		drag: 'draggable',
		scroll_zoom: 'scrollwheel',
		dbl_click_zoom: 'disableDoubleClickZoom'
	}
	
	/* !Object initialization & defaults */
	
	/* Map initialization */
	google_map.use_options(options);
	jQuery('#google_maps_' + map_id).attr('style', google_map.properties.style);
	
	var init_options = {
		zoom: ~~google_map.properties.zoom,
		center: new google.maps.LatLng(google_map.properties.center.x, google_map.properties.center.y),
		mapTypeId: google.maps.MapTypeId[google_map.properties.type],
		disableDefaultUI: true,
		scrollwheel: false,
		draggable: false
	}
	
	// Setting map states(behaviors)
	if (typeof(google_map.properties.states)!='undefined') {
		var use_state;
		for (var state in google_map.properties.states) {
			if (google_map.properties.states[state]==='true') use_state = true; 
			else use_state = false;
			if (state=='dbl_click_zoom') use_state = !use_state;
			init_options[google_map.states_types[state]] = use_state; 
		}
	}
	// Setting controls
	for (var control in google_map.controls) {
		init_options[google_map.controls_types[control]] = true;
		init_options[google_map.controls_types[control] + 'Options'] = { 
			position: google.maps.ControlPosition[google_map.controls_position[control]]
		}
		if (control=='scale') {
			switch (google_map.controls[control]) {
				case 'large':
					init_options['zoomControlOptions'].style = google.maps.ZoomControlStyle.LARGE;
				break;
				
				case 'small':
					init_options['zoomControlOptions'].style = google.maps.ZoomControlStyle.SMALL;
				break;		
				
				default:
					init_options['zoomControlOptions'].style = google.maps.ZoomControlStyle.DEFAULT;
				break;
			}
		}
	}
	
	google_map.map = new google.maps.Map(
		document.getElementById("google_maps_" + map_id), 
		init_options
	);
	/* !Map initialization */
	
	/* Setting markers */
	var window_options = { };
	var google_markers = { };
	for (var marker in google_map.markers) {
		google_markers[marker] = new Google_map_marker(google_map.markers[marker], google_map.map);
	
		if (typeof(google_map.markers[marker].window)!='undefined') {
			window_options = { parms: google_map.markers[marker].window };
			if (typeof(google_map.layouts)!='undefined')
				if (typeof(google_map.layouts[google_map.markers[marker].window.layout])!='undefined')
					window_options.layout = google_map.layouts[google_map.markers[marker].window.layout];
			var google_window = new Google_map_window(window_options, google_map.map, 'marker', google_markers[marker]);
		}
	}
	/* !Setting markers */
	
	/* Setting windows */
	var google_windows = { };
	for (var window_item in google_map.windows) {
		window_options = { parms: google_map.windows[window_item] };
		if (typeof(google_map.layouts)!='undefined')
			if (typeof(google_map.layouts[google_map.windows[window_item].layout])!='undefined')
				window_options.layout = google_map.layouts[google_map.windows[window_item].layout];
		google_windows[window_item] = new Google_map_window(window_options, google_map.map, 'position');
		google_windows[window_item].open(google_map.map);
	}
	/* !Setting windows */
	
	return google_map;
	
}

function Yandex_map(options, map_id) {
	/* Object initialization & defaults */
	var yandex_map = Tao_map();
	
	yandex_map.map_types = { 
		satellite : 'satellite', 
		hybrid : 'hybrid', 
		roads : 'map', 
		publicMap : 'publicMap'
	};
	
	yandex_map.controls_types = { 
		scale : 'zoomControl', 
		scaleLine : 'scaleLine', 
		mapType : 'typeSelector', 
		miniMap : 'miniMap' 
	};
	
	yandex_map.controls_position = { 
		scale : { left: "5px", top: "5px" },
		scaleLine : { right: "6px", top: "55px" }, 
		mapType : { right: "5px", top: "5px" }, 
		miniMap : { left: "35px", top: "6px" } 
	};
	
	yandex_map.states_types = {
		drag: 'drag',
		scroll_zoom: 'scrollZoom',
		dbl_click_zoom: 'dblClickZoom'
	}
	
	/* Object initialization & defaults */
	
	/* Map initialization */
	yandex_map.use_options(options);
	jQuery('#yandex_maps_' + map_id).attr('style', yandex_map.properties.style);
	
	var init_options = {
		center: [yandex_map.properties.center.x, yandex_map.properties.center.y],
		zoom: ~~yandex_map.properties.zoom,
		type: "yandex#" + yandex_map.properties.type	
	}
	
	yandex_map.map = new ymaps.Map(
		"yandex_maps_" + map_id, 
		init_options
	);
	/* !Map initialization */

	/* Setting map states(behaviors) */
	if (typeof(yandex_map.properties.states)!='undefined') {
		for (var state in yandex_map.properties.states) {
			if (yandex_map.properties.states[state]==='true') yandex_map.map.behaviors.enable(yandex_map.states_types[state]);
			else
				if (typeof(yandex_map.map.behaviors.isEnabled(yandex_map.states_types[state]))!='undefined')
				yandex_map.map.behaviors.disable(yandex_map.states_types[state]);
		}
	}
	/* !Setting map states(behaviors) */

	/* Setting map controls */
	for (var control in yandex_map.controls) {
		
		switch (control) {
			
			case 'miniMap':
				yandex_map.map.controls.add(new ymaps.control.MiniMap({ expanded: false }), yandex_map.controls_position[control] );
			break;
			
			case 'scale':
				switch (yandex_map.controls[control]) {

					case 'large':
						yandex_map.map.controls.add('zoomControl', yandex_map.controls_position[control] );
					break;
				
					case 'small':
						yandex_map.map.controls.add('smallZoomControl', yandex_map.controls_position[control] );
					break;		
				
					default:
						yandex_map.map.controls.add('smallZoomControl', yandex_map.controls_position[control] );
					break;
				}
			break;
			
			default: 
				yandex_map.map.controls.add(yandex_map.controls_types[control], yandex_map.controls_position[control] );
			break;
		}
		
	}
	/* !Setting map controls */
	
	/* Setting markers */
	var yandex_markers = { };
	
	for (var marker in yandex_map.markers) {
		yandex_markers[marker] = new Yandex_map_marker(yandex_map.markers[marker], yandex_map.layouts);
		yandex_map.map.geoObjects.add(yandex_markers[marker]);
	}
	/* !Setting markers */

	/* Setting windows */
	var window_options = { };
	for (var window_item in yandex_map.windows) {
		window_options = { parms: yandex_map.windows[window_item] };  
		if (typeof(yandex_map.windows[window_item].layout)!='undefined')
			window_options.layout = yandex_map.layouts[yandex_map.windows[window_item].layout];
		Yandex_map_window(window_options, yandex_map.map);
	}
	/* !Setting windows */
	
	return yandex_map;
}
/* !Main map classes */


$(document).ready(function() {
	window.TAO = window.TAO || {};
	if (typeof(google_maps_list)!='undefined') {
		if (typeof(TAO.maps)=='undefined') {
			TAO.maps = { };
		}
		for (var map_id in google_maps_list) {
			var list_map_options = google_maps_options[google_maps_list[map_id]];
			TAO.maps['google_maps_' + map_id] = Google_map(list_map_options, map_id);
		}
	}
});

if (typeof(ymaps)!='undefined') {
	ymaps.ready (function() {
		window.TAO = window.TAO || {};
		if (typeof(yandex_maps_list)!='undefined') {
			if (typeof(TAO.maps)=='undefined') {
				TAO.maps = { };
			}
			for (var map_id in yandex_maps_list) {
				var list_map_options = yandex_maps_options[yandex_maps_list[map_id]];
				TAO.maps['yandex_maps_' + map_id] = Yandex_map(list_map_options, map_id);
			}	
		}
	});
}