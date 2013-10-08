TAO.require('tao/oop', function() {
	TAO.OOP.define('Slick.Table', function() {

		function buttonFormatter(row, cell, value, columnDef, dataContext) {
			var res = "<img class='cell-button-action cell-button-action-" + columnDef.id + "' src='" + columnDef.icon + "'>";
			return res;
		}

		function clickButton(e, args) {
			var self = this;
			var data = args.grid.getData();
			var item = null;
			if (data instanceof Array) {
				item = data[args.row];
			} else {
				item = data.getItem(args.row);
			}
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
						action = self[action]
					}
					action.call(self, item, column, args);
				}
			}
			e.stopImmediatePropagation();
		}

		function setupColumns() {
			var self = this;
			if (!self.columns) {
				self.columns = self.defaultColumns();
			}
			if (self.options.reordered) {
				self.columns.unshift(self.reorderColumn);
			}
		}

		function addColumns(fields) {
			var self = this;
			var classes_fields = ['editor', 'formatter'];
			var i = 1;
			$.each(fields, function(name, field) {
				if (!$.isEmptyObject(field)) {
					var column = {id: name, field: name, name: field.caption, width: self.defaultWidth, weight: i};
					column = $.extend(true, {}, field, column);
					for (var i in classes_fields) {
						var cname = classes_fields[i];
						if (field[cname]) {
							column[cname] = TAO.helpers.stringToFunction(column[cname]);
						}
						var skip = false;
						for (var i = 0; i < self.columns.length; i++) {
							if(self.columns[i].id == name) {
								self.columns[i] = $.extend(true, {}, self.columns[i], column);
								skip = true;
							};
						}
						if (skip) {
							continue;
						}
						self.columns.push(column);
						i++;
					}
				}
			});
			self.columns.sort(function(a,b) {
				var aw = typeof a.weight == 'undefined' ? 0 : a.weight,
				bw = typeof b.weight == 'undefined' ? 0 : b.weight;
				if (aw < bw) return -1;
				if (aw > bw) return 1;
				return 0;
			});
		}

		function gridOnClick(e, args) {
			var self = this;
			if ($(e.target).hasClass("cell-button-action")) {
				self.clickButton(e, args);
			}
		}

		function run() {

	    }

	    function gridOnBeforeCellEditorDestroy(e, args) {
			var cell = args.grid.getActiveCell();
			var item = args.grid.getData()[cell.row];
			this.store.update(item);
		}

		function onBeforeMoveRows(e, args) {

		}

		function saveOrder(data) {
		}

		function onMoveRows(e, args) {
			var self = this;
			var extractedRows = [], left, right;
			var rows = args.rows;
			var insertBefore = args.insertBefore;
			var data = self.grid.getData();
			left = data.slice(0, insertBefore);
			right = data.slice(insertBefore, data.length);

			rows.sort(function(a,b) { return a-b; });

			for (var i = 0; i < rows.length; i++) {
				extractedRows.push(data[rows[i]]);
			}

			rows.reverse();

			for (var i = 0; i < rows.length; i++) {
				var row = rows[i];
				if (row < insertBefore) {
					left.splice(row, 1);
				} else {
					right.splice(row - insertBefore, 1);
				}
			}

			data = left.concat(extractedRows.concat(right));

			var selectedRows = [];
			for (var i = 0; i < rows.length; i++) {
			  selectedRows.push(left.length + i);
			}


			self.saveOrder({files:data});

			self.grid.resetActiveCell();
			self.grid.setData(data);
			self.grid.setSelectedRows(selectedRows);
			self.grid.render();
		}

		function initReorder() {
			var self = this;
			self.selectModel = new Slick.RowSelectionModel();
			self.grid.setSelectionModel(self.selectModel);

			self.moveRowsPlugin = new Slick.RowMoveManager({
				cancelEditOnDrag: true
			});

			self.moveRowsPlugin.onBeforeMoveRows.subscribe(function (e, args) {
				self.onBeforeMoveRows(e, args);
			});

			self.moveRowsPlugin.onMoveRows.subscribe(function (e, args) {
				self.onMoveRows(e, args);
			});

			self.grid.registerPlugin(self.moveRowsPlugin);

			self.grid.onDragInit.subscribe(function (e, dd) {
				// prevent the grid from cancelling drag'n'drop by default
				e.stopImmediatePropagation();
			});
		}

		function init() {
			var self = this;
			self.grid.onClick.subscribe(function(e, args) {
				self.gridOnClick(e, args);
			});
			self.grid.onBeforeCellEditorDestroy.subscribe(function(e, args) {
				self.gridOnBeforeCellEditorDestroy(e, args);
			});
			if (self.options.reordered) {
				self.initReorder();
			}
		}

		function build(data) {
			if (!data) {
				data = this.data;
			}
			this.grid = new Slick.Grid(this.container, data, this.columns, this.options);
		}

		function defaultColumns() {
			var self = this;
			return [
				{id:"edit",   name: " ", maxWidth : 26, weight: 100,
				            cssClass: 'cell-button', icon: "/tao/images/edit.gif", formatter: self.buttonFormatter,
				            toolTip: 'Редактировать', action: 'editAction'},
				{id:"delete", name: " ", maxWidth : 26, weight: 101,
				            cssClass: 'cell-button', icon: "/tao/images/del.gif", formatter: self.buttonFormatter,
				            toolTip: 'Удалить', action: 'deleteAction'}
			];
		}



		return {
			constructor_: function(container, data, columns, options) {
				this.container = container;
				this.columns = columns;
				this.data = data;
				this.options = $.extend(true, {}, this.defaults, options);
				this.grid = null;
			},


	        defaults : {
				// autoHeight:true,
				editable: true,
				enableCellNavigation: true,
				asyncEditorLoading: false,
				enableColumnReorder: true,
				forceFitColumns: true,
				reordered: true,
				rerenderOnResize: true,
			},
			reorderColumn: {
				id: "#",
				name: "",
				width: 40,
				behavior: "selectAndMove",
				selectable: false,
				resizable: false,
				cssClass: "cell-reorder dnd",
				weight: -100
			},
			defaultWidth: 200,

			defaultColumns: defaultColumns,
			buttonFormatter: buttonFormatter,
			clickButton: clickButton,
			setupColumns: setupColumns,
			init: init,
			run: run,
			build: build,
			addColumns: addColumns,
			gridOnClick: gridOnClick,
			gridOnBeforeCellEditorDestroy: gridOnBeforeCellEditorDestroy,
			initReorder: initReorder,
			onBeforeMoveRows: onBeforeMoveRows,
			onMoveRows: onMoveRows,
			saveOrder: saveOrder


		}
	});

});