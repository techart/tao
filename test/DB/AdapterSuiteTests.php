<?php
//require_once 'PHPUnit/Framework.php';


// подключаем файлы с тестами
require_once 'Adapter/ModuleTest.php';
require_once 'Adapter/MySQLSuiteTests.php';
//require_once 'Adapter/PDOSuiteTests.php';

class AdapterSuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('AdapterSuite');
        
        
        // добавляем тест в набор
        $suite->addTestSuite('DB_Adapter_ModuleTest');
        
        // Добавим сьюты
//        $suite->addTest(PDOSuiteTests::suite());
        $suite->addTest(MySQLSuiteTests::suite());

		return $suite; 
    }
}
