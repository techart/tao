Ext.onReady(function() {

	Ext.define('TAO.view.tree.AdminVarsTreepanel', {
		extend : 'TAO.view.tree.AdminTreePanel',
		alias: 'adminvars',
		hideHeaders: false,
    includeConfigFields: true,
    dragOnIcon: true,
    onlySort: true,
    initComponent: function() {
    	Ext.apply(this.viewConfig.listeners, {
    		validdroppoint: function(drop, node, position, dragZone, e, data, targetNode) {
	          	if (targetNode && targetNode.raw) return targetNode.raw.vartype == 'dir';
	          	return true;
          	}
    	});
    	Ext.apply(this.listeners, {
    		itemclick : function(view, record, item, index, e, eOpts) {
    			if(!Ext.fly(e.target).hasCls('x-tree-icon') && !Ext.fly(e.target).hasCls('x-action-col-icon') && record.raw.vartype !== 'dir') {
    				window.location.href = record.raw.edit_parms;
    			}
    		}
    	});
    	if (TAO.settings.adminvars) Ext.apply(this, TAO.settings.adminvars);
    	this.callParent(arguments);

        this.getView().getRowClass = function(record, rowIndex, rowParams, store) {
            return 'var-type-' + record.raw.vartype;
        };
    },

    extraColumns: [
        {
          xtype:'actioncolumn',
          menuDisabled: true,
          width:25,
          //maxWidth: 22,
          flex:0,
          resizable: false,
          weight: 0,
          items: [{
              // icon: '/files/_assets/images/icons/plus-button.png',
              getClass: function (v, metadata, r, rowIndex, colIndex, store) {
                if (r.raw.vartype == 'dir')
                    return 'var-add-icon';
                return '';
              },
              width: 20,
              tooltip: 'Add',
              handler: function (grid, rowIndex, colIndex) {
                  var rec = grid.getStore().getAt(rowIndex);
                  console.debug(rec);
                  if (rec.raw.vartype == 'dir') {
                    window.location = rec.raw.add;  
                  }
              }
          }]
      }
    ]

	});

    var tree = Ext.create('adminvars', {
        
    });
});
