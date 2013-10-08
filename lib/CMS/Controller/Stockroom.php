<?php

Core::load('CMS.Controller.Table');

class CMS_Controller_Stockroom extends CMS_Controller_Table
{

	protected $title_list	= 'Библиотека компонентов';
	protected $norows	= 'Нет ни одного компонента';
	protected $connect_error = false;
	protected $can_add = false;
	protected $can_delete = false;
	protected $can_edit = false;
	protected $list_style = 'lent';
	protected $per_page = 10;
	protected $tags = array();
	protected $repository = 'tao';

	public function setup()
	{
		$url = $this->rep_query('tags.json');
		$this->tags = $this->get_query($url);
		return parent::setup()->render_defaults('connect_error','tags');
	}

	protected function rep_query($uri,$parms=array())
	{
		$url = self::current_repository_url();
		if (!$url) {
			die('No repositories!');
		}
		$q = $uri.'?site='.$_SERVER['HTTP_HOST'];
		foreach($parms as $k=>$v) {
			$q .= "&{$k}={$v}";
		}
		$url .= $q;
		return $url;
	}

	public static function current_repository_url()
	{
		$rep = self::current_repository();
		if (!$rep) {
			return false;
		}
		return CMS_Stockroom::$repositories[$rep]['url'];
	}

	public static function current_repository()
	{
		if (isset($_GET['repository'])&&isset(CMS_Stockroom::$repositories[$_GET['repository']])) {
			return $_GET['repository'];
		}
		if (count(CMS_Stockroom::$repositories)>0) {
			return array_shift(array_keys(CMS_Stockroom::$repositories));
		}
		return false;
	}

	protected function get_query($url)
	{
		if ($this->connect_error) {
			return false;
		}
		$agent = Net_HTTP::Agent(CMS_Stockroom::$curl_options)->act_as_browser();
		$res = $agent->send($url);
		if ($res->status->code!=200) {
			$this->connect_error = "<p>Ошибка подключения к <a href='{$url}'>репозиторию</a></p><p class='status'>";
			if ($res->status->code>0) {
				$this->connect_error .= "{$res->status->code}: ";
			}
			$this->connect_error .= "{$res->status->message}</p>";
			return false;
		}
		$rc = json_decode($res->body);
		if (is_null($rc)) {
			return $res->body;
		}
		return $rc;
	}

	protected function new_object()
	{
		return new CMS_Stockroom_Entity();
	}

	protected function templates_dir()
	{
		return CMS::views_path('admin/stockroom');
	}

	protected function filters()
	{
		return array('tag','search','repository');
	}

	protected function filters_form()
	{
		return array(
			'search' => array(
				'caption' => 'Поиск',
				'style' => 'width:150px',
			),
		);
	}

	protected function count_all($parms)
	{
		$url = $this->rep_query('count.json',$parms);
		$o = $this->get_query($url);
		if ($this->connect_error) {
			return 0;
		}
		if (is_object($o)) {
			if (isset($o->count)) {
				return (int)$o->count;
			}
		}
		$this->connect_error = "<p>Некорректный ответ от <a href='{$url}'>репозитория</a></p>";
		return 0;
	}

	protected function select_all($parms)
	{
		$url = $this->rep_query('select.json',$parms);
		$o = $this->get_query($url);
		if ($this->connect_error) {
			return array();
		}
		if (is_array($o)) {
			foreach($o as $n => $item) {
				$o[$n] = new CMS_Stockroom_Entity;
				$o[$n]->init_from($item);
			}
			return $o;
		}
		$this->connect_error = "<p>Некорректный ответ от <a href='{$url}'>репозитория</a></p>";
		return array();
	}

	protected function action_updok()
	{
		$item = $this->load_item($this->id);
		if (!$item) {
			return $this->page_not_found();
		}

		Core::load('Net.HTTP.Session');
		$session = Net_HTTP_Session::Store();

		$updated = isset($session['stockroom/updated'])? $session['stockroom/updated'] : array();
		if (!is_array($updated)) {
			$updated = array();
		}
		$not_updated = isset($session['stockroom/not_updated'])? $session['stockroom/not_updated'] : array();
		if (!is_array($not_updated)) {
			$not_updated = array();
		}

		return $this->render('updok',array(
			'item' => $item,
			'list_url' => $this->action_url('list',$this->page),
			'list_button_caption' => $this->button_list(),
			'updated' => $updated,
			'not_updated' => $not_updated,
		));
	}

