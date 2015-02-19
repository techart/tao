<?php
Core::load('Templates.HTML.Assets.Preprocess');
Core::load('Templates.HTML.Assets.Postprocess');
Core::load('Templates.HTML.Assets.Join');

class Templates_HTML_Assets implements Core_ConfigurableModuleInterface
{

	const VERSION = '0.1.0';

	static protected $options = array();

	static public function initialize(array $options = array())
	{
		foreach (self::$options as $k => $v) {
			self::$options[$k] = str_replace('%files%', Core::option('files_name'), $v);
		}
		self::options($options);
	}

	static public function options(array $options = array())
	{
		if (count($options)) {
			Core_Arrays::update(self::$options, $options);
		}
		return self::$options;
	}

	static public function option($name, $value = null)
	{
		$prev = isset(self::$options[$name]) ? self::$options[$name] : null;
		if ($value !== null) {
			self::options(array($name => $value));
		}
		return $prev;
	}

	static public function agregator()
	{
		$args = func_get_args();
		return Core::amake('Templates.HTML.Assets.Agregator', $args);
	}

}

interface Templates_HTML_Assets_AgregatorInterface extends IteratorAggregate
{
	/* public function __construct()*/
	public function files_list();

	public function join($v = true);

	public function exclude_join($file);

	public function add($file, $weight = 0, $type = 'app', $join = true);

	public function remove($file);
}

class Templates_HTML_Assets_Agregator implements Core_IndexedAccessInterface, Templates_HTML_Assets_AgregatorInterface
{

	protected $files;
	protected $type;
	protected $stapler;
	protected $exclude_join = array();
	protected $preprocessors = array();
	protected $postprocessors = array();
	static protected $sort_types = array(
		'app', 'lib'
	);

	public function __construct($files = array(), $type = 'js')
	{
		$this->files = new ArrayObject($files);
		$this->type = $type;
		$this->stapler = Templates_HTML_Assets_Join::Stapler($type);
	}

	public function add_preprocessor($name, $p)
	{
		$this->preprocessors[$name] = $p;
		return $this;
	}

	public function add_postprocessor($name, $p)
	{
		$this->postprocessors[$name] = $p;
		return $this;
	}

	public function remove_postprocessor($name)
	{
		unset($this->postprocessors[$name]);
		return $this;
	}

	public function get_postprocessor($name)
	{
		return $this->postprocessors[$name];
	}

	public function remove_preprocessor($name)
	{
		unset($this->preprocessors[$name]);
		return $this;
	}

	public function get_preprocessor($name)
	{
		return $this->preprocessors[$name];
	}

	public function preprocess($path, $data = array(), $content = null)
	{
		Events::call('templates.assets.preprocess', $path, $data, $content);
		foreach ($this->preprocessors as $k => $p) {
			if (is_string($p)) {
				$p = Core::make($p);
				$this->preprocessors[$k] = $p;
			}
			list($path, $content) = $p->preprocess($path, $data);
		}
		if (!empty($content)) {
			$this->write($path, $content);
		}
		return $path;
	}

	public function postprocess($path, $data, $content = null)
	{
		Events::call('templates.assets.postprocess', $path, $data, $content);
		foreach ($this->postprocessors as $k => $p) {
			if (is_string($p)) {
				$p = Core::make($p);
				$this->postprocessors[$k] = $p;
			}
			list($path, $content) = $p->postprocess($path, $data, $content);
		}
		if (!empty($content)) {
			$this->write($path, $content);
		}
		return $path;
	}

	protected function write($path, $content)
	{
		$os_path = './' . ltrim($path, '\/.');
		$os_dir = dirname($os_path);
		if (!IO_FS::exists($dir)) {
			IO_FS::mkdir($os_dir);
		}
		IO_FS::File($os_path)->update($content);
		//IO_FS::chmod($path, IO_FS::option('file_mod'));
		return $this;
	}

	public function add($file, $weight = 0, $type = 'app', $join = true,
		$immutable = false, $attrs = array(), $add_timestamp = null, $minify = false, $absolute = null, $place = null)
	{
		$file = (string)$file;
		$exist = isset($this->files[$file]) ? $this->files[$file] : null;
		if (!empty($exist) && $exist['immutable']) {
			return $this;
		}
		$file_object = new ArrayObject(array(
			'weight' => $weight,
			'type' => $type,
			'join' => in_array($file, $this->exclude_join) ? false : $join,
			'immutable' => $immutable,
			'attrs' => $attrs,
			'minify' => $minify,
			'absolute' => $absolute,
			'place' => $place
		));
		if (!is_null($add_timestamp)) {
			$file_object['add_timestamp'] = $add_timestamp;
		}
		$this->files[$file] = $file_object;
		return $this;
	}

	public function add_file_array($file, $options = array())
	{
		$options = (array)$options;
		$file = (string)$file;
		$exist = isset($this->files[$file]) ? $this->files[$file] : null;
		if (!empty($exist) && $exist['immutable']) {
			return $this;
		}
		if (empty($exist)) {
			$this->add($file,
				empty($options['weight']) ? 0 : $options['weight'],
				empty($options['type']) ? 'app' : $options['type'],
				!isset($options['join']) ? true : $options['join'],
				!isset($options['immutable']) ? false : $options['immutable'],
				isset($options['attrs']) ? $options['attrs'] : array(),
				isset($options['add_timestamp']) ? $options['add_timestamp'] : null,
				isset($options['minify']) ? $options['minify'] : false,
				isset($options['absolute']) ? $options['absolute'] : null,
				isset($options['place']) ? $options['place'] : null
			);
		} else {
			$exist_options = $exist->getArrayCopy();
			$exist_options = Core_Arrays::deep_merge_update($exist_options, $options);
			$this->files[$file] = new ArrayObject($exist_options);
		}
		return $this;
	}

