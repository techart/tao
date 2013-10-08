<?php
/**
 * Набор классов стандартных тестов валидации.
 * 
 * @package Validation\Commons
 */
 
Core::load('Validation');

/**
 * Класс модуля
 * 
 * @package Validation\Commons
 */
class Validation_Commons implements Core_ModuleInterface {

	/**
	 * Версия модуля
	 */
	const VERSION = '0.2.1';
	
	/**
	 * @var string Регулярное выражение для проверки формата Email адреса.
	 */
	static protected $email_regexp = '';

	/**
	 * Инициализация модуля.
	 * 
	 * @param array $options
	 * 
	 */
	static public function initialize($options = array()) {
		foreach ($options as $k => $v) if (isset(self::$k)) self::$k = $v;
	}
}

/**
 * Класс теста.
 * 
 * Проверяет атрибут на соответствие регулярному выражению.
 * 
 * @package Validation\Commons
 */
class Validation_Commons_FormatTest extends Validation_AttributeTest {
	/** @var string регулярное выражение */
	protected $regexp;

	/**
	 * Конструктор
	 * 
	 * @param string $attribute имя атрибута
	 * @param string $regexp регулярное выражение
	 * @param string $message сообщение об ошибке
	 */
	public function __construct($attribute, $regexp, $message) {
		$this->regexp = $regexp;
		parent::__construct($attribute, $message);
	}

	/** 
	 * Проверка значения.
	 * 
	 * Производит проверку значения атрибута на соответствие регулярному выражению.
	 * 
	 * @param string $value
	 * 
	 * @return boolean
	 */
	protected function do_test($value) {
		return Core_Regexps::match($this->regexp, $value);
	}
}


/**
 * Класс теста.
 * 
 * Проверяет атрибут на соответствие формату Email адреса.
 * Проверка не учитывает национальные алфавиты.
 * 
 * @package Validation\Commons
 */
class Validation_Commons_EmailTest extends Validation_Commons_FormatTest {
	/**
	 * Регулярное выражение для проверки по умочанию
	 */
	const REGEXP = '{^$|^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$}';

	/**
	 * Конструктор
	 * 
	 * @todo Согласовать c Validation_Commons::$email_regexp
	 * 
	 * 
	 * @param string $attribute имя атрибута
	 * @param string $message сообщение об ошибке
	 * @param string $regexp_email регулярное выражение null по умолчанию
	 */
	public function __construct($attribute, $message, $regexp_email = null) {
		$regexp = ($regexp_email) ? $regexp_email : self::REGEXP;
		parent::__construct($attribute, $regexp, $message);
	}
}

/**
 * Класс теста.
 * 
 * Проверяет атрибут на равенство значения двух атрибутов объекта.
 * 
 * @package Validation\Commons
 */
class Validation_Commons_ConfirmationTest extends Validation_AbstractTest {
	
	/** @var string имя атрибута */
	protected $attribute;
	
	/** @var string имя атрибута */
	protected $confirmation;
	
	/** @var string сообщение об ошибке */
	protected $message;

	/**
	 * Конструктор
	 * 
	 * @param string $attribute имя атрибута
	 * @param string $confirmation имя атрибута
	 * @param string $message сообщение об ошибке
	 */
	public function __construct($attribute, $confirmation, $message) {
		$this->attribute    = $attribute;
		$this->confirmation = $confirmation;
		$this->message      = $message;
	}

	/**
	 * Производит проверку объекта.
	 * 
	 * Проверяет атрибут на равенство значения двух атрибутов объекта.
	 * @see Validation_AbstractTest::value_of_attribute()
	 * @see Validation_AbstractTest::test()
	 * 
	 * @param mixed $object объект проверки
	 * @param Validation_Errors $errors Класс представляющий ошибки валидации
	 * @param boolean $array_access флаг индексного доступа к объекту
	 * 
	 * @return boolean
	 */
	public function test($object, Validation_Errors $errors, $array_access = false) {
		if ($this->value_of_attribute($object, $this->attribute, $array_access) != $this->value_of_attribute($object, $this->confirmation, $array_access)) {
			$errors->reject_value($this->attribute, $this->message);
			return false;
		}
		return true;
	}
}

/**
 * Класс теста.
 * 
 * Проверяет content type файла (IO.FS.File)
 * @todo Написать тест
 * 
 * @package Validation\Commons
 */
class Validation_Commons_ContentTypeTest extends Validation_AttributeTest {
	/** @var string content type файла */
	protected $content_type;

	/**
	 * Конструктор
	 * 
	 * @param string $attribute имя атрибута
	 * @param string $content_type content type файла
	 * @param string $message сообщение об ошибке
	 */
	public function __construct($attribute, $content_type, $message) {
		$this->content_type = $content_type;
		parent::__construct($attribute, $message);
	}

	/** 
	 * Проверка значения.
	 * 
	 * Производит проверку значения атрибута content_type на то, что оно 
	 * начинается со значения content_type, переданного в конструкторе.
	 * 
	 * @param IO_FS_File $value
	 * 
	 * @return boolean
	 */
	protected function do_test($value) {
		return ($value instanceof IO_FS_File) &&
			Core_Regexps::match("!^$this->content_type!", $value->content_type);
	}
}

