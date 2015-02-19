<?php
/**
 * @package Storage\File
 */

Core::load('Storage', 'IO.FS', 'Storage.Query.Array');

class Storage_File implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

}

abstract class Storage_File_Type extends Storage_Type
{

	protected $base_path = '';
	protected $use_query_cache = true;

	public function __construct($base_path = '../storage')
	{
		$this->base_path = $base_path;
		parent::__construct();
	}

	public function base_path()
	{
		return $this->base_path . ($this->name ? DIRECTORY_SEPARATOR . $this->name : '');
	}

	abstract public function read($file);

	abstract public function write($file, $data);

	public function path_to_entity($id = '', $name = '')
	{
		$path = implode(DIRECTORY_SEPARATOR, array_filter(array($this->base_path(), 'data', $id)));
		if (!IO_FS::exists($path)) {
			IO_FS::make_nested_dir($path);
		}
		return $path . ($name ? DIRECTORY_SEPARATOR . $name : '');
	}

	public function path_to_data()
	{
		return $this->path_to_entity();
	}

	public function add_to_index($e)
	{
		$index = $this->read($this->path_to_index());
		if (empty($index)) {
			$index = array('ids' => array($e->id()));
		} else {
			$index['ids'][] = $e->id();
		}
		return $this->write($this->path_to_index(), $index);
	}

	public function remove_from_index($e)
	{
		$index = $this->read($this->path_to_index());
		if (!empty($index['ids'])) {
			$key = array_search($e->id, $index['ids']);
			if ($key !== false) {
				unset($index['ids'][$key]);
			}
			return $this->write($this->path_to_index(), $index);
		}
		return true;
	}

	public function path_to_index()
	{
		return $this->base_path() . DIRECTORY_SEPARATOR . 'index';
	}

	public function generate_id()
	{
		$index = $this->read($this->path_to_index());
		if (empty($index) || empty($index['ids'])) {
			return 1;
		}
		return max($index['ids']) + 1;
	}

	public function get_ids()
	{
		$index = $this->read($this->path_to_index());
		if (!empty($index['ids'])) {
			return $index['ids'];
		}
		return array();
	}

	public function insert($e)
	{
		$id = $e->id();
		if (empty($id)) {
			$e['id'] = $this->generate_id();
		}
		$path = $this->path_to_entity($e->id(), 'values');
		if (IO_FS::exists($path)) {
			return $this->update($e);
		}
		if ($this->write($path, $e->as_array())) {
			$rc = $this->add_to_index($e);
			if ($rc) {
				$this->clear_query_cache();
			}
			return $rc;
		}
		return false;
	}

	public function find($id)
	{
		$path = $this->path_to_entity($id, 'values');
		if (IO_FS::exists($path)) {
			$attrs = $this->read($path);
			return $this->make_entity($attrs);
		}
		return null;
	}

	public function update($e)
	{
		$path = $this->path_to_entity($e->id(), 'values');
		if (IO_FS::exists($path)) {
			$attrs = $this->read($path);
			$ue = $this->make_entity($attrs);
			$ue->assign($e->attrs);
			$rc = $this->write($path, $ue->as_array());
			if ($rc) {
				$this->clear_query_cache();
			}
			return $rc;
		}
		return false;
	}

	public function delete($e)
	{
		$path = $this->path_to_entity($e->id());
		if (IO_FS::rm($path)) {
			$rc = $this->remove_from_index($e);
			if ($rc) {
				$this->clear_query_cache();
			}
			return $rc;
		}
		return false;
	}

	public function use_query_cache($v = true)
	{
		$this->use_query_cache = $v;
		return $this;
	}

	protected function query_cache_key($query)
	{
		$str = serialize($query);
		return md5($str);
	}

	protected function get_query_cache($query)
	{
		$key = $this->query_cache_key($query);
		$cache = $this->query_cache($key);
		if ($cache) {
			return $this->select_by_ids($cache);
		}
		return null;
	}

	protected function clear_query_cache()
	{
		if ($this->use_query_cache) {
			return (bool)IO_FS::rm($this->query_cache_path());
		}
		return false;
	}

	protected function set_query_cache($query, $res)
	{
		$ids = array();
		foreach ($res as $e)
			$ids[] = $e['id'];
		return $this->query_cache($this->query_cache_key($query), $ids);
	}

	protected function query_cache($key, $value = null)
	{
		$path = $this->query_cache_path($key);
		if (is_null($value)) {
			return $this->read($path);
		} else {
			return $this->write($path, $value);
		}
	}

	public function query_cache_path($key = '')
	{
		$base = $this->base_path() . DIRECTORY_SEPARATOR . 'query_cache';
		if (!IO_FS::exists($base)) {
			IO_FS::make_nested_dir($base);
		}
		return $base . ($key ? DIRECTORY_SEPARATOR . $key : '');
	}

	public function select_by_ids($ids)
	{
		$res = array();
		foreach ($ids as $id) {
			$res[] = $this->find($id);
		}
		return $res;
	}

	public function select($query = null)
	{
		$res = array();
		if (!$query && !$this->current_query->is_empty()) {
			$query = $this->current_query;
			$this->current_query = $this->create_query();
		}
		if ($query && $this->use_query_cache && $cache = $this->get_query_cache($query)) {
			return $cache;
		}
		$res = $this->select_by_ids($this->get_ids());
		if ($query) {
			$res = $query->execute($res);
			if ($this->use_query_cache) {
				$this->set_query_cache($query, $res);
			}
		}
		return $res;
	}

	//TODO: optimize
	public function count($query = null)
	{
		return count($this->select($query));
	}

	public function delete_all()
	{
		$this->clear_query_cache();
		return (bool)IO_FS::rm($this->base_path());
	}

	public function create_query()
	{
		return Storage_Query_Array::query();
	}

}