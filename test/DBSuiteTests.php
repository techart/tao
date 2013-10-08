<?php
//require_once 'PHPUnit/Framework.php';
require_once 'lib/DB/ORM.php';
//require_once 'lib/DB/Adapter.php';
//require_once 'lib/DB/Adapter/PDO.php';
require_once 'lib/DB/Adapter/MySQL.php';


// подключаем файлы с наборами тестов
require_once 'DB/ORMSuiteTests.php';
require_once 'DB/AdapterSuiteTests.php';

// подключаем файлы с тестами
require_once 'DB/DSNTest.php';

class DBSuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('DBSuite');

        // добавляем тест в набор
        $suite->addTestSuite('DB_DSNTest');

        // добавляем сьюты в набор
       // $suite->addTest(ORMSuiteTests::suite());
        // $suite->addTest(AdapterSuiteTests::suite());

		return $suite; 
    }
}