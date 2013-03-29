$(function() {

	window.TAO = window.TAO || {}
	TAO.fields = TAO.fields || {}
	TAO.fields.multivalue = TAO.fields.multivalue || {}
	
	TAO.fields.multivalue.add_button = function() {
		var $b = $(this);
		var url = $b.attr('data-href');
		var $last_item = $b.parents('.field-multivalue').find('.field-multivalue-item').last();
		var $items = $b.parents('.field-multivalue').find('.field-multivalue-items');
		var last_name = $last_item.attr('data-item-name');
		$.get(url, {'last_name' : last_name}, function(data) {
			$new_item = $(data.data);
			if ($last_item.length)
				$new_item.insertAfter($last_item);
			else
				$items.append($new_item);
			$new_item.find('.delete-item').click(TAO.fields.multivalue.delete_button);
			
			TAO.helpers.process_data(data);
			
		}, 'json');
		return false;
	}
	
	TAO.fields.multivalue.delete_button = function() {
		$b = $(this);
		var url = $b.attr('data-href');
		$item = $b.parents('.field-multivalue-item');
		$.post(url, function(data) {
			$item.remove();
		})
	}
	
	$('.field-submit-add-multivalue .submit-button').click(TAO.fields.multivalue.add_button);
	
	$('.field-multivalue-item-actions .delete-item').click(TAO.fields.multivalue.delete_button);
	
})
