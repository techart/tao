$(function() {

window.TAO = window.TAO || {};
TAO.fields = TAO.fields || {};
TAO.fields.autocomplete = function(input) {

var id = input.getAttribute('id');
var input = Ext.get(id);
var style = input.getAttribute('style');
var width = input.getWidth('width');
var height = input.getHeight('height');
var combo_id = id + '_combobox';
var name = input.getAttribute('name');
var displayValue = input.getAttribute('data-text');
var value = input.getValue();
var url = input.getAttribute('data-href');

//$('<div id ="' + combo_id + '"></div>').insertAfter('#' + id);
Ext.DomHelper.insertAfter(id, {tag: 'div', id: combo_id});
input.destroy();

Ext.define('ComboModel', {
    extend: 'Ext.data.Model',
    fields: ['title', 'id']
});

var store = new Ext.data.Store({
			model: 'ComboModel',
			proxy: {
				type: 'ajax',
				api: {
					read: url
				},
				reader: {
					type: 'json',
					root: 'data'
				}
			},
			pageSize: 0,
			//autoLoad: true,
			listeners: {
				load: {
					single: true,
					fn : function() {
						combo.forceSelection = true;
					}
				}
			}
		});


var combo = new Ext.form.field.ComboBox({
    name: name,
//    hideTrigger: true,
    autoSelect: false,
    style: style + ';',
    flex: 1,
    //width: 1000,
    
    width: parseInt(width),
    height: parseInt(height),
    
    //typeAhead: true,
    queryDelay: 200,
//    triggerAction: 'all',
    //forceSelection : true,
    
    minChars: 1,
    store: store,
    value :  new ComboModel({id: value, title: displayValue}),
    displayField: 'title',
    valueField: 'id',
		renderTo: combo_id
});

combo.getPicker().loadMask = false;

}


});
