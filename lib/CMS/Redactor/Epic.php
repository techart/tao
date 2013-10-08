<?php
/**
 * EpicEditor редактор
 *
 * @author Svistunov <svistunov@techart.ru>
 *
 * @version  0.1.0
 *
 * @package CMS\Redactor
 * 
 */

Core::load('CMS.Redactor');

/**
 * EpicEditor редактор. Класс модуля.
 *
 * @package CMS\Redactor
 * 
 */
class CMS_Redactor_Epic extends CMS_Redactor_AbstractEditor implements Core_ModuleInterface
{
	/**
	 * Библиотечные файлы
	 *
	 * Обычно список файлов для подключения плагина
	 * @return array
	 */
	protected function libraries_files()
	{
		return array(
			array('name' => 'editors/epiceditor/js/epiceditor.js', 'type' => 'lib', 'weight' => -1),
			array('name' => 'editors/reMarked.js')
		);
	}
	
	/**
	 * Настройки по умолчанию
	 * @return array
	 */
	public function default_settings()
	{
		return array(
			'basePath' => '/scripts/editors/epiceditor',
			// 'theme' => array('editor' => 'themes/editor/epic-light.css'),
		);
	}

}