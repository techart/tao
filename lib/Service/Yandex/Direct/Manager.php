<?php
/**
 * Service.Yandex.Direct.Manager
 *
 * @package Service\Yandex\Direct\Manager
 * @version 0.1.0
 */
Core::load('CLI.Application', 'IO.FS', 'Service.Yandex.Direct');

/**
 * @package Service\Yandex\Direct\Manager
 */
class Service_Yandex_Direct_Manager implements Core_ModuleInterface, CLI_RunInterface
{

	const VERSION = '0.2.0';

	/**
	 * @return int
	 */
	static public function main(array $argv)
	{
		return Core::with(new Service_Yandex_Direct_Manager_Application())->main($argv);
	}

}


/**
 * @package Service\Yandex\Direct\Manager
 */
class Service_Yandex_Direct_Manager_Exception extends Core_Exception
{
}


/**
 * @package Service\Yandex\Direct\Manager
 */
class Service_Yandex_Direct_Manager_MissingCertificateException extends Service_Yandex_Direct_Manager_Exception
{
	protected $path;

	/**
	 * @param string $path
	 */
	public function __construct($path = '')
	{
		$this->path = $path;
		parent::__construct($path === '' ? 'Missing certificate' : "Missing certificate: $path");
	}

}

/**
 * @abstract
 * @package Service\Yandex\Direct\Manager
 */
class Service_Yandex_Direct_Manager_MissingTaskFileException extends Service_Yandex_Direct_Manager_Exception
{

	protected $path;

	/**
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = $path;
		parent::__construct("Missing task file: $path");
	}

}


/**
 * @package Service\Yandex\Direct\Manager
 */
class Service_Yandex_Direct_Manager_BadArgumentException extends Service_Yandex_Direct_Manager_Exception
{
	protected $name;
	protected $value;

	/**
	 */
	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = (string)$value;

		parent::__construct("Bad argument value for $name: $value");
	}

}

/**
 * @abstract
 * @package Service\Yandex\Direct\Manager
 */
class Service_Yandex_Direct_Manager_Task
{

	protected $file;
	protected $name;
	protected $config;

	protected $log;

	/**
	 * @param IO_FS_File $file
	 * @param array      $options
	 */
	public function __construct(IO_FS_File $file, Service_Yandex_Direct_Manager_Application $app)
	{
		$this->file = $file;
		$this->name = IO_FS::Path($file->path)->filename;
		$this->config = $app->config;
		$this->log = $app->log->context(array('task' => $this->name));
	}

	/**
	 */
	public function run()
	{
		$api = Service_Yandex_Direct::api();
		$this->log->debug('Task %s started', $this->name);
		try {
			$campaigns = (isset($this->config->preload) && $this->config->preload) ?
				((isset($this->config->direct) && $this->config->direct) ?
					$api->all_campaigns() : $api->campaigns_for($this->name)) :
				new Service_Yandex_Direct_CampaignsCollection(array());
			//ob_start();
			include($this->file->path);
			//ob_end_clean();
		} catch (Exception $e) {
			$this->log->error("Task error: %s", $e->getMessage());
		}
	}

	/**
	 * @param float $limit
	 * @param       $phrases
	 * @param float $delta
	 *
	 * @return Service_Yandex_Direct_Manager_Task
	 */
	protected function stay_special($limit, $phrases, $delta = 0)
	{
		$this->log->debug('Running stay_special, limit %.2f', $limit);

		$phrases = $this->get_phrases_for($phrases);
		$prices = $phrases->prices;

		foreach ($phrases as $phrase) {
			$d = is_string($delta) ? ((float)$delta) / 100 * $phrase->premium_min : $delta;
			$prices->by_id($phrase->id)->price = ($phrase->premium_min + $d) <= $limit ? $phrase->premium_min + $d : $limit;
		}
		return $this->update_prices($prices);
	}

	/**
	 * @param float $limit
	 * @param       $phrases
	 * @param float $delta
	 *
	 * @return Service_Yandex_Direct_Manager_Task
	 */
	protected function stay_visible($limit, $phrases, $delta = 0)
	{
		$this->log->debug('Running stay_visible, limit %.2f', $limit);

		$phrases = $this->get_phrases_for($phrases);
		$prices = $phrases->prices;
		foreach ($phrases as $phrase) {
			$d = is_string($delta) ? ((float)$delta) / 100 * $phrase->min : $delta;
			$prices->by_id($phrase->id)->price =
				($phrase->min + $d) <= $limit ? $phrase->min + $d : $limit;
		}

		return $this->update_prices($prices);
	}

