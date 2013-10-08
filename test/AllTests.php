<?php
/**
 * О структуре тестов.
 * 
 * Каждый тестируемый файл представляет собой модуль, 
 * который может содержать больше, чем один тестируемый файл.
 * 
 * Для каждого такого модуля сделана такая структура тестовых классов и файлов:
 * 
 * Файл Имя_МодуляSuiteTests.php содержит тест сьют для всех классов, входящих в тестируемый файл.
 * Тест для каждого класса содержится в отдельном файле. Например для модуля Object:
 * Тестируемый файл называется Object.php и лежит в каталоге lib
 * Тест сьют для него лежит в каталоге test. Еще в каталоге test лежит каталог Object, 
 * который содержит файлы самих тестов для классов из файла Object.php
 * В файле lib/ObjectSuiteTests.php происходит только загрузка файла с тестируемыми классами (Object.php) 
 * и файлов тестов (lib/Object/*Test.php). В нем же определяется функция suite(), где 
 * добавляются к тест сьюту классы тестов.
 * 
 * Файл конфигурации для phpunit => ../etc/phpunit.xml
 * Запуск tao/ make test
 * 
 */
// подключаем файл с набором тестов
require_once 'lib/Core.php';
Core::initialize();

// require_once 'CoreSuiteTests.php';
require_once 'DBSuiteTests.php';
require_once 'ValidationSuiteTests.php';
require_once 'ObjectSuiteTests.php';
require_once 'TimeSuiteTests.php';

class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('AllSuite');
        // добавляем наборы тестов
        // $suite->addTest(CoreSuiteTests::suite());
		$suite->addTest(ObjectSuiteTests::suite());
		$suite->addTest(ValidationSuiteTests::suite());
		$suite->addTest(DBSuiteTests::suite());
		$suite->addTest(TimeSuiteTests::suite());
        
        return $suite; 
    }
}