	protected function action_update()
	{
		$item = $this->load_item($this->id);
		if (!$item) {
			return $this->page_not_found();
		}
		$error = false;

		if (!$item->installed()) {
			$error = 'Этот компонент еще не установлен. Попробуйте сначала установить его.';
		} elseif($item->installed()==$item['version']) {
			$error = 'Уже установлена последняя версия этого компонента. Обновление не требуется.';
		} else {
			if ($this->env->request->method=='post') {
				$files = $this->get_install_pack($item);
				if (is_string($files)) {
					$error = $files;
				} else {
					$updated = array();
					$not_updated = array();
					foreach($files as $file => $data) {
						if ($item->can_update_file($file)) {
							$from = $data['path'];
							$to = "../{$file}";
							if ($m = Core_Regexps::match_with_results('{^(.+)/[^/]+$}',$to)) {
								$dir = $m[1];
								if (!IO_FS::exists($dir)) {
									@CMS::mkdirs($dir);
									if (!IO_FS::exists($dir)) {
										$error = "Невозможно создать каталог {$dir}";
										break;
									}
								}
								$new_hash = $data['hash'];
								$old_hash = $item->get_installed_hash($file);
								if (!$old_hash||$old_hash!=$new_hash) {
									if ($item->file_was_changed($file)) {
										copy($from,"{$to}.upd");
										CMS::chmod_file("{$to}.upd");
										$not_updated[] = $file;
									} else {
										copy($from,$to);
										CMS::chmod_file($to);
										$updated[] = $file;
									}
									$item->set_installed_hash($file,$new_hash);
								}
							}
						}
					}
					Core::load('Net.HTTP.Session');
					$session = Net_HTTP_Session::Store();
					$session['stockroom/updated'] = $updated;
					$session['stockroom/not_updated'] = $not_updated;
					$item->save_info_file();
					CMS::rmdir($item->install_temp_dir());
					return $this->redirect_to($this->action_url('updok',$item));
				}
			}
		}

		return $this->render('update',array(
			'item' => $item,
			'error' => $error,
			'list_url' => $this->action_url('list',$this->page),
			'list_button_caption' => $this->button_list(),
		));
	}

	protected function load_item($id)
	{
		$url = $this->rep_query('info.json',array('id' => $id));
		$o = $this->get_query($url);
		if (!$o) {
			return false;
		}
		$item = new CMS_Stockroom_Entity();
		$item->init_from($o);
		return $item;
	}

	protected function get_install_pack($item)
	{
		$tdir = $item->install_temp_dir();
		if (IO_FS::exists($tdir)) {
			CMS::rmdir($tdir);
		}
		CMS::mkdirs($tdir);
		$zip = "{$tdir}/install.zip";
		$agent = Net_HTTP::Agent(CMS_Stockroom::$curl_options)->act_as_browser();
		$res = $agent->send($item['download']);
		if ($res->status->code==200) {
			$unknown = 'Некорректный инсталляционный пакет!';
			if (isset($res->headers['Content-Type'])) {
				if (Core_Regexps::match('{^application/zip}',$res->headers['Content-Type'])) {
					file_put_contents($zip,$res->body);
					CMS::chmod_file($zip);
					Core::load('IO.Arc');
					IO_Arc::ZIP($zip)->extract_to($tdir);
					unlink($zip);
					return $this->hash_dir($tdir,'',$item);
				} else {
					return $unknown;
				}
			} else {
				return $unknown;
			}
		} else {
			return "Error {$res->status->code} {$res->status->message}";
		}
	}

	protected function hash_dir($path,$prefix='',$item=false)
	{
		$out = array();
		foreach(IO_FS::Dir($path) as $file) {
			$fp = $prefix==''?$file->name:"{$prefix}/{$file->name}";
			if ($fp=='www') {
				$fp = CMS::www();
			}
			if (is_dir($file->path)) {
				$files = $this->hash_dir($file->path,$fp,$item);
				foreach($files as $key => $value) {
					$out[$key] = $value;
				}
			} else {
				if ($item&&Core_Regexps::match('{\.(php|phtml)$}',$file)) {
					$version = trim($item['version']);
					if ($version!='') {
						$content = file_get_contents($file->path);
						$content = str_replace('%%{version}',$version,$content);
						file_put_contents($file->path,$content);
					}
				}
				$out[$fp] = array(
					'path' => $file->path,
					'hash' => md5_file($file->path),
				);
			}
		}
		return $out;
	}

	protected function action_instok()
	{
		$item = $this->load_item($this->id);
		if (!$item) {
			return $this->page_not_found();
		}

		return $this->render('instok',array(
			'item' => $item,
			'list_url' => $this->action_url('list',$this->page),
			'list_button_caption' => $this->button_list(),
		));
	}

