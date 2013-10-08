<?php
//require_once 'PHPUnit/Framework.php';


// подключаем файлы с тестами
require_once 'ORM/ModuleTest.php';
require_once 'ORM/MappingOptionsTest.php';
require_once 'ORM/SQLMapperTest.php';
require_once 'ORM/SQLBuilderTest.php';
require_once 'ORM/MapperSetTest.php';

class ORMSuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('ORMSuite');
        // добавляем тест в набор
//        $suite->addTestSuite('DB_ORM_MappingOptionsTest');
        $suite->addTestSuite('DB_ORM_SQLMapperTest');
//        $suite->addTestSuite('DB_ORM_MapperSetTest');
//        $suite->addTestSuite('DB_ORM_SQLBuilderTest');
//        $suite->addTestSuite('DB_ORM_ModuleTest');

		return $suite; 
    }
}
