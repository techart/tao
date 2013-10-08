<?php
//require_once 'PHPUnit/Framework.php';


// подключаем файлы с тестами
require_once 'PDO/ConnectionTest.php';
require_once 'PDO/CursorTest.php';

class PDOSuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PDOSuite');
        // добавляем тест в набор
        $suite->addTestSuite('DB_Adapter_PDO_ConnectionTest');
        $suite->addTestSuite('DB_Adapter_PDO_CursorTest');

		return $suite; 
    }
}
