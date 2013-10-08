<?php

Core::load('DB.SQL');

class CMS_Entity extends DB_SQL_Entity implements Core_ModuleInterface { 
	const MODULE = 'CMS.Entity'; 
	const VERSION = '0.0.0'; 
	
	public function store() { 
		$id = $this->id(); 
		if ($id) $this->update(); else $this->insert(); 
	} 
	
	protected function where($query,$parms) { 
		return $query; 
	} 
	
	protected function limit($query,$parms) { 
		$limit = (int)$parms['limit']; 
		$offset = (int)$parms['offset']; 
		if ($limit==0) return $query; 
		return $query->limit($limit,$offset); 
	} 
	
	protected function order_by($query,$parms) { 
		if (isset($parms['order_by'])) return $query->order_by($parms['order_by']); 
		return $query; 
	} 
	
	public function extract_extra_values($tbl,$fe='extravalues') { 
		$e = unserialize((string)$this[$fe]); 
		if (is_array($e)) { 
			foreach(DB_SQL::db()->$tbl->columns as $column) unset($e[$column]); 
			foreach($e as $key => $value) $this->attributes[$key] = $value; 
		} 
	} 
	
	protected function fill_extra_values($tbl,$fe='extravalues') { 
		$e = array(); 
		foreach($this->attributes as $key => $value) $e[$key] = $value; 
		foreach(DB_SQL::db()->$tbl->columns as $column) unset($e[$column]); 
		$this[$fe] = serialize($e); 
	} 
	
	protected function multilink_update($table,$kname,$fname,$regexp=false) { 
		if ($this->id()>0) { 
			$del = "delete_$kname"; 
			DB_SQL::db()->$table->$del($this->id()); 
			if (!$regexp) $regexp = "/^$fname(\d+)$/"; 
			foreach($this->attributes as $key => $value) { 
				if ($m = Core_Regexps::match_with_results($regexp,$key)) { 
					$sid = $m[1]; 
					if ($value) { 
						$parms = array($kname=>$this->id,$fname=>$sid); 
						DB_SQL::db()->$table->insert($parms); 
					} 
				} 
			} 
		} 
	} 
	
	protected function multilink_load($table,$kname,$fname,$mask=false,$afield=false) { 
		if ($this->id()>0) { 
			if (!$afield) { 
				$afield = $table; 
			} 
			
			if (!$mask) { 
				$mask = "$fname%"; 
				$regexp = "/^$fname(\d+)$/"; 
			} 
			
			foreach($this->attributes as $key => $value) { 
				if ($m = Core_Regexps::match_with_results($regexp,$key)) { 
					unset($this->attributes[$key]); 
				} 
			} 
			
			$query = DB_SQL::db()->$table->select->where("$kname=:$kname"); 
			$rows = $query->run($this->id()); 
			$arr = array(); 
			foreach($rows as $row) { 
				$arr[] = $row->$fname; 
				$p = str_replace('%',$row->$fname,$mask); 
				$this->attributes[$p] = 1; 
			} 
			
			$this->attributes[$afield] = $arr; 
		} 
	}
	
	protected function serialize_parms() { 
		$args = func_get_args(); 
		foreach($args as $arg) { 
			if (isset($this->attributes[$arg])) { 
				if (!is_string($this->attributes[$arg])) { 
					$this->attributes[$arg] = serialize($this->attributes[$arg]); 
				} 
			} 
		} 
	} 
	
	protected function unserialize_parms() { 
		$args = func_get_args(); 
		foreach($args as $arg) { 
			if (!is_array($this->attributes[$arg])) $this->attributes[$arg] = unserialize((string)$this->attributes[$arg]); 
			if (!is_array($this->attributes[$arg])) $this->attributes[$arg] = array(); 
		} 
	} 
} 

