<?php
// тестируемые файлы
//require_once ('lib/Core.php');

// подключаем файлы с тестами
require_once 'Core/RegexpTest.php';
require_once 'Core/StringsTest.php';
require_once 'Core/TypesTest.php';
require_once 'Core/ArraysTest.php';

require_once 'Core/ModuleTest.php';


class CoreSuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('CoreSuite');
        // добавляем тест в набор
        $suite->addTestSuite('Core_RegexpTest'); 
        $suite->addTestSuite('Core_StringsTest');
        $suite->addTestSuite('Core_TypesTest');
        $suite->addTestSuite('Core_ArraysTest');

        $suite->addTestSuite('Core_ModuleTest'); 
		return $suite; 
    }
}