<?php
/**
 * TinyMCE редактор
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
 * TinyMCE редактор. Класс модуля.
 *
 * @package CMS\Redactor
 * 
 * @todo: maybe configure for file paths
 */
class CMS_Redactor_Tiny extends CMS_Redactor_AbstractEditor implements Core_ModuleInterface
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
			array('name' => '/editors/tiny_mce/jquery.tinymce.js', 'type' => 'lib', 'weight' => -1),
			array('name' => '/editors/tiny_mce/tiny_mce.js', 'type' => 'lib', 'weight' => -2)
		);
	}

	/**
	 * Установка ссылки для добавления картинок в редактор
	 * @param string $link ссылка
	 */
	public function set_images_link($link)
	{
		return $this->attach_options['external_image_list_url'] = $link;
		return $this;
	}

	/**
	 * Настройки по умолчанию
	 * @return array
	 */
	public function default_settings()
	{
		return array(
			'theme' => 'advanced',
			'mode' => 'specific_textareas',
			'elements' => "editor_textarea",
			//'editor_selector' => 'mce-advanced',
			'content_css' => "/styles/editor.css",
			'paste_auto_cleanup_on_paste' => true,
			'dialog_type' => "modal",
			'language' => 'ru',
			'skin'=> 'o2k7',
			'skin_variant' => 'silver',
			'plugins' => "contextmenu,autosave,paste,preview,fullscreen,table,advimage,media,advlink,inlinepopups",
			'relative_urls' => false,
			'convert_urls' => false,
			'remove_linebreaks' => false,
			'theme_advanced_buttons1' => "formatselect,fontselect,fontsizeselect,bold,italic,underline,strikethrough,separator,code,preview,fullscreen,separator,undo,redo",
			'theme_advanced_buttons2' => "justifyleft,justifycenter,justifyright,indent,outdent,separator,bullist,numlist,forecolor,backcolor,separator,link,unlink,image,media,table,separator,pastetext,pasteword,selectall,separator,hr",
			'theme_advanced_buttons3' => "",
			'theme_advanced_resize_horizontal' => false,
			'theme_advanced_resizing' => true,
			'theme_advanced_statusbar_location' => 'bottom',
			'theme_advanced_toolbar_align' => 'left',
			'theme_advanced_toolbar_location' => 'top'
		);
	}

	/**
	 * Преобразование списка файлов в необходимый json формат
	 * @param  array $files список файлов
	 * @return string        json
	 */
	public function image_list_to_js($files)
	{
		$res = 'var tinyMCEImageList = new Array(';
		$list = array();
		foreach ($files as $f) {
			$name = pathinfo($f, PATHINFO_BASENAME);
			$list[] = "[" . "'$name'" . ',' . "'$f'" .  "]";
		}
		$res .= implode(',', $list) .  ');';
		return $res;
	}
}