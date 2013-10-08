$(function() {

	TAO = window.TAO = window.TAO || {}
	TAO.fields = TAO.fields || {}
	TAO.fields.__lang_switcher = function(el) {
		$('.field-lang-switcher', $(el)).click(function() {
			var self = $(this);
			var rel = self.attr('rel');
			$.cookie('admin-field-lang',rel,{path:'/'});
			$('.field-lang-switcher').removeClass('field-lang-switcher-current');
			$('.field-lang-switcher-'+rel).addClass('field-lang-switcher-current');
			$('.field-lang-panel').removeClass('field-lang-panel-current');
			$('.field-lang-panel-'+rel).addClass('field-lang-panel-current');
			$('.field-lang').removeClass('field-lang-visible');
			$('.field-lang-'+rel).addClass('field-lang-visible');
		});
	}	
});
