$(function() {
	var menu = $('<div>')
	.addClass('embedded_admin_menu_popup')
	.bind('mouseleave',function() {
		$(this).hide();
	});
	var icon = $('<div>')
	.addClass('embedded_admin_menu_icon')
	.html('&nbsp;')
	.bind('mouseenter',function() {
		menu.fadeIn(300);
	});

	menu.append(embedded_admin_menu);
	$('body').append(icon).append(menu);

	$('.embedded_admin_menu_popup li').hover(embededAdminMenuItemHover, embededAdminMenuItemOut);
});

function embededAdminMenuItemHover() {
	if($(this).children('ul').length) {
		$(this).children('ul').show();
		$(this).children('ul').css({
			right: '-' + $(this).children('ul').outerWidth() + 'px'
			});
		embededAdminMenuCorrectPosition($(this).children('ul'));
	}
	$(this).addClass('active');
}

function embededAdminMenuItemOut() {
	if($(this).children('ul').length)
		$(this).children('ul').hide();
	$(this).removeClass('active');
}

function embededAdminMenuCorrectPosition(menu) {
	if($(menu).offset().top + $(menu).outerHeight() > $(window).height()) {
		$(menu).css({
			top: ($(window).height() - $(menu).outerHeight() - $(menu).parent().offset().top) + 'px'
		});
	}
}