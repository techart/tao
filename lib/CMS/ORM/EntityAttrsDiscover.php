<?php
/**
 * @package CMS\ORM\EntityAttrsDiscover
 */


class CMS_ORM_EntityAttrsDiscover implements Core_ModuleInterface
{

	protected $force_inspect = true;
	protected $enable_cache = true;

	public function __construct($force_inspect = true, $enable_cache = true)
	{
		$this->force_inspect = $force_inspect;
		$this->enable_cache = $enable_cache;
	}

	protected function from_schema($data) {
		$res =  Object::AttrList();
		$columns = $data['columns'];
		if (!empty($columns)) {
			foreach ($columns as $name => $column) {
				$shtype = $column['type'];
				$stype = null;
				switch (true) {
					case in_array($shtype, array('int', 'serial')):
						$stype = 'int';
						break;
					case in_array($shtype, array('float', 'numeric')):
						$stype = 'float';
						break;
					case in_array($shtype, array('timestamp', 'datetime', 'date')):
						$stype = 'datetime';
						break;
				}
				$res->value($name, $stype);
			}
		}
		return $res;
	}

	protected function cache_key($entity)
	{
		$table = $entity->mapper->options['table'][0];
		return "orm:$table:attrs";
	}

	public function cache()
	{
		return WS::env()->cache;
	}

	public function discover($entity, $flavor = array())
	{
		$ckey = $this->cache_key($entity);
		if ($this->enable_cache && $this->cache()->has($ckey)) {
			return $this->cache()->get($ckey);
		}
		$data = $entity->mapper ? $entity->mapper->schema_fields() : null;
		if (!empty($data) && !$this->force_inspect) {
			$data = CMS_Fields::fields_to_schema($data, $entity->mapper->options['table'][0]);
		} else {
			$data = $entity->mapper ? $entity->mapper->inspect() : null;
		}
		if (!empty($data)) {
			$res = $this->from_schema($data);
			if ($this->enable_cache) {
				$this->cache()->set($ckey, $res, 0);
			}
			return $res;
		}
	}
}