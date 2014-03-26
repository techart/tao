<?php
/**
 * CKEditor редактор
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
 * CKEditor редактор. Класс модуля.
 *
 * @package CMS\Redactor
 * 
 */
class CMS_Redactor_CKEditor extends CMS_Redactor_AbstractEditor implements Core_ModuleInterface
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
			array('name' => '/editors/ckeditor/ckeditor.js', 'type' => 'lib', 'weight' => -1)
		);
	}

}