<?php

Core::load('DB.ORM');

class DB_ORM_Mappers_Information implements Core_ModuleInterface {
  const VERSION = '0.1.0';

  static public function mappers($parent) {
    return new DB_ORM_Mappers_Information_MapperSet($parent);
  }
}

class DB_ORM_Mappers_Information_MapperSet extends DB_ORM_MapperSet {
  
  protected function setup() {
    return $this->submappers(array(
      'tables' => 'TablesMapper',
      'columns' => 'ColumnsMapper',
      'statistics' => 'StatisticsMapper'
    ), 'DB.ORM.Mappers.Information.');
  }
  
}

class DB_ORM_Mappers_Information_Entity extends DB_ORM_Entity {

}

class DB_ORM_Mappers_Information_Base extends DB_ORM_SQLMapper {

  public function setup() {
    return $this->
      classname('DB.ORM.Mappers.Information.Entity');
  }
  
  public function map_for_schema($scheme) {
    return $this->where('table_schema = :scheme', (string) $scheme);
  }
  
  public function map_for_table($table) {
    return $this->where('table_name = :table', (string) $table);
  }

} 

class DB_ORM_Mappers_Information_TablesMapper extends DB_ORM_Mappers_Information_Base {

  public function setup() {
    return parent::setup()->
      table('information_schema.tables')->
      columns('table_name', 'table_schema');
  }
  
}

class DB_ORM_Mappers_Information_ColumnsMapper extends DB_ORM_Mappers_Information_Base {

  public function setup() {
    return parent::setup()->
      table('information_schema.columns')->
      columns(array('column_name', 'data_type', 'is_nullable', 'column_default', 'numeric_precision', 'numeric_scale', 'character_maximum_length'));
  }
  
  public function map_for_name($name) {
    return $this->where('column_name = :name', $name);
  }
  
  public function map_for_type($type) {
    if ($type) return $this->where('data_type = :d_type', $type);
    return $this;
  }
  
  public function map_for_nullable($nullable) {
    if (!is_null($nullable)) $this->where('is_nullable = :nullable', $nullable ? 'YES' : 'NO'); //????
    return $this;
  }
  
  public function map_for_default($default) {
    if (!empty($default)) return $this->where('column_default = :default', $default);
    return $this;
  }
  
  public function map_for_length($length) {
    if (!is_null($length)) return $this->where('character_maximum_length = :length', $length);
    return $this;
  }

}

class DB_ORM_Mappers_Information_StatisticsMapper extends DB_ORM_Mappers_Information_Base {

  public function setup() {
    return parent::setup()->
      table('information_schema.statistics')->
      columns('table_name', 'table_schema', 'column_name', 'index_name', 'sub_part', 'non_unique', 'index_type');
  }
  
}
