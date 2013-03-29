<?php

Core::load('DB.ORM');

class DB_ORM_Mappers implements Core_ModuleInterface {
  const VERSION = '0.1.0';
  
  static protected $set;
  
  static public function set() {
    return !empty(self::$set) ? self::$set : self::$set = new DB_ORM_Mappers_Set();
  }
}

class DB_ORM_Mappers_Set extends DB_ORM_ConnectionMapper {

  protected function setup() {
    return $this->submappers(array(
      'information' => 'Information.*'
    ), 'DB.ORM.Mappers.');
  }
  
}