	protected function action_install()
	{
		$item = $this->load_item($this->id);
		if (!$item) {
			return $this->page_not_found();
		}
		$error = false;
		foreach($item->not_install_if_exists as $entry) {
			$entry = $this->validate_path($entry);
			if ($entry) {
				if (IO_FS::exists($entry)) {
					$error = "<b>Файл (каталог) {$entry}</b> уже существует. Установка невозможна!";
				}
			}
		}

		if ($item['download']=='-') {
			$error = 'Невозможно получить инсталляционный пакет!';
		}

		if ($this->env->request->method=='post') {
			$files = $this->get_install_pack($item);
			if (is_string($files)) {
				$error = $files;
			} else {
				foreach($files as $file => $data) {
					$from = $data['path'];
					$to = "../{$file}";
					if ($m = Core_Regexps::match_with_results('{^(.+)/[^/]+$}',$to)) {
						$dir = $m[1];
						if (!IO_FS::exists($dir)) {
							@CMS::mkdirs($dir);
							if (!IO_FS::exists($dir)) {
								$error = "Невозможно создать каталог {$dir}";
								break;
							}
						}
						copy($from,$to);
						CMS::chmod_file($to);
						$item->set_installed_hash($file,$data['hash']);
					}
				}
				$item->save_info_file();
				CMS::rmdir($item->install_temp_dir());
				return $this->redirect_to($this->action_url('instok',$item));
			}
		}

		return $this->render('install',array(
			'item' => $item,
			'error' => $error,
			'list_url' => $this->action_url('list',$this->page),
			'list_button_caption' => $this->button_list(),
		));
	}

	protected function validate_path($s)
	{
		$s = trim($s);
		if ($s=='') {
			return false;
		}
		if ($s[0]!='.'&&$s[0]!='/') {
			$s = "../{$s}";
		}
		if ($m = Core_Regexps::match_with_results('{^\.\./www/(.+)$}',$s)) {
			$s = '../'.CMS::www().'/'.$m[1];
		}
		return $s;
	}
}

class CMS_Stockroom_Entity extends CMS_ORM_Entity
{
	protected $rep = false;
	protected $installed_version = false;
	protected $hashes = array();

	public function init_from($o)
	{
		$this->rep = CMS_Controller_Stockroom::current_repository();
		foreach($o as $field => $value) {
			if ($field=='install_only'||$field=='not_install_if_exists') {
				$m = array();
				foreach($value as $s) {
					$s = preg_replace('{^www/}',CMS::www().'/',$s);
					$m[$s] = $s;
				}
				$value = $m;
			}
			$this[$field] = $value;
		}
		return $this;
	}

	public function can_update_file($file)
	{
		$this->load_info();
		if (isset($this['install_only'])&&is_array($this['install_only'])) {
			if (isset($this['install_only'][$file])) {
				return false;
			}
		}
		return true;
	}

	public function install_temp_dir()
	{
		return CMS::temp_dir().'/stockroom/'.$this->code();
	}

	public function code()
	{
		return $this->rep.$this->id();
	}

	public function save_info_file()
	{
		$title = $this['title'];
		$version = $this['version'];
		$content = "component {$title}\nversion {$version}\n";
		foreach($this->hashes as $file => $hash) {
			$content .= "hash {$hash} {$file}\n";
		}
		file_put_contents($this->info_path(),$content);
		CMS::chmod_file($this->info_path());
	}

	public function info_path()
	{
		$dir = CMS_Stockroom::$info_dir;
		if (!IO_FS::exists($dir)) {
			CMS::mkdirs($dir);
		}
		$code = $this->code();
		return "{$dir}/{$code}";
	}

	public function hashes()
	{
		$this->load_info();
		return $this->hashes;
	}

	public function load_info()
	{
		if (!$this->installed_version) {
			if( $content = $this->load_info_file()) {
				foreach(explode("\n",$content) as $line) {
					$line = trim($line);
					if ($m = Core_Regexps::match_with_results('{^version(.+)$}',$line)) {
						$this->installed_version = trim($m[1]);
					} elseif ($m = Core_Regexps::match_with_results('{^hash\s+([^\s]+)\s+(.+)$}',$line)) {
						$hash = trim($m[1]);
						$file = trim($m[2]);
						$this->hashes[$file] = $hash;
					}
				}
			}
		}
	}

	public function load_info_file()
	{
		$path = $this->info_path();
		if (IO_FS::exists($path)) {
			return file_get_contents($path);
		}
		return false;
	}

	public function set_installed_hash($file,$hash)
	{
		$this->load_info();
		return $this->hashes[$file] = $hash;
	}

	public function file_was_changed($file)
	{
		$installed = $this->get_installed_hash($file);
		if (!$installed) {
			return false;
		}
		$existed = $this->get_existed_hash($file);
		if (!$existed) {
			return false;
		}
		return $installed != $existed;
	}

	public function get_installed_hash($file)
	{
		$this->load_info();
		if (isset($this->hashes[$file])) {
			return $this->hashes[$file];
		}
		return false;
	}

	public function get_existed_hash($file)
	{
		$path = "../$file";
		if (!IO_FS::exists($path)) {
			return false;
		}
		return md5_file($path);
	}

	public function installed()
	{
		$this->load_info();
		return $this->installed_version;
	}

}