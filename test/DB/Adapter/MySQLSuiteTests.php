<?php
//require_once 'PHPUnit/Framework.php';


// подключаем файлы с тестами
require_once 'MySQL/ConnectionTest.php';
require_once 'MySQL/CursorTest.php';
require_once 'MySQL/SchemaTest.php';

class MySQLSuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('MySQLSuite');
        // добавляем тест в набор
        $suite->addTestSuite('DB_Adapter_MySQL_CursorTest');
//        $suite->addTestSuite('DB_Adapter_MySQL_SchemaTest');
        $suite->addTestSuite('DB_Adapter_MySQL_ConnectionTest');

		return $suite; 
    }
}
