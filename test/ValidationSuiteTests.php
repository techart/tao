<?php
//require_once 'PHPUnit/Framework.php';

// тестируемые файлы
// Загружается через Core::load в тестируемом модуле Commons
// require_once('lib/Validation.php');

// подключаем файлы с наборами тестов
require_once 'Validation/CommonsSuiteTests.php';

// подключаем файлы с тестами
require_once 'Validation/ModuleTest.php';
require_once 'Validation/ValidatorTest.php';
require_once 'Validation/ErrorsTest.php';


class ValidationSuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('ValidationSuite');
        
        // добавляем наборы тестов
        $suite->addTest(Validation_CommonsSuiteTests::suite());
        // добавляем тест в набор
        $suite->addTestSuite('Validation_ErrorsTest'); 
        $suite->addTestSuite('Validation_ValidatorTest'); 
        $suite->addTestSuite('Validation_ModuleTest'); 
        
		return $suite; 
    }
}