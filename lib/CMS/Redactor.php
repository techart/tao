<?php
/**
 * Возможность использовать разные редакторы
 *
 * @author Svistunov <svistunov@techart.ru>
 *
 * @version  0.1.0
 *
 * @package CMS\Redactor
 * 
 */


/**
 * Класс модуля CMS.Redactor
 *
 * Предназначен для регистрации и получения экземпляров редакторов.
 * Редактор имеет возможность подключить необходимые js/css файлы, провести необходимую инициализацию,
 * а также проверить наличие необходимых файлов для возможности подключения того или иного редактора.
 *
 * По умолчанию поддерживаются следующие редакторы:
 *
 * - *tiny* {@link http://www.tinymce.com/ TinyMCE}  
 * - *nicedit* {@link http://nicedit.com/ NicEdit}  
 * - *redactor* {@link http://imperavi.com/redactor/ Redactorjs}
 * - *ckeditor* {@link http://ckeditor.com/ CKEditor}
 * - *epic* {@link http://epiceditor.com/ EpicEditor}
 *
 * По умолчанию используется *redactor*, если же он не установлен, то используется *tiny*.
 * Для редактирования wiki разметки по умолчанию используется *epic*.
 *
 * Для установки редактора необходимо скопировать файлы в docroot/scripts/editors/.
 * Список файлов можно найти в соответствующем классе CMS.Redactor.*
 *
 * Для добавления своего редактора необходимо реализовать класс CMS.Redactor.AbstractEditor и зарегистрировать его 
 * с помощью CMS_Redactor::add_editor($name, $class), а также создать js обертку (см. scripts/tao/editor.js).
 *
 * Для изменения/добавления настроек редактора и установки другого редакторо по умолчанию можно воспользоваться Core::configure
 *
 * <code>
 * Core::configure(array(
 * 		'CMS.Redactor' => array(
 * 			'default_editor' => 'tiny',
 * 			'settings' => array('tiny' => array('theme' => 'modern')),
 * 		)
 * ));
 * </code>
 *
 * Чтобы воспользоваться редактором достаточно
 *
 * - у необходимого редактора вызвать *process_template* передав экземпляр шаблона
 * <code>
 * // в шаблоне
 * CMS_Redactor::get_default()->process_template($this);
 * </code>
 * - c помощью js обертки подключить редактор или вызвать
 * <code>
 * $editor->attach_to($this, '.class-of-textarea');//process_template($this, $selector = '.class-of-textarea')
 * </code>
 *
 * @package CMS\Redactor
 */
class CMS_Redactor implements Core_ModuleInterface
{


	/**
	 * Набор опций модуля
	 *
	 * - *editors* зарегистрированный редакторы
	 * - *settings* настройки редакторов
	 * - *default_editor* редактор по умолчанию
	 * - *fallback_editor* редактор, который используется при отсутствии default_editor
	 * 
	 * @var array
	 */
	private static $options = array(
		'editors' => array(),
		'settings' => array(),
		'default_editor' => 'nicedit',
		'fallback_editor' => 'textarea'
	);

	private static $editors = array();

	private static $instances = array();

	/**
	 * Инициализация модуля
	 * 
	 * @param  array  $options массив опций
	 */
	public static function initialize(array $options = array())
	{
		self::options($options);
		self::add_default_editors();
		if (isset(self::$options['editors'])) {
			self::add_editors(self::$options['editors']);
		}
	}

	/**
	 * Добавление редакторов по умолчанию
	 */
	protected static function add_default_editors()
	{
		self::add_editor('tiny', 'CMS.Redactor.Tiny');
		self::add_editor('redactor', 'CMS.Redactor.Redactor');
		self::add_editor('nicedit', 'CMS.Redactor.NicEdit');
		self::add_editor('ckeditor', 'CMS.Redactor.CKEditor');
		self::add_editor('epic', 'CMS.Redactor.Epic');
		self::add_editor('textarea', 'CMS.Redactor.Textarea');
	}

