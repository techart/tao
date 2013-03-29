$(function() {

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}
TAO.fields.documents_grid = TAO.fields.documents_grid || {}

TAO.fields.documents_grid.process = function(field) {

var run = function(field) {
	if (!field) return;
	var base_element = field.find('.field-documents-grid-root');
	if (base_element.length < 1) return;
	var field_name = base_element.attr('data-field-name');
	
var fields = []

Ext.iterate(TAO.settings.fields[field_name].fields, function (key, item) {
	var f = {name: key};
	Ext.apply(f, item.store);
	fields.push(f);
})


var model = 'TAO.data.fields.'+field_name

Ext.define(model, {
    extend: 'Ext.data.Model',
    fields: fields
});

//TODO: to TAO....
	var store = Ext.create('Ext.data.Store', {
		model: model,
		autoLoad: true,
		autoSync: true,
		proxy: {
			type: 'ajax',
			api: TAO.settings.fields[field_name].api,
			reader: {
					root: 'files',
					type: 'json',
					idProperty: 'name'
			},
			writer: {
					type: 'json',
					writeAllFields: true,
			},
    }
	})
	
	var rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
        clicksToMoveEditor: 1,
        autoCancel: false,
        pluginId: 'gridRowEditor',
        autoCancel: true,
        saveBtnText: 'asdsad',
        listeners: {
        	beforeedit: {
        		single: true,
        		fn: function () {
		      		var columnCount = grid.columns.length;
		      		var editor = grid.getPlugin('gridRowEditor').getEditor();
		      		editor.addCls('documents-frid-editor');
		      		//editor.floatingButtons.items.items[0].margins.left = 5;
		      		var summaryWidth = 0;
		      		for (i=1;i<=3;i++) {
		      			var column = grid.columns[columnCount - i]
				    		field = editor.getEditor(column);
				    		summaryWidth += field.width;
				   		 	editor.mun(field, 'validitychange', editor.onValidityChange, editor);
				    		editor.columns.removeAtKey(field.id);
				    	}
				    	var lastColumn = editor.getEditor(grid.columns[columnCount-4])
				    	lastColumn.width += summaryWidth;
        		},
        	}
        }
    });
    
Ext.override(Ext.grid.RowEditor,{
    saveBtnText: 'Сохранить',
    cancelBtnText: 'Отменить',
});
	
	Ext.define( 'TAO.view.fields.documents_grid.Grid',{
		extend: 'Ext.grid.Panel',
		renderTo: base_element[0],
		store: store,
		stateful: false,
		enableColumnResize: false,
		enableColumnMove: false,
		sortableColumns: false,
		//hideHeaders: true,
		defultColumns: [],
		extraColumns: [
			{
				xtype:'actioncolumn',
		    menuDisabled: true,
		    width:23,
		    //maxWidth: 22,
		    flex:0,
		    resizable: false,
		    weight: 1000,
		    items: [
		    	{
		        icon: '/files/_assets/images/edit.gif',
		        tooltip: 'Edit',
		        width: 18,
		        iconCls: 'mousepointer',
		        handler: function (grid, rowIndex, colIndex) {
		            rowEditing.startEdit(rowIndex, 1);
		        }
		    	}
		    ]
		  },
		  {
				xtype:'actioncolumn',
		    menuDisabled: true,
		    width:23,
		    //maxWidth: 22,
		    flex:0,
		    resizable: false,
		    weight: 1000,
		    items: [
		    	{
		        icon: '/files/_assets/images/del.gif',
		        tooltip: 'Delete',
		        width: 18,
		        iconCls: 'mousepointer',
		        handler: function (grid, rowIndex, colIndex) {
		            var rec = grid.getStore().getAt(rowIndex);
		            if (confirm('Вы уверенны что хотите удалить документ?')) {
		            	grid.getStore().remove(rec);
		            }
		        }
		    	}
		    ]
		  },
			{
				xtype:'actioncolumn',
				menuDisabled: true,
				width:23,
				//maxWidth: 22,
				flex:0,
				resizable: false,
				weight: 1000,
				items: [
					{
					  icon: '/files/_assets/images/download.png',
					  width: 18,
					  tooltip: 'Delete',
					  iconCls: 'mousepointer',
					  handler: function (grid, rowIndex, colIndex) {
					      var rec = grid.getStore().getAt(rowIndex);
					      window.open(rec.get('path'));
					  }
					}
				]
			}
		],
		defualtColumnDefinetion: {
			editor: 'textfield',
			flex: 1,
			menuDisabled: true,
		},
		initComponent : function(){
			var me = this;
			if (!me.columns) {
				me.columns = me.defultColumns;
		    Ext.iterate(TAO.settings.fields[field_name].fields, function(key, item) {
		    	if (!item.column) return;
		    	var col = {dataIndex: key};
		    	Ext.apply(col, me.defualtColumnDefinetion);
		    	Ext.apply(col, item.column);
		    	me.columns.push(col);
		    })
		    if (me.extraColumns) {
		      for (var i=0; i < me.extraColumns.length; i++)
		        me.columns.push(me.extraColumns[i]);
		    }
			}
			me.callParent(arguments);
		},
		viewConfig: {
			emptyText: 'Документы отсутствуют',
			forceFit: true,
			plugins: [{
            ptype: 'gridviewdragdrop',
            dragText: 'Drag and drop to reorganize',
      }],
      listeners: {
            	drop: function (node, data, overModel, dropPosition, eOpts) {
            		//Save order manualy
            		grid.getSelectionModel().clearSelections();
            		grid.getSelectionModel().setSelectionMode('MULTI')
            		grid.getSelectionModel().selectAll();
            		var sm = grid.getSelectionModel().getSelection();
            		var parms = []
            		for (i=0; i<=sm.length-1; i++) {
									parms.push(sm[i].data);
								}
            		Ext.Ajax.request({
									url: TAO.settings.fields[field_name].api.update,
									method: 'POST',
									jsonData: parms,
									success: function(obj) {
										var resp = obj.responseText;
									}
								});
            		grid.getSelectionModel().deselect(sm);
            	}
            }
		},
		plugins: [
			//Ext.create('Ext.grid.plugin.CellEditing', {clicksToEdit:2})
			rowEditing
		]
	});
	
	var grid = Ext.create('TAO.view.fields.documents_grid.Grid');
	
	$(".admin-table-form-tabs .tabs-nav").bind('showTab', function (e, clicked) {
		var tab = $($(clicked).attr('href'));
		var gridEl = $(grid.getEl().dom);
		if (tab.find('#' + gridEl.attr('id')).length)
			grid.getView().refresh();
	});
	
	return grid;
}

	run(field);
	
	//from attaches field
	var id = field.find('.attaches-list').attr('id');
	$('#' + id, field).bind('reload', function(e, list, type) {
		var gr = run($(list).parents('.field'));
		if (type == 'upload') {
			gr.getStore().on('load', function() {
				gr.getPlugin('gridRowEditor').startEdit(gr.getStore().getCount() - 1, 1);
			}, {single: true})
		}
	});
	
}

})
