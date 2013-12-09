<?php
/**
 * @package CMS\Lang\Common\Ru
 */


class CMS_Lang_Common_Ru implements Core_ModuleInterface { 
	const MODULE = 'CMS.Lang.Common.Ru'; 
	const VERSION = '0.0.0'; 
	
	public $pages = 'Страницы'; 
	public $next = 'След.'; 
	public $month_i = array('январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'); 
	public $month_r = array('января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'); 
	public $name = 'Наименование';
	public $components = 'Компоненты';
	
	public $ta_list			= "Список записей";
	public $ta_norows		= 'Записи отсутствуют';
	public $ta_title_edit		= 'Редактирование записи';
	public $ta_title_add		= 'Добавление записи';
	public $ta_del_confirm		= 'Вы действительно хотите удалить эту запись?';
	public $ta_submit_add		= 'Добавить';
	public $ta_submit_edit		= 'Сохранить';
	public $ta_save_button		= 'Сохранить и остаться';
	public $ta_submit_mass_edit	= 'Изменить данные';
	public $ta_button_add		= 'Добавить запись';
	public $ta_button_update	= 'Изменить';
	public $ta_button_list		= 'К списку';
	public $ta_WYSIWYG		= 'Визуальный редактор';

	public $unable_in_add		= 'Недоступно при добавлении записи';
	public $ta_file			= "Файл";
	public $ta_addfile		= "Добавить файл";
	public $ta_addimg		= "Добавить изображение";
	public $ta_adddoc		= "Добавить документ";
	public $ta_delfile		= "Удалить";
	public $ta_download		= "Скачать";
	public $ta_reupload		= "Перезалить";
	public $ta_upload		= "Закачать";
	public $ta_browse		= "Выбрать";
	public $no_file_uploaded	= "Файл не загружен!";
	public $error_file_upload	= "Ошибка загрузки файла!";
	public $ta_dfconfirm		= 'Вы действительно хотите удалить этот файл?';
	public $ta_diconfirm		= 'Вы действительно хотите удалить это изображение?';
	public $ta_image_error		= 'Ошибка изображения';
	public $file_not_found		= 'Файл не найден';
	public $a_dir_undef		= 'Не определен параметр <b>attaches_dir</b>';
	public $no_attachements		= 'Прикрепленных файлов нет';
	public $captcha			= 'Введите число, которое видите на картинке';
	public $captcha_error		= 'Введен неверный цифровой код';
	
	public $all_languages		= 'Все языки';
	public $flush_caches		= 'Очистить кеш';


	public $latitude		 = 'Широта';
	public $longitude		 = 'Долгота';
	public $zoom			= 'Масштаб';
	public $decimal			= 'десятичные';
	public $degree			= 'градусы';
	public $to_marker		= 'К маркеру';

	public function date_in_human_language($idate) { 
		return date('d',$idate).' '.$this->month_r[date('m',$idate)-1].' '.date('Y',$idate); 
	} 
	
} 