/**
 * Класс теста.
 * 
 * Проверяет атрибут на незаполненность
 * 
 * @package Validation\Commons
 */
class Validation_Commons_PresenceTest extends Validation_AttributeTest {
	
	/** 
	 * Проверка значения.
	 * 
	 * Проверяет атрибут на отсутствие значения.
	 * 
	 * @param mixed $value
	 * 
	 * @return boolean
	 */
	protected function do_test($value)
	{
		if (is_string($value)) {
			$value = trim($value);
			return !empty($value);
		}
		return $value ? true : false;
	}
}

/**
 * Класс теста.
 * 
 * Проверяет значение атрибута на принадлежность к числам
 * 
 * @package Validation\Commons
 */
class Validation_Commons_NumericalityTest extends Validation_AttributeTest {

	/** 
	 * Проверка значения.
	 * 
	 * Проверяет атрибут на принадлежность к числам.
	 * Значение NULL интерпретируется как число.
	 * 
	 * @param mixed $value
	 * 
	 * @return boolean
	 */
	protected function do_test($value) { return $value == NULL || Core_Types::is_number($value); }
}

/**
 * Класс теста.
 * 
 * Проверяет значение атрибута на вхождение в заданный числовой интервал
 * 
 * @package Validation\Commons
 */
class Validation_Commons_NumericRangeTest extends Validation_AttributeTest {
	/** @var integer начало интервала */
	protected $from;

	/** @var integer конец интервала */
	protected $to;

	/**
	 * Конструктор
	 * 
	 * @param string $attribute имя атрибута
	 * @param string|integer $from начало интервала
	 * @param string|integer $to конец интервала
	 * @param string $message сообщение об ошибке
	 */
	public function __construct($attribute, $from, $to, $message) {
		$this->from = $from;
		$this->to   = $to;
		parent::__construct($attribute, $message);
	}

	/** 
	 * Проверка значения.
	 * 
	 * Проверяет значение атрибута на вхождение в заданный числовой интервал.
	 * Границы интервала считаются входящими в интервал. Если границы были представлены  
	 * в виде строк и не могут быть преобразованы в числа, то они преобразуются в значения 0.
	 * 
	 * @param string|integer $value
	 * 
	 * @return boolean
	 */
	protected function do_test($value) {
		return Core_Types::is_number($value) && ($value >= $this->from) && ($value <= $this->to);
	}
}

/**
 * Класс теста.
 * 
 * Проверяет значение атрибута на вхождение в заданный набор значений.
 * 
 * Класс может проверять вхождение элемента в набор элементов и 
 * равенство значения свойства объекта одному из значений этого свойства 
 * в наборе объектов.
 * 
 * Для проверки вхождения элемента в набор элементов:
 * параметр $values конструктора должен быть набором элементов(объектов, массивов, скаляров), 
 * среди которых будет выполняться поиск элемента $value.
 * $value должен быть того же типа, набором которых является параметр конструктора $values.
 * Параметр конструктора $options в этом случае не используется.
 * 
 * Для проверки равенство значения свойства объекта одному из значений 
 * этого свойства в наборе объектов:
 * параметр $values конструктора должен быть набором объектов,
 * $value должен быть объектом,
 * параметр конструктора $options должен содержать ключ 'attribute' и 
 * значением этого ключа должно быть имя проверяемого свойства объекта, переданного в $value.
 * Каждый из набора объектов $values должен содержать свойство с таким же именем.
 * 
 * @package Validation\Commons
 */
class Validation_Commons_InclusionTest extends Validation_AttributeTest {
	/** @var mixed[] набор значений */
	protected $values;
	
	/** @var array массив атрибутов по умолчанию array( 'attribute' => false ) */
	protected $options = array( 'attribute' => false );

	/**
	 * Конструктор
	 * 
	 * @param string $attribute имя атрибута
	 * @param mixed[] $values начало интервала
	 * @param string $message сообщение об ошибке
	 * @param array $options массив атрибутов 
	 */
	public function __construct($attribute, $values, $message, array $options = array()) {
		$this->values = $values;
		Core_Arrays::update($this->options, $options);
		parent::__construct($attribute, $message);
	}

	/**
	 * Проверка значения.
	 * 
	 * @param mixed $value См. описание работы класса.
	 * 
	 * @return boolean
	 */
	protected function do_test($value) {
		foreach ($this->values as $v)
			if ($this->is_equal($v, $value)) return true;
		return false;
	}

	/**
	 * Сравнение значений.
	 * 
	 * Сравнивает:
	 * - свои атрибуты своих аргументов, если в массиве атрибутов значение ключа attribute установлено;
	 * - сами аргументы, если не установлено.
	 * 
	 * @param mixed $arg1
	 * @param mixed $arg2
	 * 
	 * @return boolean
	 */
	protected function is_equal($arg1, $arg2) {
		return ($attribute = $this->options['attribute']) ?
			isset($arg1->$attribute) && isset($arg2->$attribute) && 
				$arg1->$attribute == $arg2->$attribute :
			$arg1 == $arg2;
	}
}
