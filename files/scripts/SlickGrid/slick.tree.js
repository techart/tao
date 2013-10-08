(function ($) {
  // Register namespace
  $.extend(true, window, {
     "Slick": {
        "Tree": Tree
      }
  });



  function Tree(container, dataStore, columns, options) {
    var _self = this;
    var _dataStore = dataStore;
    var _dataView = dataStore.getView();
    var _url = options && options['url'] ? options['url'] : dataStore.getUrl();

    var _defaults = {
      // autoHeight:true,
      editable: true,
      enableCellNavigation: true,
      asyncEditorLoading: false,
      enableColumnReorder: true,
      forceFitColumns: true,
      reordered: true,
      rerenderOnResize: true,
      icons: {
          documentIcon: "/tao/images/SlickGrid/images/document.png",
          expandedIcon: "/tao/images/SlickGrid/images/folder-open.gif",
          collapsedIcon: "/tao/images/SlickGrid/images/folder.gif",
      }
    }
    var _treeColumnOptions = {
      formatter: titleFormat,
      cssClass: "cell-title",
      width: 220
    }

    var _grid = null;
    var _pager = null;

    options = $.extend(true, {}, _defaults, options);

    remoteOptions().always(function() {

      if (!columns) {
        columns = [
          {id:"title",  field:"title", name: "Заголовок"},
          // to php
          {id:"edit",   name: " ", maxWidth : 26, weight: 100,
                      cssClass: 'cell-button', icon: "/tao/images/edit.gif", formatter: buttonFormatter,
                      toolTip: 'Редактировать', action: 'editAction'},
          {id:"delete", name: " ", maxWidth : 26, weight: 101,
                      cssClass: 'cell-button', icon: "/tao/images/del.gif", formatter: buttonFormatter,
                      toolTip: 'Удалить', action: 'deleteAction'}
        ];
      }

      if (options.reordered) {
        columns.unshift(
          {
            id: "#",
            name: "",
            width: 40,
            behavior: "selectAndMove",
            selectable: false,
            resizable: false,
            cssClass: "cell-reorder dnd",
            weight: -100
          }
        );
      }

      remoteColumns().always(init);
    });

    function setOptions(data) {
      options = $.extend(true, {}, options, data);
      if (options.limit !== undefined) {
        _dataStore.setOptions({pagesize: options.limit});
      }
    }

    function remoteOptions() {
      var url = _url + "options/";
      return $.ajax({
        url: url,
        dataType: 'json',
        success : function(data) {
          setOptions(data);
        }
      });
    }

    function buttonFormatter(row, cell, value, columnDef, dataContext) {
      var res = "<img class='cell-button-action cell-button-action-" + columnDef.id + "' src='" + columnDef.icon + "'>";
      return res;
    }

    
    function init() {
      columns = updateColumns(columns)
      _grid = new Slick.Grid(container, _dataView, columns, options);

      if (_dataStore.getOptions().usePager && options.usePager !== false) {
        var _pager = new Slick.Controls.RemotePager(_dataStore, _grid, $("#pager"));
         _dataView.setRefreshHints({
          isFilterUnchanged: true
        });
        // _pager.setPageSize(_dataStore.getPageSize());

        _dataView.onRowCountChanged.subscribe(function (e, args) {
          _grid.updateRowCount();
          // adjustHeight();
          _grid.render();
        });
      }
      
      _dataView.onRowsChanged.subscribe(function (e, args) {
        _grid.invalidateRows(args.rows);
        adjustHeight();
        _grid.render();
      });

      _grid.onCellChange.subscribe(function (e, args) {
        _dataView.updateItem(args.item.id, args.item);
      });
  
  
      _grid.onClick.subscribe(function (e, args) {
        if ($(e.target).hasClass("toggle")) {
          treeClickToggle(e, args);
        }
        if ($(e.target).hasClass("cell-button-action")) {
          clickButton(e, args);
        }
      });

      _grid.onViewportChanged.subscribe(function (e, args) {
        var vp = _grid.getViewport();
        _dataStore.ensureData(vp.top, vp.bottom);
      });

      _dataStore.onDataLoading.subscribe(function () {
        //loadingIndicator
      });

      _dataStore.onDataLoaded.subscribe(function (e, args) {
        for (var i = args.from; i <= args.to; i++) {
          _grid.invalidateRow(i);
        }
        _grid.updateRowCount();
        // adjustHeight();
        _grid.render();
      });

      _grid.onBeforeCellEditorDestroy.subscribe(function(e, args) {
        var value = args.editor.serializeValue();
        var cell = args.grid.getActiveCell();
        var item = _dataView.getItem(cell.row);
        var column = args.grid.getColumns()[cell.cell];
        var update = {id: item.id};
        update[column.field] = value;
        dataStore.updateItems([update], [column.field]);
      });

      //dataView may already have filter
      _dataView.setFilter(filter);

      // load the first page
      _grid.onViewportChanged.notify();

      if (options.reordered) {
        initReorder();
      }

      $(window).resize(function() {
        adjustHeight();
        _grid.render();
      });

    }

    function adjustHeight() {
        // Calculate the height of the Div by adding the values of the header row height and the row height * # rows
        var rowH = (options.rowHeight != null ? options.rowHeight : 25); // If no rowHeight is specified, use the default size 25 (could be set by CSS)
        var headerH = 0;

        // First, determine whether to account for the header row
        if( options.showHeaderRow == null || options.showHeaderRow == true)
        {  
            // If so, use specified height, or default height
            if( options.headerRowHeight == null )
                headerH = 27;
            else
                headerH = options.headerRowHeight;        
        }
        var vp = _grid.getViewport();
        // Set the table size
        var viewSize = (_dataView.getLength() * rowH);
        var containerSize = viewSize + headerH + 2;
        $('.slick-viewport', container).css( 'height' , containerSize + 'px');
        $(container).css( 'height' , containerSize + 'px');
        _grid.resizeCanvas();
        _grid.scrollCellIntoView(vp.bottom, 0);
    }

    function ifnull(val, default_) {
      return val === null ? default_ : val;
    }

    function initReorder() {
      _grid.setSelectionModel(new Slick.RowSelectionModel());

      var moveRowsPlugin = new Slick.RowMoveManager({
        cancelEditOnDrag: true
      });

      moveRowsPlugin.onBeforeMoveRows.subscribe(function (e, args) {
        var data = _dataStore.getFilteredItems();
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
      });

      moveRowsPlugin.onMoveRows.subscribe(function (e, args) {
        var extractedRows = [], left, right;
        var rows = args.rows;
        var insertBefore = args.insertBefore;
        var data_all = _dataView.getItems();
        var data = _dataStore.getFilteredItems();
        var insertBeforeItem = data[insertBefore];
        insertBefore = _dataView.getIdxById(insertBeforeItem.id);
        var itemsToUpdate = [];
        left = data_all.slice(0, insertBefore);
        right = data_all.slice(insertBefore, data_all.length);

        rows.sort(function(a,b) { return a-b; });

        for (var i = 0; i < rows.length; i++) {
          var row = data[rows[i]];
          var index = _dataView.getIdxById(row.id);
          extractedRows.push(row);

          
          var parent_ids = [ifnull(row.parent_id,0)];
          while (row.parent_id) {
            row = _dataView.getItemById(row.parent_id);
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
          var idx = _dataView.getIdxById(split[i].id);// rows[i];
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
            _grid.invalidateRows([_dataView.getRowById(item.id)]);
          }
        }

        _grid.resetActiveCell();
        
        _dataStore.updateItems(itemsToUpdate, ['ord']); //update only ord column
        _dataView.beginUpdate();
        _dataView.setItems(data);
        _dataView.endUpdate();

        _grid.setSelectedRows(selectedRows);
        adjustHeight();
        _grid.render();
      });

      _grid.registerPlugin(moveRowsPlugin);

      _grid.onDragInit.subscribe(function (e, dd) {
        // prevent the grid from cancelling drag'n'drop by default
        e.stopImmediatePropagation();
      });


      _grid.onSort.subscribe(function (e, args) {
        _dataStore.setSort(args.sortCol.field, args.sortAsc ? 1 : -1);
        var vp = _grid.getViewport();
        _dataStore.ensureData(vp.top, vp.bottom);
      });

      addFilters();
    }

    function addFilters() {
      var $form = $('.table-admin-filters-form form');
      if ($form.length) {
          $form.submit(function(event) {
              event.preventDefault();
              var data = {};
              $('input[type!=submit], select, textarea', this).each(function() {
                  data[$(this).attr('data-field-name')] = $(this).val();
              });
              if (data) {
                  _dataStore.setSearch(data);
                  var vp = _grid.getViewport();
                  _dataStore.ensureData(vp.top, vp.bottom);
              }
              return false;
          });
      }
    }

    function clickButton(e, args) {
      var item = _dataView.getItem(args.row);
      if (item) {
        var column = args.grid.getColumns()[args.cell];
        if (column && column.action) {
          var action = column.action;
          if (typeof action == 'string') {
            findAction = TAO.helpers.stringToFunction(action);
            if (findAction)
              action = findAction;
          }
          if (typeof action !== "function") {
            action = _self[action]
          }
          action(item, column, args);
        }
      }
      e.stopImmediatePropagation();
    }

    function editAction(item, column, args) {
      var url = _url + "edit/id-" + item.id + '/' + window.location.search;
      if (item.edit) {
        url = item.edit;
      }
      window.location = url;
    }

    function deleteAction(item, column, args) {
      var url = _url + "delete/id-" + item.id + '/' + window.location.search;
      if (item.delete) {
        url = item.delete;
      }
      if (confirm("Вы уверенны что хотите удалить запись?")) {
         window.location = url;
      }
    }

    function treeClickToggle(e, args) {
      var item = _dataView.getItem(args.row);
      if (item) {
        if (!item._collapsed) {
          item._collapsed = true;
        } else {
          item._collapsed = false;
        }
        _dataView.updateItem(item.id, item);
        var vp = _grid.getViewport();
        _dataStore.ensureData(vp.top, vp.bottom);
        _grid.onViewportChanged.notify();
        adjustHeight();
        // _grid.resizeCanvas();
      }
      e.stopImmediatePropagation();
    }

    function updateColumns(columns) {
      if (columns.length > 0) {
        for (i in columns) {
          if (columns[i].id == "title") {
            columns[i] = $.extend(true, {}, _treeColumnOptions, columns[i]);
          }
        }
      }
      return columns;
    }
    
    function destroy() {
    }

    function filter(item) {
      if (item && item.parent_id != null) {
        var parent = _dataView.getItemById(item.parent_id);
        while (parent) {
          if (parent._collapsed) {
            return false;
          }
          parent = _dataView.getItemById(parent.parent_id);
        }
      }
      return true;
    }

    function itemIcon(iurl, collapsed) {
      if (iurl) {
        return "<img class='tree-icon' src='"+iurl+"'>";
      } else {
        return '&nbsp;';
      }
    }

    function titleFormat(row, cell, value, columnDef, dataContext) {
      var icon = '&nbsp;';
      var iurl = '';
      if (dataContext.icon) {
        iurl = dataContext.icon;
      }
      value = value.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
      value = "<span class='tree-value'>"+value+"</span>"
      var spacer = "<span style='display:inline-block;height:1px;width:" + (15 * dataContext["depth"]) + "px'></span>";
      var idx = _dataView.getIdxById(dataContext.id);
      data = _dataView.getItems();
      if (data[idx + 1] && data[idx + 1].depth > data[idx].depth) {
        if (dataContext._collapsed) {
          icon = itemIcon(iurl ? iurl : options.icons.collapsedIcon);
          return spacer + " <span class='toggle expand'></span>"+icon + value;
        } else {
          icon = itemIcon(iurl ? iurl : options.icons.expandedIcon);
          return spacer + " <span class='toggle collapse'></span>"+icon + value;
        }
      } else {
        icon = itemIcon(iurl ? iurl : options.icons.documentIcon);
        return spacer + " <span class='toggle'></span>" + icon + value;
      }
    }

    function remoteColumns() {
      resp = $.ajax({
        url: _url + "columns/",
        dataType: "json",
        // async: false
      });
      var classes = ['editor', 'formatter'];
      resp.done(function(data) {
        for (name in data) {
          var field = data[name];
          var field = $.extend(true, {}, {id:name, field:name, name: data[name].caption}, field);
          for (var i in classes) {
            var cname = classes[i];
            if (field[cname]) {
              field[cname] = TAO.helpers.stringToFunction(field[cname]);
            }
          }
          var skip = false;
          for (var i = 0; i < columns.length; i++) {
            if(columns[i].id == name) {
              columns[i] = $.extend(true, {}, columns[i], field);
              skip = true;
            };
          }
          if (skip) {
            continue;
          }
          columns.push(field);
        }
        columns.sort(function(a,b) {
          var aw = typeof a.weight == 'undefined' ? 0 : a.weight,
          bw = typeof b.weight == 'undefined' ? 0 : b.weight;
          if (aw < bw) return -1;
          if (aw > bw) return 1;
          return 0;
        });
      });
      return resp;
    }
    
    // Public API
    $.extend(this, {
     // "init": init,
      "destroy": destroy,
      "titleFormat" : titleFormat,
      "filter": filter,
      // "getColumns": getColumns,
      "editAction" : editAction,
      "deleteAction" : deleteAction
    });
  }
})(jQuery);