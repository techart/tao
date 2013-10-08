$(function () {
	window.TAO = window.TAO || {};
	TAO.fields = TAO.fields || {};
	TAO.fields.autocomplete = function (input) {
		$(input).tao_autocomplete();
	}

	$.widget( "custom.tao_autocomplete", {
		_create: function() {
			this.wrapper = $( "<span>" )
				.addClass( "custom-autocomplete" )
				.insertAfter( this.element );
			this.href = this.element.data('href');

			this.element.hide();
			this._createAutocomplete();
		},

		_createAutocomplete: function() {
			var value = this.element.data('text');
			var element = this.element;
			var href = this.href;

			this.input = $( "<input>" )
				.appendTo(this.wrapper)
				.val(value)
				.addClass("custom-autocomplete-input")
				.autocomplete({
					delay: 0,
					minLength: 0,
					source:  $.proxy( this, "_source" )
				});

			this._on( this.input, {
				autocompleteselect: function(event, ui) {
					if(ui.item)
						this.element.val(ui.item.input_value);
				},
				autocompletechange: "_removeIfInvalid"
			});
		},

		_removeIfInvalid: function( event, ui ) {
			if ( ui.item ) {
				return;
			}

			var valid = false,
				value = this.input.val().toLowerCase(),
				element = this.element;

			if(this.items.length) {
				this.items.forEach(function(el) {
					if(el.title.toLowerCase() == value) {
						element.val(el.id);
						valid = true;
					}
				});
			}

			if(!valid) {
				this.input.val("");
				this.element.val("");
			}
		},

		_itemsRequest: function(success, query) {
			$.ajax({
				url: this.href,
				dataType: "json",
				data: {
					query: query
				},
				context: this,
				success: success
			});
		},

		_source: function(request, response) {
			var success = function(data) {
				this.items = data.data;
				response($.map(data.data, function (item) {
					return {
						label: item.title,
						input_value: item.id
					};
				}));
			};
			this._itemsRequest(success, request.term);
		},

		_destroy: function() {
			this.wrapper.remove();
			this.element.show();
		}
	});

});