	public function remove($file)
	{
		unset($this->files[$file]);
		return $this;
	}

	public function sort($a, $b)
	{
		switch (true) {
			case $a['type'] == 'app' && $b['type'] == 'lib':
				return 1;
			case $a['type'] == 'lib' && $b['type'] == 'app':
				return -1;
			case $a['type'] == $b['type']:
				if ($a['weight'] == $b['weight']) {
					return 0;
				}
				return $a['weight'] < $b['weight'] ? -1 : 1;
		}
	}

	protected function file_path($file)
	{
		$method = $this->type . '_path';
		$path = ltrim(Templates_HTML::$method($file), '/');
		return Templates_HTML::is_url($path) ? $path : '/' . $path;
	}

	protected function auto_weigth($files = null)
	{
		if (is_null($files)) {
			$files = $this->files;
		}
		$types_weight = array();
		$delta = 0.0000001;
		foreach ($files as $name => $f) {
			if ($f['weight'] == 0) {
				if (!isset($types_weight[$f['type']])) {
					$types_weight[$f['type']] = $delta;
				}
				$types_weight[$f['type']] += $delta;
				$f['weight'] = $types_weight[$f['type']];
			}
		}
	}

	public function join($v = true)
	{
		if ($v) {
			$this->stapler->enable();
		} else {
			$this->stapler->disable();
		}
		return $this;
	}

	public function join_group($name, $files = array(), $data = array())
	{
		$args = func_get_args();
		$data = call_user_func_array(array($this->stapler, 'fasten_group'), $args);
		$data['group'] = true;
		$this->files[$name] = new ArrayObject($data);
		foreach ($files as $fname) {
			if (!isset($this->files[$fname])) {
				$this->add_file_array($fname, $data);
			}
			$this->files[$fname]['from_group'] = true;
		}
		return $this;
	}

	public function exclude_join($file)
	{
		if (isset($this[$file])) {
			$this[$file]['join'] = false;
		} else {
			$this->exclude_join[] = $file;
		}
	}

	public function filter_by_place($place = null)
	{
		$result = new ArrayObject();
		foreach ($this->files as $name => $item) {
			$r = (isset($item['place']) && $item['place'] == $place)
				|| (!isset($item['place']) && is_null($place));
			if ($r) {
				$result[$name] = $item;
			}
		}
		return $result;
	}

	// TODO: refoctoring this hell
	public function files_list($place = null)
	{
		$files = $this->filter_by_place($place);
		$this->auto_weigth($files);
		$files->uasort(array($this, 'sort'));
		$files_result = array();
		$index = 0;
		$join_indexes = array('default' => -1);
		$names = array();
		foreach ($files as $original_name => $data) {
			$data['original_name'] = $original_name;
			// is group save index & skip
			if (isset($data['group']) && $data['group']) {
				$join_indexes[$original_name] = $index;
				$index++;
				continue;
			}
			// run preprocess
			$file_path = $this->preprocess($original_name, $data);
			// get file path
			$file_path = $this->file_path($file_path);
			// do join if needed
			if ($this->stapler->fasten($file_path, $data)) {
				// save first file index for join (need for sort)
				if (!$data['from_group'] && $join_indexes['default'] == -1) {
					$join_indexes['default'] = $index;
				}
			} else { // no join
				// run postprocess
				$file_path = $this->postprocess($file_path, $data);
				// write result
				$files_result[$index] = $file_path;
				$names[$file_path] = $original_name;
			}
			$index++;
		}
		// join files
		$flash_result = $this->stapler->flash();
		foreach ($flash_result as $name => $flash_info) {
			if (is_null($flash_info)) {
				continue;
			}
			list($file_path, $data) = $flash_info;
			if ($data['write'] && !empty($data['content'])) {
				$this->write($file_path, $data['content']);
				unset($data['content']);
			}
			// run postprocess for join file
			$file_path = $this->postprocess($file_path, $data);
			// add ro result
			$files_result[$join_indexes[$name]] = $file_path;
			$names[$file_path] = $file_path;
			$this->files[$file_path] = $data;
		}
		// sort
		ksort($files_result);
		// write result
		$res = array();
		foreach ($files_result as $i => $file_path) {
			$file_data = isset($names[$file_path]) ? $files[$names[$file_path]] : array();
			$res[$file_path] = $file_data;
		}
		return $res;
	}

	public function getIterator()
	{
		return new ArrayIterator($this->files_list());
	}

	/**
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return $this->files[$index];
	}

	/**
	 * @param string $index
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		return $this->add_file_array($index, $value);
	}

	/**
	 * @param string $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->files[$index]);
	}

	/**
	 * @param string $index
	 */
	public function offsetUnset($index)
	{
		unset($this->files[$index]);
	}

}