	/**
	 * @param float $limit
	 * @param       $phrases
	 * @param float $delta
	 *
	 * @return Service_Yandex_Direct_Manager_Task
	 */
	protected function only_special($limit, $phrases, $delta = 0)
	{
		$this->log->debug('Running only_special, limit %.2f', $limit);
		$phrases = $this->get_phrases_for($phrases);
		$prices = $phrases->prices;
		foreach ($phrases as $phrase) {
			$d = is_string($delta) ? ((float)$delta) / 100 * $phrase->premium_min : $delta;
			$prices->by_id($phrase->id)->price =
				($phrase->premium_min + $d) <= $limit ? $phrase->premium_min + $d : 0.01;
		}
		return $this->update_prices($prices);
	}

	/**
	 * @param float $limit
	 * @param float $gap
	 * @param       $phrases
	 * @param float $delta
	 *
	 * @return Service_Yandex_Direct_Manager_Task
	 */
	protected function try_first_stay_visible($limit, $phrases, $delta = 0)
	{
		$this->log->debug('Running try_first_stay_visible, limit %.2f', $limit);
		$phrases = $this->get_phrases_for($phrases);
		$prices = $phrases->prices;

		foreach ($phrases as $phrase) {
			$price = $phrase->price;
			$d = is_string($delta) ? ((float)$delta) / 100 * $phrase->min : $delta;
			switch (true) {
				case (($phrase->max + $d) <= $limit):
					$price = $phrase->max + $d;
					break;
				case ($phrase->min + $d) <= $limit:
					$price = $phrase->min + $d;
					break;
				default:
					$price = $limit;
			}
			$prices->by_id($phrase->id)->price = $price;
		}
		return $this->update_prices($prices);
	}

	/**
	 * @param float $limit
	 * @param float $gap
	 * @param       $phrases
	 * @param float $delta
	 *
	 * @return Service_Yandex_Direct_Manager_Task
	 */
	protected function try_special_stay_visible($limit, $gap, $phrases, $delta = 0)
	{
		$this->log->debug('Running try_special_stay_visible, limit %.2f', $limit);
		$phrases = $this->get_phrases_for($phrases);
		$prices = $phrases->prices;

		foreach ($phrases as $phrase) {
			$price = $phrase->price;
			switch (true) {
				case (($phrase->premium_min + $delta) <= $limit) && (($phrase->premium_min - $phrase->min) <= $gap):
					$price = $phrase->premium_min + $delta;
					break;
				case ($phrase->min + $delta) <= $limit:
					$price = $phrase->min + $delta;
					break;
				default:
					$price = $limit;
			}
			$prices->by_id($phrase->id)->price = $price;
		}
		return $this->update_prices($prices);
	}

	/**
	 * @param float $limit
	 * @param       $phrases
	 * @param float $delta
	 *
	 * @return Service_Yandex_Direct_Manager_Task
	 */
	protected function try_special($limit, $phrases, $delta = 0)
	{
		$this->log->debug('Running try_special, limit %.2f', $limit);

		$phrases = $this->get_phrases_for($phrases);
		$prices = $phrases->prices;

		foreach ($phrases as $phrase)
			$prices->by_id($phrase->id)->price =
				($phrase->premium_min + $delta < $limit) ?
					($phrase->premium_min + $delta) :
					(($phrase->price < $phrase->min) ?
						($phrase->min + $delta < $limit ?
							$phrase->min + $delta : $limit) :
						$limit);

		return $this->update_prices($prices);
	}

	/**
	 * @param Service_Yandex_Direct_PricesCollection $prices
	 *
	 * @return Service_Yandex_Direct_Manager_Task
	 */
	protected function update_prices(Service_Yandex_Direct_PricesCollection $prices)
	{
		if ($prices->update()) {
			$this->log->debug('Prices successfully updated');
		} else {
			$this->log->error('Prices not updated');
		}
		return $this;
	}

	/**
	 * @param  $phrases
	 *
	 * @return Service_Yandex_Direct_PhrasesCollection
	 */
	private function get_phrases_for($phrases)
	{
		switch (true) {
			case $phrases instanceof Service_Yandex_Direct_Campaign:
			case $phrases instanceof Service_Yandex_Direct_CampaignsCollection:
				$phrases = $phrases->all_banners()->all_phrases();
				break;
			case $phrases instanceof Service_Yandex_Direct_Banner:
			case $phrases instanceof Service_Yandex_Direct_BannersCollection:
				$phrases = $phrases->all_phrases();
				break;
			case $phrases instanceof Service_Yandex_Direct_PhrasesCollection:
				break;
			default:
				throw new Service_Yandex_Direct_Manager_BadArgumentException('stay_special->phrases', $phrases);
		}

		$phrases = $phrases->where(array('LowCTR' => 'No'));
		$this->log->debug('Got phrases: %d', count($phrases));
		return $phrases;
	}

}


