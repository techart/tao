<?php
/**
 * Redactor редактор
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
 * Redactor редактор. Класс модуля.
 *
 * @package CMS\Redactor
 * 
 */
class CMS_Redactor_Redactor extends CMS_Redactor_AbstractEditor implements Core_ModuleInterface
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
			array('name' => '/editors/redactor/redactor.js', 'type' => 'lib', 'weight' => -1),
			array('name' => '/editors/redactor/redactor.css')
		);
	}
	
	/**
	 * Установка ссылки для добавления картинок в редактор
	 * @param string $link ссылка
	 */
	public function set_images_link($link)
	{
		return $this->attach_options['imageGetJson'] = $link;
		return $this;
	}
}