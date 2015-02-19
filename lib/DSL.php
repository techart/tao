<?php
/**
 * DSL
 *
 * Базовые классы, предназначенные для построения DSL
 *
 * @package DSL
 * @version 0.2.0
 */

/**
 * @package DSL
 */
class DSL implements Core_ModuleInterface
{
	const VERSION = '0.2.0';
}

/**
 * Базовый класс фабрик иерахически связанных объектов
 *
 * <p>Класс предназначен для использования в качестве базового при
 * построении систем иерархически связанных объектов.</p>
 *
 * @package DSL
 */
class DSL_Builder implements Core_PropertyAccessInterface
{

	protected $parent;
	protected $object;

	/**
	 * Конструктор
	 *
	 * @param        $parent
	 * @param object $object
	 */
	public function __construct($parent, $object)
	{
		$this->parent = $parent;
		$this->object = $object;
	}

	/**
	 * Возвращает значение свойства
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'end':
				return $this->parent ? $this->parent : $this->object;
			case 'object':
				return $this->$property;
			default:
				return $this->object->$property;
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return DSL_Builder
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет установку значения свойства
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'object':
				return isset($this->$property);
			default:
				return false;
		}
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Делегирует вызов конфигурируемому объекту
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		method_exists($this->object, $method) ?
			call_user_func_array(array($this->object, $method), $args) :
			$this->object->$method = $args[0];

		return $this;
	}

}

