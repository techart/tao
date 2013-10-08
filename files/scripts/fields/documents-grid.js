$(function() {

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}
TAO.fields.documents_grid = TAO.fields.documents_grid || {}

TAO.require(['tao/oop', 'SlickGrid/slick.table'], function() {
	TAO.OOP.define('TAO.class.fields.documentsGrid', function() {

		function editAction(item, column, args) {
			this.grid.setActiveCell(args.row, args.cell + 2);
			this.grid.editActiveCell();
		}

		function downloadAction(item, column, args) {
			window.open(item.path);
		}

		function defaultColumns() {
			var self = this;
			var res = this.super_.defaultColumns();
			res.unshift({id:"download",   name: " ", maxWidth : 26, weight: -91,
	                      cssClass: 'cell-button', icon: "/tao/images/download.png", formatter: self.buttonFormatter,
	                      toolTip: 'Скачать', action: self.downloadAction});
			for (var i = 0; i < res.length; i++) {
				res[i].weight = -100;
			};
			return res;
		}

		function deleteAction(item, column, args) {
			var self = this;
			if (confirm('Вы уверенны что хотите удалить документ?')) {
				self.store.destroy(item, function() {
					self.store.load(function(data) {
						self.grid.setData(data.files);
						self.grid.invalidate();
						self.grid.render();
					});
				});
			}
		}

		function saveOrder(data) {
			this.store.save(data);
		}

		function run() {
			var self = this;
			this.store.load(function(data) {
				self.build(data.files);
				self.init();
			});
		}

		function fixWidth() {
			var self = this;
			var c = $(self.container);
			if (c.length < 1) return;
			var width = c.parent('div').width();
			if (!c.css('width')) {
				c.width(width);
			} else {
				c.parent('div').width(c.css('width'));
			}
		}

		return {
			extends_: 'Slick.Table',
			constructor_: function (container, store) {
				this.super_.constructor.apply(this, arguments);
				this.fixWidth();
				this.store = store;
				if (typeof this.options.autoHeight == 'undefined') {
					this.options.autoHeight = true;
				}
				// this.options.forceFitColumns = false;
				this.setupColumns();
			},

			editAction: editAction,
			downloadAction: downloadAction,
			deleteAction: deleteAction,
			defaultColumns: defaultColumns,
			run: run,
			saveOrder: saveOrder,
			fixWidth: fixWidth

		}
	});
});


TAO.fields.documents_grid.process = function(field) {
	TAO.require(['tao/oop', 'tao/data', 'SlickGrid/slick.table'], function() {
		var base_element = field.find('.field-documents-grid-root');
		var field_name = base_element.attr('data-field-name');
		$('.addattache', field).attr('data-url-reload', '');

		var api = {urls: TAO.settings.fields[field_name].api};
		var store = TAO.data.Store(api);
		var table = new TAO.class.fields.documentsGrid(base_element[0], store);
		table.addColumns(TAO.settings.fields[field_name].fields);
		table.run();
		
		$(".admin-table-form-tabs .tabs-nav").bind('showTab', function (e, clicked) {
			var tab = $($(clicked).attr('href'));
			if (tab.find(base_element).length) {
				var width = base_element.parents('.attaches-list').width();
				base_element.parent('div').width(width);
				base_element.width(width);
				table.grid.resizeCanvas();
				table.grid.invalidate();
			}
		});

		//from attaches field
		var id = field.find('.attaches-list').attr('id');
		$('#' + id, field).bind('reload', function(e, list, type) {
			field = $(list).parents('.field')
			if (type == 'upload') {
				store.load(function(data) {
					table.grid.setData(data.files);
					table.grid.invalidate();
					table.grid.render();
					var lastColumn = table.grid.getColumns().length - 1
					table.grid.setActiveCell(data.files.length - 1, lastColumn);
					table.grid.editActiveCell();
				});
			}
		});
	});

}

});
