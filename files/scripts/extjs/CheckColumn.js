Ext.define('Ext.ux.CheckColumn', {
    extend: 'Ext.grid.column.Column',
    alias: 'widget.checkcolumn',

    disableColumn: false,
    disableFunction: null,
    disabledColumnDataIndex: null,
    columnHeaderCheckbox: false,
    autoSync: true,
    originText: '',

    constructor: function(config) {

        var me = this;
        me.addEvents(
            /**
             * @event checkchange
             * Fires when the checked state of a row changes
             * @param {Ext.ux.CheckColumn} this
             * @param {Number} rowIndex The row index
             * @param {Boolean} checked True if the box is checked
             */
            'beforecheckchange',
            /**
             * @event checkchange
             * Fires when the checked state of a row changes
             * @param {Ext.ux.CheckColumn} this
             * @param {Number} rowIndex The row index
             * @param {Boolean} checked True if the box is checked
             */
            'checkchange'
        );

        me.callParent(arguments);
        me.originText = me.text;
    },

    onRender: function(column, container, pos, eOpts) {
        var me = this;
        if(this.columnHeaderCheckbox)
        {
            var store = this.up('tablepanel').store;
            me.store = store;
            store.on("datachanged", function(){
                me.updateColumnHeaderCheckbox(me);
            });
            store.on("update", function(){
                me.updateColumnHeaderCheckbox(me);
            });
            store.on("load", function(){
                me.updateColumnHeaderCheckbox(me);
            });
            me.setText(me.getHeaderCheckboxImage(store, me.dataIndex));
        }
        this.callParent(arguments);
        
    },

    updateColumnHeaderCheckbox: function(column){
        var image = column.getHeaderCheckboxImage(column.store, column.dataIndex);
        column.setText(image);
    },

    toggleSortState: function(){
        var me = this;
        if(me.columnHeaderCheckbox)
        {
            var store = me.up('tablepanel').store;
            var isAllChecked = me.getStoreIsAllChecked(store, me.dataIndex);
            me.storeEach(store, function(record){
                record.set(me.dataIndex, !isAllChecked);
                //record.commit();
            });
            me.updateColumnHeaderCheckbox(me);
            if (me.autoSync) {
                store.sync();
            }
        }
        else
            me.callParent(arguments);
    },

    storeEach : function (store, func) {
        if (store.each)
            store.each(func);
        if (store.getRootNode) {
            var forChilds = function(childs) {
                Ext.each(childs, function(record) {
                    func(record);
                    if (record.childNodes)
                        forChilds(record.childNodes);
                });
            };
            var childs = store.getRootNode().childNodes;
            if (childs) forChilds(childs);
        }
    },

    getStoreIsAllChecked: function(store, dataIndex){
        var allTrue = true;
        var check = function(record){
                if(!record.get(dataIndex))
                    allTrue = false;
            };
        
        this.storeEach(store, check);

        return allTrue;
    },

    getHeaderCheckboxImage: function(store, dataIndex){

        var allTrue = this.getStoreIsAllChecked(store, dataIndex);

        var cssPrefix = Ext.baseCSSPrefix,
            cls = [cssPrefix + 'grid-checkheader'];

        if (allTrue) {
            cls.push(cssPrefix + 'grid-checkheader-checked');
        }
        return this.originText+'<div class="' + cls.join(' ') + '"></div>';
    },

    /**
     * @private
     * Process and refire events routed from the GridView's processEvent method.
     */
    processEvent: function(type, view, cell, recordIndex, cellIndex, e) {
        if (type == 'mousedown' || (type == 'keydown' && (e.getKey() == e.ENTER || e.getKey() == e.SPACE))) {
            var store = this.up('panel').getStore();
            var record = store.getAt ? store.getAt(recordIndex) : view.getRecord(view.getNode(recordIndex)),
                dataIndex = this.dataIndex,
                checked = !record.get(dataIndex),
                column = view.panel.columns[cellIndex];
            if(!(column.disableColumn || record.get(column.disabledColumnDataIndex) || (column.disableFunction && column.disableFunction(checked, record))))
            {
                if(this.fireEvent('beforecheckchange', this, recordIndex, checked, record))
                {
                    record.set(dataIndex, checked);
                    if (this.autoSync)
                        store.sync();
                    this.fireEvent('checkchange', this, recordIndex, checked, record);
                }
            }
            // cancel selection.
            return false;
        } else {
            return this.callParent(arguments);
        }
    },

    // Note: class names are not placed on the prototype bc renderer scope
    // is not in the header.
    renderer : function(value, metaData, record, rowIndex, colIndex, store, view){
        var disabled = "",
            column = view.panel.columns[colIndex];
        if(column.disableColumn || column.disabledColumnDataIndex || (column.disableFunction && column.disableFunction(value, record)))
            disabled = "-disabled";
        var cssPrefix = Ext.baseCSSPrefix,
            cls = [cssPrefix + 'grid-checkheader' + disabled];

        if (value) {
            cls.push(cssPrefix + 'grid-checkheader-checked' + disabled);
        }
        return '<div class="' + cls.join(' ') + '">&#160;</div>';
    }
});