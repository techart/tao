Ext.require([
    'Ext.tree.*',
    'Ext.data.*',
]);

Ext.onReady(function() {

var fields =  [
        {name: 'title', type: 'string'},
        {name: 'edit', type: 'string'},
        {name: 'delete', type: 'string'},
        {name : 'ord', type: 'int'},
        {name: 'parent_id', type: 'int'}
    ];
    
Ext.iterate(TAO.settings.tree.fields, function(key, item) {
	for (var i = 0, len = fields.length; i < len; i++) {
		if(fields[i].name == key) return;
	}
	fields.push({name: key, type: item.type ? item.type : 'string'});
});


Ext.define('TAO.data.AdminTreeModel', {
    extend: 'Ext.data.Model',
    fields: fields
});

Ext.define('TAO.store.AdminTree', {
    extend: 'Ext.data.TreeStore',
    model: 'TAO.data.AdminTreeModel',
    storeId:'AdminTreeStore',
    proxy: {
        type: 'ajax',
        //url: 'tree/list.json',
        api: {
            read: 'json_tree/' + window.location.search,
            //create: 'app.php/users/create',
            update: 'update_tree/' + window.location.search,
            //destroy: 'app.php/users/destroy'
        },
        reader: {
            type: 'json',
        },
        writer: {
            type: 'json',
            writeAllFields: false,
        },
    },
    root: {
        title: 'Root',
        id: 0,
        expanded: true
    },
    folderSort: true,
    sorters: [{
        property: 'ord',
        direction: 'ASC'
    }]
});

Ext.define('TAO.view.tree.AutoHeightPanel', {
    extend: 'Ext.tree.Panel',
    alias: 'widget.autoheighttree',
    constructor: function(cnfg){
        this.callParent(arguments);
        this.initConfig(cnfg);
        this.on('afterlayout', this.forceAutoHeight, this);
        this.on('afterrender', this.forceAutoHeight, this);
        this.on('resize', this.forceAutoHeight, this);
    },

    forceAutoHeight: function(){
    	//TODO: fix height
        var el = this.getEl().applyStyles({height : 'auto', overflow : 'visible'});
        Ext.get(el.query('.x-panel-body')).applyStyles({height : 'auto', overflow : 'visible'})
        this.items.each(function(item, idx, len) {
            if(Ext.isDefined(item.getEl())) item.getEl().applyStyles({height : 'auto', overflow : 'visible'});
        });
    }
});

Ext.override(Ext.data.AbstractStore,{
    indexOf: Ext.emptyFn
});

Ext_tree_ViewDropZone_isValidDropPoint = Ext.tree.ViewDropZone.prototype.isValidDropPoint;
Ext.override(Ext.tree.ViewDropZone, {
	isValidDropPoint : function(node, position, dragZone, e, data) {
		var res = Ext_tree_ViewDropZone_isValidDropPoint.apply(this, arguments);
		var view = this.view,
          targetNode = view.getRecord(node),
          draggedRecords = data.records;
		if (position != 'append') targetNode = targetNode.parentNode;
		if (targetNode && res && view.panel.onlySort) {
    	res = res && this.isValidSortPoint(targetNode, draggedRecords);
		}
		res = res && view.fireEvent('validdroppoint', this, node, position, dragZone, e, data, targetNode);
		return res;
	},
	isValidSortPoint: function (targetNode, draggedRecords) {
		var f = true;
    Ext.each(draggedRecords, function(item){
      f = f && (item.data.parentId == targetNode.data.id);
   });
   return f;
	}
});

Ext_tree_ViewDragZone_getDragData = Ext.tree.ViewDragZone.prototype.getDragData;
Ext.override(Ext.tree.ViewDragZone, {
	getDragData : function(e) {
		var tree = this.view.panel;
		var r = Ext_tree_ViewDragZone_getDragData.apply(this, arguments);
		if (!tree.dragOnIcon) return r;
		var dragOnIconElement = Ext.fly(e.target).hasCls('x-tree-icon');
		if (dragOnIconElement) return r;
		return false;
	}
});


Ext.define('TAO.view.tree.AdminTreePanel', {
	extend: 'TAO.view.tree.AutoHeightPanel',
	stateful: false,
	stateId: TAO.settings.component_name,
	dragOnIcon: false,
	enableColumnResize: false,
  enableColumnMove: false,
  sortableColumns: false,
  rootVisible: false,
  multiSelect: true,
  loadMask: true,
  saveDelay: 0,
  expandedStateId: TAO.settings.tree.expanded_state_name,
  renderTo: 'admin-tree',
  hideHeaders: true,
  includeConfigFields: false,
  plugins: [
  	Ext.create('Ext.grid.plugin.CellEditing', {clicksToEdit:2})
  ],
  
	initComponent : function(){
		var me = this;
    if (!this.columns) {
      // default columns:
      this.columns = this.defultColumns;
      // if we passed extraColumns config
      if (this.extraColumns) {
        for (var i=0; i < this.extraColumns.length; i++)
          this.columns.push(this.extraColumns[i]);
      }
      
      if (this.includeConfigFields) {
      	var j = 0;
				Ext.iterate(TAO.settings.tree.fields, function(key, item) {
					for (var i = 0, len = me.columns.length; i < len; i++) {
					//TODO: refactoring
						if(me.columns[i].dataIndex == key) {
							me.columns[i].weight = j;
							me.columns[i].text = item.caption;
							Ext.apply(me.columns[i], item);
							return;
						}
					}
					me.columns.push(Ext.apply({flex: 0, dataIndex: key, text: item.caption, menuDisabled: true, weight: j}, item));
					j++;
				});
      }
      
      this.columns.sort(function(a,b) {
      	var aw = typeof a.weight == 'undefined' ? 0 : a.weight,
      		bw = typeof b.weight == 'undefined' ? 0 : b.weight;
      	if (aw < bw) return -1;
      	if (aw > bw) return 1;
      	return 0;
      });
    }
    if (!this.store)
    	this.store = Ext.create('TAO.store.AdminTree');

    this.callParent(arguments);
  },
  
  viewConfig: {
    toggleOnDblClick: false,
      plugins: [{
         ptype: 'treeviewdragdrop',
      }],
      listeners : {
      	//TODO: to plugin class
          	drop: function (node, data, overModel, dropPosition, eOpts) {
          		var me = this.up('treepanel')
          		var item =  data.records[0];
          		if (item) {
          			Ext.each(item.parentNode.childNodes, function(chItem, index) {
          				chItem.dirty = true;
          				if (!chItem.modified) chItem.modified = {};
          				chItem.modified.index = chItem.data.index =item.parentNode.indexOf(chItem);
          				chItem.modified.parentId = 1;
          			});
          		}
          		me.getStore().sync();
          	},
          	/*validdroppoint: function(drop, node, position, dragZone, e, data, targetNode) {
	          	targetNode = drop.view.getRecord(node);
	          	console.debug(targetNode);
          		return true;
          	}*/
          	/*beforedrop : function(node, data, overModel, dropPosition, dropFunction) {
          		console.debug(node, data, overModel);
          		return false;
          	}*/
          }
  },
  listeners: {
  	itemexpand: function(node) {
  		this.saveExpanded(node, 'expanded');
  	},
  	itemcollapse: function(node) {
  		this.saveExpanded(node, 'collapsed');
  	}
  },
  saveExpanded: function(node, action) {
  	var state = Ext.state.Manager.get(this.expandedStateId);
    if (!Ext.isObject(state)) state = {};
    var id = parseInt(node.data.id);
    var index = 'item_' + id;
  	if (action == 'expanded') {
    	state[index] = id;
    }
    else {
    	delete state[index];
    }
    Ext.state.Manager.set(this.expandedStateId, state);
  },
  
	defultColumns: [{
      xtype: 'treecolumn',
      menuDisabled: true,
      text: 'Заголовок',
      flex: 1,
      //editor: 'textfield',
      sortable: false,
      dataIndex: 'title',
      weight: -1000
  	},
		/*{
      xtype:'actioncolumn',
      menuDisabled: true,
      width:22,
      //maxWidth: 22,
      flex: 0,
      weight: 1000,
      items: [{
          icon: TAO.settings.tree.edit_icon,  // Use a URL in the icon config
          tooltip: 'Edit',
          handler: function(grid, rowIndex, colIndex) {
              var rec = grid.getStore().getAt(rowIndex);
              window.location = rec.get('edit');
          }
      }]
  },*/
  {
    xtype:'templatecolumn',
    tpl: "<a class='tao-tree-icon-edit' href='{edit}'>&nbsp;</a>",
    menuDisabled: true,
    width:25,
    flex: 0,
    weight: 1000,
  },
  {
      xtype:'actioncolumn',
      menuDisabled: true,
      width:25,
      //maxWidth: 22,
      flex:0,
      resizable: false,
      weight: 1000,
      items: [{
          icon: TAO.settings.tree.delete_icon,
          width: 20,
          tooltip: 'Delete',
          handler: function (grid, rowIndex, colIndex) {
              var rec = grid.getStore().getAt(rowIndex);
              Ext.Msg.confirm('Подтверждение', 'Вы уверенны что хотите удалить запись?', function (btn){
              if (btn !== 'yes')
                return;
              window.location = rec.get('delete');//TODO: Ajax call & refresh store
            });
          }
      }]
  }
  ],
  clearTbar: [
		{
			xtype: 'button',
			text: 'Очистить Состояние',
			handler: function() {
				var me = this.up('treepanel');
				Ext.state.Manager.clear(me.stateId);
				Ext.state.Manager.clear(me.expandedStateId);
				Ext.Msg.show({'title': 'Message', msg: 'State is cleared', buttons: Ext.Msg.OK, 
					fn: function() {
						window.location.reload();
					}
				});
			}
		}
	]
});

});
