<?php

class CMS_Lang_Vars_Ru implements Core_ModuleInterface {

	const MODULE  = 'CMS.Lang.Vars.Ru';
	const VERSION = '0.0.0';

	public $title			= 'Настройки';
	public $root_dir 		= 'Корневой каталог'; 
	
	public $dir 			= 'Подкаталог'; 
	public $int		 	= 'Целое число'; 
	public $string 			= 'Строка'; 
	public $text 			= 'Текст'; 
	public $html			= 'HTML-текст'; 
	public $array 			= 'Массив'; 
	public $file 			= 'Файл'; 
	public $mail 			= 'Почтовое сообщение'; 
	public $htmp 			= 'HTML с параметрами';

	public $help 			= 'Справка';

	public $invalid_int		= 'Неверное числовое значение';
	public $levelup			= 'На уровень вверх';
	public $add_file		= 'Добавить файл';
	public $creation		= 'Создание новой настройки';
	public $dump_gen		= 'Генерация дампа';
	public $dump_get		= 'Получить дамп';
	public $dump_uploading		= 'Загрузка дампа';
	public $dump_upload		= 'Загрузить на сайт ранее полученный дамп можно здесь';
	public $dump_about		= 'Для удобного переноса настроек между проектами и хостингами предлагается воспользоваться дампом. Дамп - это один текстовый файл, содержащий в себе как настройки, так и прикрепленные к ним файлы (в формате Base64).';
	
	public $type			= 'Тип';
	public $identifier		= 'Идентификатор';
	public $comment			= 'Комментарий';
	public $restricted		= 'Ограниченный доступ';
	
	public $no_vars			= 'Здесь не создано ни одной настройки';
	public $no_attaches		= 'Прикрепленных файлов нет';
	
	public $submit_edit		= 'Изменить';
	public $submit_add		= 'Добавить';
	public $submit_create		= 'Создать';
	public $submit_upload		= 'Загрузить';
	
	public $confirm_delete_var		= 'Вы действительно хотите удалить эту настройку?';
	public $confirm_delete_file		= 'Вы действительно хотите удалить этот файл?';
	
	public $tab_text 		= 'Текст';
	public $tab_attaches	= 'Файлы и картинки';
	public $tab_parms 		= 'Параметры';
	
}

