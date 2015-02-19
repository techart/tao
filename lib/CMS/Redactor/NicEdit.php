<?php
/**
 * NicEdit редактор
 *
 * @author   Svistunov <svistunov@techart.ru>
 *
 * @version  0.1.0
 *
 * @package  CMS\Redactor
 *
 */

Core::load('CMS.Redactor');

/**
 * NicEdit редактор. Класс модуля.
 *
 * @package CMS\Redactor
 *
 */
class CMS_Redactor_NicEdit extends CMS_Redactor_AbstractEditor implements Core_ModuleInterface
{
	/**
	 * Библиотечные файлы
	 *
	 * Обычно список файлов для подключения плагина
	 *
	 * @return array
	 */
	protected function libraries_files()
	{
		return array(
			array('name' => CMS::stdfile_url('scripts/nicEditor/nicEdit.js'), 'type' => 'lib', 'weight' => -1),
			array('name' => CMS::stdfile_url('styles/nicEditor/nicEdit.css')),
		);
	}

	/**
	 * Настройки по умолчанию
	 *
	 * @return array
	 */
	public function default_settings()
	{
		return array(
			'fullPanel' => true,
			'iconsPath' => CMS::stdfile_url('images/nicEditor/nicEditorIcons.gif'),
		);
	}

}