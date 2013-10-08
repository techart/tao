$(function () {
	window.TAO = window.TAO || {};
	TAO.fields = TAO.fields || {};
	TAO.fields.datepicker = function (input, lang) {
		if (!lang)
			lang = TAO.settings.lang || 'ru';

		$.datepicker.setDefaults($.datepicker.regional[lang]);
		input.datepicker({
			showOn: "button",
			buttonImage: "/tao/images/calendar.png",
			buttonImageOnly: true,
			dateFormat: 'dd.mm.yy'
		});
	}
});