// polyfill
if (!Object.create) {
    Object.create = (function(){
        function F(){}

        return function(o){
            if (arguments.length != 1) {
                throw new Error('Object.create implementation only accepts one parameter.');
            }
            F.prototype = o
            return new F()
        }
    })()
}
//

TAO.OOP = {};

TAO.OOP.inherits = function(child, parent) {
	child.prototype = Object.create(parent.prototype);
	child.prototype.constructor = child;
	child.prototype.super_ = parent.prototype;
	child.prototype.super_class_ = parent;
};

TAO.OOP.define = function(name, def, callback) {
	if (typeof def == 'function') {
		def = def();
	}
	var constructor = function() {
		var original = def.constructor_ || function() {};
		if (typeof this.cls_ == 'undefined') {
			this.cls_ = TAO.OOP.objectFromName(name).prototype;
			this.prototype = this.cls_;
		}
		original.apply(this, arguments);
	};
	var result = TAO.OOP.objectFromName(name, constructor);
	if (typeof def.extends_ != 'undefined') {
		var parent = TAO.OOP.objectFromName(def.extends_);
		TAO.OOP.inherits(result, parent);
	}
	$.extend(result.prototype, def);
	if (typeof callback == 'function') {
		callback(result.prototype);
	}
	return result;
};

TAO.OOP.objectFromName = function(name, value) {
	value = value || {};
	var scope = window;
	var parts = name.split('.');
	var obj = scope;
	var len = parts.length;
	for(var i = 0; i < len; i++){
		var part = parts[i];
		if (typeof obj[part] == 'undefined') {
			if (i == len - 1) {
				obj[part] = value;	
			} else {
				obj[part] = {};
			}
		}
		obj = obj[part];
	}
	return obj;
}

TAO.OOP.mixin = function() {

};