	/**
	 * Установка опций
	 * @param  array  $options масси опций
	 */
	public static function options(array $options = array())
	{
		self::$options = Core_Arrays::deep_merge_update(self::$options, $options);
	}

	/**
	 * Чтение опций
	 * @param  string $name название опции
	 * @return mixed       значение опции
	 */	
	public static function option($name)
	{
		return isset(self::$options[$name]) ? self::$options[$name] : null;
	}

	/**
	 * Регистрация редакторов
	 * @param array $editors массив редакторов для регистрации
	 */
	public static function add_editors($editors)
	{
		foreach ($editors as $name => $class) {
			self::add_editor($name, $class);
		}
	}

	/**
	 * Доступ к настройкам редактора
	 * @param  string $name название редактора
	 * @return array       массив настроек
	 */
	public static function get_settings($name)
	{
		return isset(self::$options['settings'][$name]) ? self::$options['settings'][$name] : array();
	}

	/**
	 * Регистрация редактора
	 * @param string $name  название редактора
	 * @param string $class класс редактора
	 */
	public static function add_editor($name, $class)
	{
		self::$editors[$name] = $class;
	}

	/**
	 * Получение экземпляра редактора
	 * @param  string $name имя редактора
	 * @return object       редактор
	 */
	public static function get_editor($name)
	{
		if (!isset(self::$instances[$name]) && !isset(self::$editors[$name])) {
			throw new Core_Exception("Editor '$name' not registered");
		}
		if (!isset(self::$instances[$name]) && isset(self::$editors[$name])) {
			self::$instances[$name] = Core::make(self::$editors[$name], $name, self::get_settings($name));
		}
		return self::$instances[$name];

	}

	/**
	 * Получение редактора по умолчанию
	 * @return object редактор
	 */
	public static function get_default()
	{
		$default = self::get_editor(self::get_default_name());
		return $default->is_installed() ? $default : self::get_editor(self::$options['fallback_editor']);
	}

	/**
	 * Имя редактора по умолчанию
	 * @return string 
	 */
	public function get_default_name()
	{
		return self::$options['default_editor'];
	}

}

/**
 * Интерфейс редактора
 *
 * @todo заполнить интерфейс
 *
 * @package CMS\Redactor
 */
interface CMS_Redactor_EditorInterface
{

}

/**
 * Абстрактный класс редактора
 * 
 * @package CMS\Redactor
 */
abstract class CMS_Redactor_AbstractEditor implements CMS_Redactor_EditorInterface
{

	/**
	 * Название редактора
	 * @var string
	 */
	protected $name;

	/**
	 * Настройки редактора
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Настройки редактора при подключении
	 * @var array
	 */
	protected $attach_options = array();

	/**
	 * Конструктор класса
	 * @param string $name     название редактора
	 * @param array  $settings массив настроек
	 */
	public function __construct($name, $settings = array())
	{
		$this->name = $name;
		$this->settings = $settings;
	}

