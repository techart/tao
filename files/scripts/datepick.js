$.dpText = {
	TEXT_PREV_YEAR		:	'Предыдущий год',
	TEXT_PREV_MONTH		:	'Предыдущий месяц',
	TEXT_NEXT_YEAR		:	'Следующий год',
	TEXT_NEXT_MONTH		:	'Следующий месяц',
	TEXT_CLOSE		:	'Закрыть',
	TEXT_CHOOSE_DATE	:	'Выбрать дату'
}
$(function() {
	$('.date-pick').each(function() {
		var v = this.value;
		var e = $(this).datePicker({startDate:'01/01/1900'});
/*		if (v=='') {
			e.val(new Date().asString()).trigger('change');
			this.value = '';
		}
		
		else {
			e.val(v).trigger('change');
		}*/
	});
});