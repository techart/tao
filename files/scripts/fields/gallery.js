$(function() {

window.TAO = window.TAO || {}
TAO.fields = TAO.fields || {}

TAO.fields.gallery = TAO.fields.gallery || {}

$('body').keyup(function(e) {
	if (e.keyCode == 27) {
		$('.gallery-user-mods.clone').hide();
		$('.gallery-list-item').removeClass('gallery-list-item-hover');
	}
});


TAO.fields.gallery.user_mods = function(field) {

	if ($(field).find('.gallery-user-mods').length < 1) return;

	var name = $('input', field).attr('data-field-name');
	
	var remove_mods = function() {
		$('.gallery-user-mods.clone').remove();
	}
	
	var clear = function() {
		remove_mods();
		$('.gallery-list-item', field).removeClass('gallery-list-item-hover');
	}
	
	var mods_upload = function(ref) {
		var $ref = $(ref);
		var up = new AjaxUpload(ref, {
			action: $ref.attr('data-href'),
			name: 'user_mode_attachement',
			autoSubmit: true,
			responseType: false,
			onCancel: function() {
				clear();
			},
			onSubmit: function(file, extension){
				clear();
			},
			onComplete: function(file, resp){
				if (resp == 'ok') {
					$('#attaches-list-' + name)[0].__reload();
				} else {
					alert(resp);
				}
			}
		});
	}
	
	var mods_del = function(del) {
		var $del = $(del);
		$del.click(function(){
			if (confirm("Удалить загруженное изображение?")) {
				$.ajax({
					url: $del.attr('data-href'),
					complete: function(jqXHR, textStatus) {
						clear();
						if (textStatus == 'success' && jqXHR.response == 'ok') {
							$('#attaches-list-' + name)[0].__reload();
							//alert('Изображение удалено');
						} else {
							alert(jqXHR.response);
						}
					}
				})
			}
			return false;
		})
	} 
	
	
	var inside = function(x,y,el) {
		var offset = el.offset();
		if (!offset) return false;
		var w = el.width();
		var h = el.height();
		var ww = x - offset.left;
		var hh = y - offset.top;
		return ww >= 0 && w >= ww && hh >= 0 && h > hh;
	}
	
	$('.gallery-image-caption-button', field).each(function(i, button) {
		var $button = $(button);
		var $item = $button.parents('.gallery-list-item');
		
		$item.mouseleave(function (e) {
			if (!inside(e.pageX, e.pageY, $('.gallery-user-mods.clone')) && !inside(e.pageX, e.pageY, $item))
				clear();
		});
		
		$button.
			mouseenter(function(e) {
				clear();
				
				var $mods = $('.gallery-user-mods', $item).clone();
				$mods.addClass('clone');
				$mods.appendTo('body');
				
				var item_size = $item.offset();
				
				$mods.css({
					left: $(window).scrollLeft() + item_size.left + $item.width()/2 - $mods.width()/2,
					bottom: $(window).height() - $button.offset().top + 5
				})
				
				$mods.find('.user-mod-upload').each(function(i, up) {
					mods_upload(up);
				})
				$mods.find('.user-mod-delete').each(function(i, del) {
					mods_del(del);
				})
				
				$mods.show();
				
				$mods.mouseleave(function(e) {
					if (!inside(e.pageX, e.pageY, $(this))) {
						clear();
					}
				}).
				mouseenter(function(e) {
					$item.addClass('gallery-list-item-hover');
				})
				
				
			}).
			mouseleave(function(e) {
				if (e.pageY < $(this).offset().top) {
					//remove_mods();
				}
			});
	})
	

}

TAO.fields.gallery.rotate = function(field) {
	$('.gallery-button-rotate', field).click(function(e) {
		var $button = $(this);
		var url = $button.attr('href');
		TAO.helpers.ajax_block($('.attaches-list', field), {url: url}).always(function(resp) {
			if (resp=='ok') {
				var parent = $button.parents('.attaches-list')[0];
				parent.__reload();
			}
			else alert(resp);
		});
		return false;
	});
}


TAO.fields.gallery.process = function (field) {
	$('body').bind('documents_run', function(e, list, field_name, field) {
		var $gallery = $(".gallery-list", list);
		var dragSelector = ".gallery-list-item";
		$gallery.unbind("mousedown").find(dragSelector).css("cursor", "auto");
		$gallery.dragsort({
			dragSelector: dragSelector,
			dragEnd: function() {
				$item = $(this);
				$list = $item.parent(".gallery-list");
				TAO.fields.documents.submit(field)
			},
			dragBetween: false,
			placeHolderTemplate: "<li class='gallery-list-item gallery-list-empty'><div class='gallery-list-empty-inner' /></li>"
		});

		TAO.fields.gallery.rotate(field);
	});

	$('.field-gallery', field).each(function(index, item) {
	TAO.fields.gallery.user_mods(item);
	var name = $('input', item).attr('data-field-name');
	$('#attaches-list-' + name, field).bind('reload', function(e, list) {
		TAO.fields.gallery.user_mods(item);
	})
})

}

})