	/**
	 * Проверяет установлен ли редактор
	 * @return boolean 
	 */
	public function is_installed()
	{
		foreach ($this->include_files() as $file) {
			$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
			if ($ext == 'js' && !is_file('.'.Templates_HTML::js_path($file['name']))) {
				return false;
			}
			if ($ext == 'css' && !is_file('.'.Templates_HTML::css_path($file['name']))) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Настройки по умолчанию
	 * @return array
	 */
	public function default_settings()
	{
		return array();
	}

	/**
	 * Обновление настроек редактора
	 * @param  array  $settings настройки
	 * @return self           
	 */
	public function update_settings(array $settings = array())
	{
		$this->settings = Core_Arrays::deep_merge_update($this->settings, $settings);
		return $this;
	}

	/**
	 * Получение настроек редактора
	 * @return array 
	 */
	public function settings()
	{
		return Core_Arrays::deep_merge_update($this->default_settings(), $this->settings);
	}

	/**
	 * Проверка установки и выдача сообщения об ошибке
	 * @param  mixed $t объект шаблона
	 * @return boolean    прошла ли валидация
	 */
	protected function validate_instalation($t)
	{
		if (!$this->is_installed()) {
			$t->root->no_duplicates_in('js');
			$error = ";alert('Editor \'{$this->name}\' not instaled');";
			$t->append_to('js', $error);
			return false;
		}
		return true;
	}

	/**
	 * Подключение необходимых файлов к шаблону
	 * @param mixed $t объект шаблона
	 */
	protected function add_files_to($t)
	{
		foreach ($this->include_files() as $file) {
			$t->use_file($file);
		}
		return $this;
	}

	/**
	 * Инициализации редактора
	 *
	 * Дополнительно можно указать jquery селектор textarea для автоматического подключения редактора
	 * @param  mixed $t        объект шаблона
	 * @param  string $selector jquery селектор
	 * @return self           
	 */
	public function process_template($t, $selector = '')
	{
		if (!$this->validate_instalation($t)) return $this;
		$this->add_files_to($t);

		Templates_HTML::add_scripts_settings(array('editor' => array($this->name => $this->settings())), true);
		if (!empty($selector)) {
			$this->attach_to($t, $selector);
		}
		return $this;
	}

	/**
	 * Дополнительные настройки при подключении
	 * @param  string $selector jquery селектор
	 * @return array           массив опций
	 */
	protected function attach_options($selector)
	{
		return $this->attach_options;
	}

	/**
	 * Подключение редактора
	 * @param  mixed $t        объект шаблона
	 * @param  string $selector jquery селектор
	 * @return self           
	 */
	public function attach_to($t, $selector)
	{
		$options = json_encode($this->attach_options($selector));
		$code = "\n ; TAO.editor.plugins.{$this->name}.attach('$selector', $options);\n";
		$t->append_to('js', $code);
		$this->attach_options = array();
		return $this;
	}

	/**
	 * Установка ссылки для добавления картинок в редактор
	 * @param string $link ссылка
	 */
	public function set_images_link($link)
	{
		return $this;
	}

	/**
	 * Список файлов для подключения в формате Templates_HTML::use_file
	 * 
	 * @see Templates_HTML::use_file
	 * @return [type] [description]
	 */
	public function include_files()
	{
		return array_merge(
			array(array('name' => '/' . CMS::stdfile('scripts/tao/editor.js'), 'type' => 'lib')),
			$this->libraries_files(),
			$this->application_files()
		);
	}

	/**
	 * Библиотечные файлы
	 *
	 * Обычно список файлов для подключения плагина
	 * @return array
	 */
	protected function libraries_files()
	{
		return array();
	}

	/**
	 * Файлы приложения
	 *
	 * Обчыно дополнительные файлы стилей (тем) или скриптов (инициализация)
	 * @return array 
	 */
	protected function application_files()
	{
		return array();
	}

	/**
	 * Преобразование списка файлов в необходимый json формат
	 * @param  array $files список файлов
	 * @return string        json
	 */
	public function image_list_to_js($files)
	{
		$res = array();
		foreach ($files as $f) {
			$res[] = array('thumb' => $f, 'image' => $f, 'title' => $f);
		}
		return json_encode($res);
	}


}


/**
 * Реализация редактора ввиде простой textarea
 * 
 * @package CMS\Redactor
 */
class CMS_Redactor_Textarea extends CMS_Redactor_AbstractEditor
{
	/**
	 * Всегда установлен
	 * @return boolean 
	 */
	public function is_installed()
	{
		return true;
	}

	/**
	 * Подключение к шаблону
	 * 
	 */
	public function process_template($t, $selector = '')
	{
		if (!$this->validate_instalation($t)) return $this;
		$this->add_files_to($t);
		return $this;
	}

	/**
	 * Подключение к шаблону
	 * 
	 */
	public function attach_to($t, $selector)
	{
		return $this;
	}
}