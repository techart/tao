//TODO: zoom==""

$(document).ready(function() {

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}
TAO.fields.map_coords = function (map_container) {

	function Map_object() {
		var map_object = new Object();
		
		map_object.lat_field = $(map_container).find('input[mapfield_type="latitude"]');
		map_object.lng_field = $(map_container).find('input[mapfield_type="longitude"]');
		map_object.zoom_field = $(map_container).find('input[mapfield_type="zoom"]');
		map_object.use_zoom = false;
		if (map_object.zoom_field.length>0) {
			map_object.use_zoom = true;
		}
		map_object.map_id = $(map_container).find('.tao-map').attr('id');
		map_object.to_marker_button = $(map_container).find('.map-button-to-marker');
		map_object.radios = $(map_container).find('.map-coords-format input[type="radio"]');
		
		map_object.format = $(map_container).find('.map-coords-format input[type="radio"]:checked').attr('value');
		
		map_object.radios.change(function() {
			map_object.reformat_fields();
		});
		
		map_object.reformat_fields = function() {
			switch (this.format) {
				case 'degrees':
					var values = this.read_fields();
					map_object.format = 'decimal';
					this.write_fields(values);
					break;
				case 'decimal':
					var values = this.read_fields();
					map_object.format = 'degrees';
					this.write_fields(values);
					break;
			}
		}
		
		map_object.degrees_to_decimal = function(degrees) {
			// в случае пустого значения не делаем ничего
			if (degrees=="") {
				decimal = degrees;
			}
			else {
				var pattern = /^(?:(-?\d+)°)*\s*(?:(\d+)')*\s*(?:(\d+)'')*$/;
				m = pattern.exec(degrees);
				if (m==null) {
					return 0;
				}
				else {
					deg = parseInt(m[1]);
					if (isNaN(deg)) deg = 0;
					min = parseInt(m[2]);
					if (isNaN(min)) min = 0;
					sec = parseInt(m[3]);
					if (isNaN(sec)) sec = 0;
					
					sign = 1;
					if (deg<0) {
						deg *= -1;
						sign = -1;
					}

					decimal = sign*(deg + min/60 + sec/3600);
				}
			}
			return decimal;
		}
		
		map_object.decimal_to_degrees = function(decimal) {
			var val = "";
			var sign = "";
			if (decimal<0) {
				decimal *= -1;
				sign = "-";
			}

			deg = decimal|0;
			min_val = (decimal-deg)*60;
			min = min_val|0;
			sec = Math.round((min_val-min)*60);
			
			if (sec == 60) {
				min += 1;
				if (min == 60) {
					deg += 1;
					min = 0;
				}
				sec = 0;
			}

			val += sign + deg + "° "
			if (min > 0) {
				val += min + "' ";
			}
			if (sec > 0) {
				val += sec + "''";
			}
			return val;
		}
		
		map_object.write_fields = function(values) {
			var lat = values['lat'];
			if (lat!=null&&lat!="") {
				lat = lat.toFixed(6);
				if (this.format == 'degrees') {
					lat = this.decimal_to_degrees(lat);
				}
				this.lat_field.val(lat);
			}
			
			var lng = values['lng'];
			if (lng!=null&&lng!="") {
				lng = lng.toFixed(6);
				if (this.format == 'degrees') {
					lng = this.decimal_to_degrees(lng);
				}
				this.lng_field.val(lng);
			}

			if (this.use_zoom&&values['zoom']) {
				var zoom = values['zoom'];
				if (zoom!=null) {
					zoom = parseInt(zoom);
					if (isNaN(zoom)||zoom<0) {
						zoom = 0;
					}
					this.zoom_field.val(zoom);
				}
			}
		}
		
		map_object.read_fields = function() {
			var lat = this.lat_field.val().replace(",", ".");
			var lng = this.lng_field.val().replace(",", ".");

			if (this.format == 'degrees') {
				lat = this.degrees_to_decimal(lat);
				lng = this.degrees_to_decimal(lng);
			}

			if (isNaN(lat)) {
				lat = 0;
			}
			if (isNaN(lng)) {
				lng = 0;
			}

			if (this.use_zoom) {
				var zoom = this.zoom_field.val();
				if (zoom!="") {
					zoom = parseInt(zoom);
					if (isNaN(zoom)||zoom<0) {
						zoom = 0;
					}
				}
			}
			else {
				zoom = "";
			}

			if (!(lat==""&&lng=="")) {
				lat = parseFloat(lat);
				lng = parseFloat(lng);
			}
			var values = {
				lat: lat,
				lng: lng,
				zoom: zoom
			};
			return values;
		}
		
		return map_object;
	}
	
	function GoogleMap_object() {
		var google_map_object = Map_object();
		
		google_map_object.set_marker = function(point) {
			if (typeof(this.marker)!='undefined') {
				this.marker.setPosition(point)
			}
			else {
				this.marker = new google.maps.Marker(
					{
						position: point,
						draggable: true,
						map: this.map
					}
				);
				google.maps.event.addListener(this.marker, 'drag', function(point) {
					var values = { 
						lat: point['latLng'].lat(), 
						lng: point['latLng'].lng(),
						zoom: null
					};
					google_map_object.write_fields(values);
				});
			};
		}
		
		google_map_object.to_marker_button.click(function() {
			google_map_object.map.setCenter(google_map_object.marker.getPosition());
		});
		
		google_map_object.fields_to_map = function() {
			var values = this.read_fields();
			var point = new google.maps.LatLng(values['lat'], values['lng']);
			this.write_fields(values);
			if (this.use_zoom&&values['zoom']) {
				this.map.setZoom(values['zoom']);
			}
			this.map.setCenter(point);
			this.set_marker(point);
		}
		
		google_map_object.init = function() {
			this.map = TAO.maps[this.map_id].map;
			this.fields_to_map();
		
			google.maps.event.addListener(this.map, 'click', function(point) {
				var values = { 
					lat: point['latLng'].lat(), 
					lng: point['latLng'].lng(),
					zoom: null
				};
				google_map_object.write_fields(values);
				var point = new google.maps.LatLng(values['lat'], values['lng']);
				google_map_object.set_marker(point);
			});
			
			this.lat_field.bind( 'blur', function(e) {
					google_map_object.fields_to_map()
				}
			);
			this.lng_field.bind( 'blur', 	function(e) {
					google_map_object.fields_to_map();
				}
			);
			if (this.use_zoom) {
				google.maps.event.addListener(this.map, 'zoom_changed', function() {
					google_map_object.zoom_field.val(google_map_object.map.getZoom());
				});
				this.zoom_field.bind( 'blur', 	function(e) {
					google_map_object.fields_to_map();
				});
			}
			
			// google maps "display: none" problem
			// solved by showTab event (tabs.js)
			tabs_nav = $('.admin-table-form-tabs .tabs-nav');
			if (tabs_nav.length) {
				var map_handler = function() {
					
					// map resizing and centering
					google.maps.event.trigger(google_map_object.map, 'resize');
					google_map_object.fields_to_map();
					
					// event already not necessary after first firing
					width = google_map_object.map.getDiv().offsetWidth;
					height = google_map_object.map.getDiv().offsetHeight;
					if (width>0&&height>0) {
						tabs_nav.unbind('showTab', map_handler);
					}
				};
				tabs_nav.bind('showTab', map_handler);
			}
		}
		return google_map_object;
	}

	function YandexMap_object() {
		var yandex_map_object = Map_object();
		
		yandex_map_object.set_marker = function(point) {
			if (typeof(this.marker)!='undefined') {
				this.marker.geometry.setCoordinates(point)
			}
			else {
				this.marker = new ymaps.Placemark(point, { }, { draggable: true });
				this.marker.events.add('drag', function () {
					var point = yandex_map_object.marker.geometry.getCoordinates();
					var values = {
						lat: point[0],
						lng: point[1],
						zoom: null
					};
					yandex_map_object.write_fields(values);
				});
				this.map.geoObjects.add(this.marker);
			};
		}
		
		yandex_map_object.to_marker_button.click(function() {
			yandex_map_object.map.setCenter(yandex_map_object.marker.geometry.getCoordinates());
		});
		
		yandex_map_object.fields_to_map = function() {
			var values = this.read_fields();
			var point = [values['lat'], values['lng']];
			this.write_fields(values);
			if (this.use_zoom&&values['zoom']) {
				this.map.setZoom(values['zoom']);
			}
			this.map.setCenter(point);
			this.set_marker(point);
		}
		
		yandex_map_object.init = function() {
			this.map = TAO.maps[this.map_id].map;
			this.fields_to_map();
			
			this.map.events.add('click',
				function(e) {
					var values = {
						lat: e.get('coordPosition')[0],
						lng: e.get('coordPosition')[1],
						zoom: null
					};
					yandex_map_object.write_fields(values);
					var point = [values['lat'], values['lng']];
					yandex_map_object.set_marker(point);
				}
			);
			
			this.lat_field.bind( 'blur', function(e) {
					yandex_map_object.fields_to_map();
				}
			);
			this.lng_field.bind( 'blur', 	function(e) {
					yandex_map_object.fields_to_map();
				}
			);
			
			if (this.use_zoom) {
				this.map.events.add('boundschange', function (e) {
					if (e.get('newZoom') != e.get('oldZoom')) {
						yandex_map_object.zoom_field.val(e.get('newZoom'));
					}
				});
				this.zoom_field.bind( 'blur', 	function(e) {
						yandex_map_object.fields_to_map();
					}
				);
			}
			// yandex карты - перерисовка на многовкладочной форме
			tabs_nav = $('.admin-table-form-tabs .tabs-nav');
			if (tabs_nav.length) {
				var map_handler = function() {
					yandex_map_object.map.container.fitToViewport();
				};
				tabs_nav.bind('showTab', map_handler);
			}
		}
		return yandex_map_object;
	}
	
	// Определение типа карты
	var type = $(map_container).find('.tao-map').attr('type');
	switch (type) {
		case 'google_maps':
			var map = GoogleMap_object();
			map.init();
			break;
		case 'yandex_maps':
			var map = YandexMap_object();
			ymaps.ready (function() {
				map.init();
			});
			break;
		default:
			return;
	}
}

});
