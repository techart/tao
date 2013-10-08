TAO.require(['tao/oop', 'SlickGrid/slick.table'], function() {
    TAO.OOP.define('Slick.Tree', function() {
      //private

      function titleFormat(row, cell, value, columnDef, dataContext) {
        var self = this;
        var icon = '&nbsp;';
        var iurl = '';
        if (dataContext.icon) {
          iurl = dataContext.icon;
        }
        value = value.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
        value = "<span class='tree-value'>"+value+"</span>"
        var spacer = "<span style='display:inline-block;height:1px;width:" + (15 * dataContext["depth"]) + "px'></span>";
        var idx = this.dataView.getIdxById(dataContext.id);
        data = this.dataView.getItems();
        if (data[idx + 1] && data[idx + 1].depth > data[idx].depth) {
          if (dataContext._collapsed) {
            icon = itemIcon(iurl ? iurl : self.icons.collapsedIcon);
            return spacer + " <span class='toggle expand'></span>"+icon + value;
          } else {
            icon = itemIcon(iurl ? iurl : self.icons.expandedIcon);
            return spacer + " <span class='toggle collapse'></span>"+icon + value;
          }
        } else {
          icon = self.itemIcon(iurl ? iurl : self.icons.documentIcon);
          return spacer + " <span class='toggle'></span>" + icon + value;
        }
      }

      function itemIcon(iurl, collapsed) {
        if (iurl) {
          return "<img class='tree-icon' src='"+iurl+"'>";
        } else {
          return '&nbsp;';
        }
      }

      function setOptions(data) {
        this.options = $.extend(true, {}, this.options, data);
        if (this.options.limit !== undefined) {
          this.dataStore.setOptions({pagesize: this.options.limit});
        }
      }

      function remoteOptions() {
        var self = this;
        var url = self.url + "options/";
        return $.ajax({
          url: url,
          dataType: 'json',
          success : function(data) {
            self.setOptions(data);
          }
        });
      }

      function setupColumns() {
        var self = this;
        self.super_.setupColumns.call(self);
        self.remoteColumns().always(function() {
          self.columns = self.updateColumns(self.columns);
          self.run();
        });
      }

      function remoteColumns() {
        var self = this;
        resp = $.ajax({
          url: self.url + "columns/",
          dataType: "json",
          // async: false
        });
        var classes = ['editor', 'formatter'];
        resp.done(function(data) {
          self.addColumns(data);
        });
        return resp;
      }

      function updateColumns(columns) {
        var self = this;
        if (columns.length > 0) {
          for (i in columns) {
            if (columns[i].id == "title") {
              columns[i] = $.extend(true, {}, self.treeColumnOptions, columns[i]);
            }
          }
        }
        return columns;
      }

      function initPager() {
        var self = this;
        if (self.dataStore.getOptions().usePager && self.options.usePager !== false) {
          self.pager = new Slick.Controls.RemotePager(self.dataStore, self.grid, $("#pager"));
          self.dataView.setRefreshHints({
            isFilterUnchanged: true
          });
          self.dataView.onRowCountChanged.subscribe(function (e, args) {
            self.grid.updateRowCount();
            // adjustHeight();
            self.grid.render();
          });
        }
      }

      function adjustHeight(vp) {
        var self = this;
        // Calculate the height of the Div by adding the values of the header row height and the row height * # rows
        var rowH = (self.options.rowHeight != null ? self.options.rowHeight : 25); // If no rowHeight is specified, use the default size 25 (could be set by CSS)
        var headerH = 0;

        // First, determine whether to account for the header row
        if( self.options.showHeaderRow == null || self.options.showHeaderRow == true)
        {  
            // If so, use specified height, or default height
            if( self.options.headerRowHeight == null )
                headerH = 27;
            else
                headerH = options.headerRowHeight;        
        }
        vp = vp || self.grid.getViewport();
        // Set the table size
        var viewSize = (self.dataView.getLength() * rowH);
        var containerSize = viewSize + headerH + 2;
        $('.slick-viewport', self.container).css( 'height' , containerSize + 'px');
        $(self.container).css('height' , containerSize + 'px');
        self.grid.resizeCanvas();
        self.grid.scrollCellIntoView(vp.bottom - 2, 0);
      }

      function dataViewOnRowsChanged(e, args) {
        this.grid.invalidateRows(args.rows);
        this.adjustHeight();
        this.grid.render();
      }

      function gridOnCellChange(e, args) {
        this.dataView.updateItem(args.item.id, args.item);
      }

      function gridOnClick(e, args) {
        var self = this;
        if ($(e.target).hasClass("toggle")) {
          self.treeClickToggle(e, args);
        }
        this.super_.gridOnClick.apply(self, arguments);
      }

      function treeClickToggle(e, args) {
        var self = this;
        var item = self.dataView.getItem(args.row);
        if (item) {
          if (!item._collapsed) {
            item._collapsed = true;
          } else {
            item._collapsed = false;
          }
          self.dataView.updateItem(item.id, item);
          var vp = self.grid.getViewport();
          self.dataStore.ensureData(vp.top, vp.bottom);
          self.grid.onViewportChanged.notify();
          self.adjustHeight();
          // self.grid.resizeCanvas();
        }
        e.stopImmediatePropagation();
      }

      function editAction(item, column, args) {
        var url = this.url + 'edit/id-' + item.id + '/' + window.location.search;
        if (item.edit) {
          url = item.edit;
        }
        window.location = url;
      }

      function deleteAction(item, column, args) {
        var url = this.url + 'delete/id-' + item.id + '/' + window.location.search;
        if (item.delete) {
          url = item.delete;
        }
        if (confirm("Вы уверенны что хотите удалить запись?")) {
           window.location = url;
        }
      }

      function dataStoreOnDataLoaded(e, args) {
        var self = this;
        for (var i = args.from; i <= args.to; i++) {
          self.grid.invalidateRow(i);
        }
        self.grid.updateRowCount();
        // adjustHeight();
        self.grid.render();
      }

      function gridOnViewportChanged(e, args) {
        var vp = this.grid.getViewport();
        this.dataStore.ensureData(vp.top, vp.bottom);
      }

      function onResize() {
        this.adjustHeight();
        this.grid.render();
      }

      function gridOnBeforeCellEditorDestroy(e, args) {
        var value = args.editor.serializeValue();
        var cell = args.grid.getActiveCell();
        var item = this.dataView.getItem(cell.row);
        var column = args.grid.getColumns()[cell.cell];
        var update = {id: item.id};
        update[column.field] = value;
        this.dataStore.updateItems([update], [column.field]);
      }



      function filter(item) {
        if (item && item.parent_id != null) {
          var parent = this.dataView.getItemById(item.parent_id);
          while (parent) {
            if (parent._collapsed) {
              return false;
            }
            parent = this.dataView.getItemById(parent.parent_id);
          }
        }
        return true;
      }

      function ifnull(val, default_) {
        return val === null ? default_ : val;
      }

      function onBeforeMoveRows (e, args) {
        var self = this;
          var data = self.dataStore.getFilteredItems();
          for (var i = 0; i < args.rows.length; i++) {
            if (args.rows[i] == args.insertBefore
                || args.rows[i] == args.insertBefore - 1
                || (data[args.insertBefore] && ifnull(data[args.rows[i]].parent_id, 0) != ifnull(data[args.insertBefore].parent_id, 0))
                ) {
              e.stopPropagation();
              return false;
            }
          }
          return true;
        }

        function onMoveRows(e, args) {
          var self = this;
          var extractedRows = [], left, right;
          var rows = args.rows;
          var insertBefore = args.insertBefore;
          var data_all = self.dataView.getItems();
          var data = self.dataStore.getFilteredItems();
          var insertBeforeItem = data[insertBefore];
          insertBefore = self.dataView.getIdxById(insertBeforeItem.id);
          var itemsToUpdate = [];
          left = data_all.slice(0, insertBefore);
          right = data_all.slice(insertBefore, data_all.length);

          rows.sort(function(a,b) { return a-b; });

          for (var i = 0; i < rows.length; i++) {
            var row = data[rows[i]];
            var index = self.dataView.getIdxById(row.id);
            extractedRows.push(row);

            
            var parent_ids = [ifnull(row.parent_id,0)];
            while (row.parent_id) {
              row = self.dataView.getItemById(row.parent_id);
              parent_ids.push(ifnull(row.parent_id,0));
            }
            index += 1;
            for (;index < data_all.length; index++) {

              var el = data_all[index];
              if ($.inArray(ifnull(el.parent_id,0), parent_ids) > -1) {
                break;
              }
              extractedRows.push(el);
            }

          }
          
          var split = extractedRows.slice().reverse();

          for (var i = 0; i < split.length; i++) {
            var idx = self.dataView.getIdxById(split[i].id);// rows[i];
            if (idx < insertBefore) {
              left.splice(idx, 1);
            } else {
              right.splice(idx - insertBefore, 1);
            }
          }

          data = left.concat(extractedRows.concat(right));

          var selectedRows = [];
          // for (var i = 0; i < rows.length; i++) {
          //   selectedRows.push(left.length + i);
          // }

          var parent_id = 0;
          if (extractedRows[0]) {
            parent_id = ifnull(extractedRows[0].parent_id, 0);
          }
          var ord = 1;
          for (var i = 0; i < data.length; i++) {
            var item = data[i];
            if (parent_id == ifnull(item.parent_id,0)) {
              item.ord = ord;
              itemsToUpdate.push(item);
              ord++;
              self.grid.invalidateRows([self.dataView.getRowById(item.id)]);
            }
          }

          var vp = self.grid.getViewport();
          self.grid.resetActiveCell();
          
          self.dataStore.updateItems(itemsToUpdate, ['ord']); //update only ord column
          self.dataView.beginUpdate();
          self.dataView.setItems(data);
          self.dataView.endUpdate();

          self.grid.setSelectedRows(selectedRows);
          self.adjustHeight(vp);
          self.grid.render();
      }

      function addFilters() {
        var self = this;
        var $form = $('.table-admin-filters-form form');
        if ($form.length) {
            $form.submit(function(event) {
                event.preventDefault();
                var data = {};
                $('input[type!=submit], select, textarea', this).each(function() {
                    data[$(this).attr('data-field-name')] = $(this).val();
                });
                if (data) {
                    self.dataStore.setSearch(data);
                    var vp = self.grid.getViewport();
                    self.dataStore.ensureData(vp.top, vp.bottom);
                }
                return false;
            });
        }
      }

      function gridOnSort(e, args) {
          this.dataStore.setSort(args.sortCol.field, args.sortAsc ? 1 : -1);
          var vp = this.grid.getViewport();
          this.dataStore.ensureData(vp.top, vp.bottom);
      }

      function run() {
        var self = this;
        self.build(self.dataView);
        self.init();
      }

      function init() {
        var self = this;
        this.super_.init.call(this);
        self.initPager();
        self.dataView.onRowsChanged.subscribe(function(e, args) {
          self.dataViewOnRowsChanged(e, args);
        });
        self.grid.onCellChange.subscribe(function(e, args) {
          self.gridOnCellChange(e, args);
        });
        self.dataStore.onDataLoaded.subscribe(function(e, args) {
          self.dataStoreOnDataLoaded(e, args);
        });
        self.grid.onViewportChanged.subscribe(function(e, args) {
          self.gridOnViewportChanged(e, args);
        });

        self.dataView.setFilter(function() {
          return self.filter.apply(self, arguments);
        });

        self.addFilters();

        self.grid.onSort.subscribe(function(e, args) {
          self.gridOnSort(e, args);
        });

        self.grid.onViewportChanged.notify();

        $(window).resize(function() {
          self.onResize();
        });

      }

      function defaultColumns() {
        var res = this.super_.defaultColumns();
        res.unshift({id:"title",  field:"title", name: "Заголовок"});
        return res;
      }


      // public
      return {
        extends_: 'Slick.Table',
        constructor_: function(container, dataStore, columns, options) {
          var self = this;
          this.super_.constructor.apply(this, arguments);
          this.treeColumnOptions = {
            formatter: function () {
              return self.titleFormat.apply(self, arguments);
            },
            cssClass: "cell-title",
            width: 220
          };
          this.dataStore = dataStore;
          this.dataView = dataStore.getView();
          this.data = this.dataView.getItems();
          this.url = options && options['url'] ? options['url'] : dataStore.getUrl();
          this.pager = null;
          this.remoteOptions().always(self.setupColumns());
        },

        // class property

        icons: {
          documentIcon: "/tao/images/SlickGrid/document.png",
          expandedIcon: "/tao/images/SlickGrid/folder-open.gif",
          collapsedIcon: "/tao/images/SlickGrid/folder.gif"
        },

        // class public method

        titleFormat: titleFormat,
        itemIcon: itemIcon,
        remoteOptions: remoteOptions,
        setOptions: setOptions,
        setupColumns: setupColumns,
        remoteColumns: remoteColumns,
        init: init,
        run: run,
        updateColumns: updateColumns,
        initPager: initPager,
        adjustHeight: adjustHeight,
        dataViewOnRowsChanged: dataViewOnRowsChanged,
        gridOnCellChange: gridOnCellChange,
        gridOnClick: gridOnClick,
        treeClickToggle: treeClickToggle,
        dataStoreOnDataLoaded: dataStoreOnDataLoaded,
        gridOnViewportChanged: gridOnViewportChanged,
        onResize: onResize,
        gridOnBeforeCellEditorDestroy: gridOnBeforeCellEditorDestroy,
        filter: filter,
        addFilters: addFilters,
        gridOnSort: gridOnSort,
        editAction: editAction,
        deleteAction : deleteAction,
        defaultColumns: defaultColumns,
        onBeforeMoveRows: onBeforeMoveRows,
        onMoveRows: onMoveRows

      };

    });
});