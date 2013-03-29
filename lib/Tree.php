<?php

// Base help function like create_tree

class Tree implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	//TODO: рфекторинг
  static public function create_tree($flat, $options = array()) {
    $childs = array();
    $rows = array();
    
    $id_name = isset($options['id_name']) ? $options['id_name'] : 'id';
    $title_name = isset($options['title_name']) ? $options['title_name'] : 'title';
    $parent_name = isset($options['parent_name']) ? $options['parent_name'] : 'parent_id';
    $childs_name = isset($options['childs_name']) ? $options['childs_name'] : 'childs';
    $flat_keys = isset($options['flat_keys']) ? $options['flat_keys'] : false;
    
    foreach($flat as $k => $row) {
      if (isset($options['process_callback']))
        $row = Core::invoke($options['process_callback'], array($row));
        
      if (is_string($row)) $row = array($title_name => $row);
      
      $id = (int) isset($row[$id_name]) ? $row[$id_name] : $k;
      
      
      $rows[$id] = $row;
      if (!isset($childs[$id])) $childs[$id] = array();
      
      if (isset($row[$childs_name])) {
        $childs[$id] += self::create_tree($row[$childs_name], $options);
      }
      
      $pid = $row[$parent_name];
      if (!is_null($pid) && $pid >= 0) {
        $pid = (int) $pid;
        if (!isset($childs[$pid])) $childs[$pid] = array();
        $childs[$pid][$id] = $row;
      }
    }

    foreach($rows as $id => &$row) {
      self::tree_row_childs($row, $id, $childs, $childs_name);
    }
    unset($row);
    
    $root = array();
    
    $root_exists = in_array(0, array_keys($rows), true);
    foreach($rows as $id => $row) {
      if ((intval($row[$parent_name]) == 0 && !$root_exists) || (is_null($row[$parent_name])) || $id == 0)
        $root[$id] = $row;
    }

    if ($flat_keys) {
      $root = array_values($root);
      array_walk($root, 'self::tree_row_flat', $childs_name);
    }

    return $root;
  }

  static protected function tree_row_flat(&$a, $key, $childs_name) {
    if (!empty($a[$childs_name])) {
          $a[$childs_name] = array_values($a[$childs_name]);
          array_walk($a[$childs_name], 'self::tree_row_flat', $childs_name);
    }
  }

  static protected function tree_row_childs(&$row, $id, $childs, $childs_name) {
      $row[$childs_name] = $childs[$id];
      if ($row[$childs_name])
        foreach ($row[$childs_name] as $ii => &$rr) {
          self::tree_row_childs($rr, $ii, $childs, $childs_name);
        }  
      return $row;
  }
}