/**
 * @package Service\Yandex\Direct\Manager
 */
class Service_Yandex_Direct_Manager_UserTask extends Service_Yandex_Direct_Manager_Task
{
}


/**
 * @package Service\Yandex\Direct\Manager
 */
class Service_Yandex_Direct_Manager_Application extends CLI_Application_Base
{

	protected $processed = 0;
	protected $log;

	/**
	 * @param array $argv
	 *
	 * @return int
	 */
	public function run(array $argv)
	{
		$this->
			check_certificate()->
			setup_api();

		return $this->config->run_all ? $this->run_all() : $this->run_tasks($argv);
	}

	/**
	 * @return Service_Yandex_Direct_Manager access=
	 */
	private function check_certificate()
	{
		if (!isset($this->config->cert)) {
			throw new Service_Yandex_Direct_Manager_MissingCertificateException();
		}
		if (!IO_FS::exists($p = $this->config->cert)) {
			throw new Service_Yandex_Direct_Manager_MissingCertificateException($p);
		}
		$this->log->debug('Using certificate: %s', $this->config->cert);
		return $this;
	}

	/**
	 * @return array
	 */
	private function configure_proxy()
	{
		$res = array();
		if (($proxy = (isset($this->config->proxy) ?
				$this->config->proxy :
				(($p = getenv('http_proxy')) ? $p : ''))) &&
			($m = Core_Regexps::match_with_results('{(?:https?://)?([^:]+):(?:(\d+))}', $proxy))
		) {
			if (isset($m[1])) {
				$res['proxy_host'] = $m[1];
			}
			if (isset($m[2])) {
				$res['proxy_port'] = $m[2];
			}
			$this->log->debug("Using proxy %s:%d", $m[1], $m[2]);
		}
		return $res;
	}

	/**
	 * @return Service_Yandex_Direct_Manager
	 */
	private function setup_api()
	{
		Service_Yandex_Direct::connect(
			array('local_cert' => $this->config->cert) + $this->configure_proxy()
		);
		$this->log->debug('API initialized');
		return $this;
	}

	/**
	 * @return Service_Yandex_Direct_Manager
	 */
	private function run_all()
	{
		$this->log->debug("Running all tasks for prefix %s", $this->config->prefix);
		foreach (IO_FS::Dir($this->config->prefix) as $file) {
			Core::with(new Service_Yandex_Direct_Manager_UserTask(IO_FS::File($file), $this))->run();
			$this->processed++;
		}
		$this->log->debug("All tasks complete");
	}

	/**
	 * @param array $tasks
	 *
	 * @return Service_Yandex_Direct_Manager
	 */
	private function run_tasks(array $tasks)
	{
		$this->log->debug("Running task list");
		foreach ($tasks as $name) {
			$path = $this->config->prefix ? $this->config->prefix . $name . '.php' : $name;
			if (!IO_FS::exists($path)) {
				throw new Service_Yandex_Direct_Manager_MissingTaskFileException($path);
			}
			Core::with(new Service_Yandex_Direct_Manager_Task(IO_FS::File($path), $this))->run();
			$this->processed++;
		}
		$this->log->debug("Task list complete");

		return $this;
	}

	/**
	 */
	protected function setup()
	{
		$this->options->
			brief('Service.Yandex.Direct.Manager ' . Service_Yandex_Direct_Manager::VERSION . ': Yandex.Direct campaigns manager')->
			string_option('cert', '-c', '--cert', 'Client certificate')->
			string_option('config_file', '-s', '--config', 'Use configuration file')->
			string_option('proxy', '-p', '--proxy', 'HTTP proxy')->
			string_option('prefix', '-i', '--prefix', 'Tasks prefix')->
			boolean_option('preload', '-l', '--preload', 'Preload campaigns')->
			boolean_option('run_all', '-a', '--all', 'Run all tasks')->
			boolean_option('direct', '-d', '--direct', 'Direct client, not agency');

		$this->config->certificate = null;
		$this->config->proxy = null;
		$this->config->prefix = '';
		$this->config->preload = true;
		$this->config->run_all = false;
		$this->config->direct = false;
	}

	/**
	 */
	protected function configure()
	{
		if ($this->config->config_file) {
			$this->log->debug('Using config: %s', $this->config->config_file);
			$this->load_config($this->config->config_file);
		}
	}

}

?>
